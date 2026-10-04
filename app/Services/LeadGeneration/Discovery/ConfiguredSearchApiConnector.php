<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\RawLead;
use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfiguredSearchApiConnector implements LeadSourceConnectorInterface
{
    private static array $quotaExhaustedEndpoints = [];
    private ?int $remainingRunSearches = null;

    public function __construct(private SearchQueryBuilder $queries)
    {
    }

    public function beginSearchRun(?int $maxRequests = null): void
    {
        $this->remainingRunSearches = max(0, (int) ($maxRequests ?? config('lead_generation.search.requests_per_run', 1)));
    }

    public function discover(LeadSource $source, Campaign $campaign, array $options = []): iterable
    {
        $config = $this->sourceConfig($source);
        $endpoint = $config['endpoint'] ?? null;

        if (! $endpoint) {
            return [];
        }

        if (isset(self::$quotaExhaustedEndpoints[$endpoint])) {
            return [];
        }

        $client = new Client([
            'timeout' => (int) ($config['timeout'] ?? 20),
            'headers' => array_filter([
                'Accept' => 'application/json',
                'User-Agent' => $config['user_agent'] ?? config('app.name', 'Laravel') . ' Lead Discovery',
            ]),
        ]);

        $queries = $options['queries'] ?? $this->queries->buildForCampaign($campaign, $config);
        $limit = (int) ($options['limit'] ?? $config['limit'] ?? 25);
        $maxQueries = max(1, (int) ($config['max_queries_per_run'] ?? $limit));
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $items = [];
        $seenUrls = [];

        foreach (array_slice($queries, 0, $maxQueries) as $query) {
            $query = trim((string) $query);

            if ($query === '') {
                continue;
            }

            $decoded = $this->searchResponse($client, $endpoint, $query, $config, $source);

            if ($decoded === null) {
                return $items;
            }

            if (! is_array($decoded)) {
                Log::warning('Lead discovery search returned non-JSON response.', [
                    'source' => $source->name,
                    'provider' => $source->provider,
                    'endpoint' => $endpoint,
                    'query' => $query,
                ]);

                continue;
            }

            if (isset($decoded['error'])) {
                if ($this->isEmptyResultsErrorMessage((string) $decoded['error'])) {
                    continue;
                }

                Log::warning('Lead discovery search returned an API error.', [
                    'source' => $source->name,
                    'provider' => $source->provider,
                    'endpoint' => $endpoint,
                    'query' => $query,
                    'error' => $decoded['error'],
                ]);

                if ($this->isQuotaErrorMessage((string) $decoded['error'])) {
                    self::$quotaExhaustedEndpoints[$endpoint] = true;
                    $this->markMonthlySearchBudgetExhausted($config);

                    return $items;
                }

                continue;
            }

            foreach ($this->results($decoded, $config) as $item) {
                $mapped = $this->mapItem($item, $source, $campaign, $query, $config);
                $url = mb_strtolower((string) ($mapped['source_url'] ?? ''));

                if (! $this->allowedResult($mapped, $config)) {
                    continue;
                }

                if ($url !== '' && isset($seenUrls[$url])) {
                    continue;
                }

                if ($url !== '' && ! $dryRun && $this->resultAlreadySeen($url, $config)) {
                    continue;
                }

                if ($url !== '') {
                    $seenUrls[$url] = true;
                }

                $items[] = $mapped;

                if (count($items) >= $limit) {
                    return $items;
                }
            }
        }

        return $items;
    }

    private function sourceConfig(LeadSource $source): array
    {
        $global = config('lead_generation.search', []);
        $sourceConfig = $source->configuration ?: [];
        $config = array_replace_recursive($global, $sourceConfig);

        foreach (['endpoint', 'api_key', 'api_key_header', 'api_key_query_parameter', 'query_parameter', 'results_path'] as $key) {
            if (($sourceConfig[$key] ?? null) === null || ($sourceConfig[$key] ?? null) === '') {
                $config[$key] = $global[$key] ?? $config[$key] ?? null;
            }
        }

        $config['query'] = array_merge($global['query'] ?? [], $sourceConfig['query'] ?? []);
        $config['headers'] = array_merge($global['headers'] ?? [], $sourceConfig['headers'] ?? []);

        return $config;
    }

    private function authorizationHeaders(array $config): array
    {
        $headers = $this->stringMap($config['headers'] ?? []);

        if (! empty($config['api_key']) && ! empty($config['api_key_header'])) {
            $headers[$config['api_key_header']] = $config['api_key'];
        }

        return $headers;
    }

    private function queryParameters(array $config): array
    {
        $query = $this->stringMap($config['query'] ?? []);

        if (! empty($config['api_key']) && ! empty($config['api_key_query_parameter'])) {
            $query[$config['api_key_query_parameter']] = $config['api_key'];
        }

        return $query;
    }

    private function searchResponse(Client $client, string $endpoint, string $query, array $config, LeadSource $source): ?array
    {
        $requestQuery = array_merge($this->queryParameters($config), [
            $config['query_parameter'] ?? 'q' => $query,
        ]);
        $cacheKey = $this->queryCacheKey($endpoint, $config['method'] ?? 'GET', $requestQuery);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return is_array($cached) ? $cached : null;
        }

        if (isset(self::$quotaExhaustedEndpoints[$endpoint])) {
            return null;
        }

        if (! $this->hasMonthlySearchBudget($config)) {
            Log::warning('Lead discovery monthly search budget exhausted.', [
                'source' => $source->name,
                'provider' => $source->provider,
                'endpoint' => $endpoint,
                'query' => $query,
                'monthly_limit' => $this->monthlySearchLimit($config),
                'month' => now()->format('Y-m'),
            ]);

            self::$quotaExhaustedEndpoints[$endpoint] = true;

            return null;
        }

        if (! $this->hasRunSearchBudget()) {
            return null;
        }

        if (! $this->consumeMonthlySearch($config)) {
            self::$quotaExhaustedEndpoints[$endpoint] = true;

            return null;
        }

        $this->consumeRunSearch();

        try {
            $response = $client->request($config['method'] ?? 'GET', $endpoint, [
                'query' => $requestQuery,
                'headers' => $this->authorizationHeaders($config),
            ]);
        } catch (GuzzleException $exception) {
            Log::warning('Lead discovery search request failed.', [
                'source' => $source->name,
                'provider' => $source->provider,
                'endpoint' => $endpoint,
                'query' => $query,
                'status' => $this->responseStatus($exception),
                'error' => $this->responseError($exception),
                'message' => $this->safeExceptionMessage($exception),
            ]);

            if ($this->isQuotaExhausted($exception)) {
                self::$quotaExhaustedEndpoints[$endpoint] = true;
                $this->markMonthlySearchBudgetExhausted($config);
            }

            return null;
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (is_array($decoded) && (! isset($decoded['error']) || $this->isEmptyResultsErrorMessage((string) $decoded['error']))) {
            Cache::put($cacheKey, $decoded, now()->addDays($this->queryCacheTtlDays($config)));
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function queryCacheKey(string $endpoint, string $method, array $requestQuery): string
    {
        foreach (['api_key', 'key', 'token', 'access_token'] as $secretKey) {
            unset($requestQuery[$secretKey]);
        }

        ksort($requestQuery);

        return 'lead_generation:search_query:' . sha1($method . '|' . $endpoint . '|' . json_encode($requestQuery));
    }

    private function queryCacheTtlDays(array $config): int
    {
        return max(1, (int) ($config['cache_ttl_days'] ?? config('lead_generation.search.cache_ttl_days', 30)));
    }

    private function resultAlreadySeen(string $url, array $config): bool
    {
        if (Lead::query()->where('source_url', $url)->exists() || RawLead::query()->where('source_url', $url)->exists()) {
            return true;
        }

        $key = 'lead_generation:search_result:' . sha1($url);
        $ttl = now()->addDays(max(1, (int) ($config['dedupe_ttl_days'] ?? config('lead_generation.search.dedupe_ttl_days', 35))));

        return ! Cache::add($key, true, $ttl);
    }

    private function hasRunSearchBudget(): bool
    {
        return $this->remainingRunSearches === null || $this->remainingRunSearches > 0;
    }

    private function consumeRunSearch(): void
    {
        if ($this->remainingRunSearches !== null) {
            $this->remainingRunSearches = max(0, $this->remainingRunSearches - 1);
        }
    }

    private function hasMonthlySearchBudget(array $config): bool
    {
        $limit = $this->monthlySearchLimit($config);

        if ($limit < 0) {
            return true;
        }

        return $this->monthlySearchUsage()['used'] < $limit;
    }

    private function consumeMonthlySearch(array $config): bool
    {
        $limit = $this->monthlySearchLimit($config);

        if ($limit < 0) {
            return true;
        }

        [$setting, $usage] = $this->monthlySearchUsageWithSetting();

        if ((int) ($usage['used'] ?? 0) >= $limit) {
            return false;
        }

        $usage['used'] = (int) ($usage['used'] ?? 0) + 1;
        $usage['limit'] = $limit;
        $setting->update(['value' => $usage]);

        return true;
    }

    private function markMonthlySearchBudgetExhausted(array $config): void
    {
        $limit = $this->monthlySearchLimit($config);

        if ($limit < 0) {
            return;
        }

        [$setting, $usage] = $this->monthlySearchUsageWithSetting();
        $usage['used'] = $limit;
        $usage['limit'] = $limit;
        $setting->update(['value' => $usage]);
    }

    private function monthlySearchUsage(): array
    {
        return $this->monthlySearchUsageWithSetting()[1];
    }

    private function monthlySearchUsageWithSetting(): array
    {
        $month = now()->format('Y-m');
        $limit = $this->monthlySearchLimit([]);
        $setting = Setting::query()->firstOrCreate(
            ['key' => 'lead_generation.search_usage'],
            [
                'value' => ['month' => $month, 'used' => 0, 'limit' => $limit],
                'type' => 'array',
                'group' => 'lead_generation',
                'description' => 'Monthly SerpApi search usage for lead discovery.',
            ]
        );
        $usage = is_array($setting->value) ? $setting->value : [];

        if (($usage['month'] ?? null) !== $month) {
            $usage = ['month' => $month, 'used' => 0, 'limit' => $limit];
            $setting->update(['value' => $usage]);
        }

        $usage['used'] = (int) ($usage['used'] ?? 0);
        $usage['limit'] = $limit;

        return [$setting, $usage];
    }

    private function monthlySearchLimit(array $config): int
    {
        return (int) ($config['monthly_limit'] ?? config('lead_generation.search.monthly_limit', 250));
    }

    private function responseStatus(GuzzleException $exception): ?int
    {
        return $exception instanceof RequestException
            ? $exception->getResponse()?->getStatusCode()
            : null;
    }

    private function responseError(GuzzleException $exception): ?string
    {
        if (! $exception instanceof RequestException || ! $exception->getResponse()) {
            return null;
        }

        $body = (string) $exception->getResponse()->getBody();
        $decoded = json_decode($body, true);

        if (is_array($decoded) && isset($decoded['error']) && is_scalar($decoded['error'])) {
            return (string) $decoded['error'];
        }

        return trim(mb_substr($body, 0, 300)) ?: null;
    }

    private function safeExceptionMessage(GuzzleException $exception): string
    {
        $message = $exception->getMessage();
        $message = preg_replace('/([?&](?:api_key|key|token|access_token)=)[^&\s]+/i', '$1[redacted]', $message) ?: $message;

        return preg_replace('/(api[_-]?key["\']?\s*[:=]\s*["\']?)[^"\'\s,&}]+/i', '$1[redacted]', $message) ?: $message;
    }

    private function isQuotaExhausted(GuzzleException $exception): bool
    {
        return $this->responseStatus($exception) === 429
            || $this->isQuotaErrorMessage($this->responseError($exception) ?: $exception->getMessage());
    }

    private function isQuotaErrorMessage(string $message): bool
    {
        $message = mb_strtolower($message);

        return str_contains($message, 'run out of searches')
            || str_contains($message, 'too many requests')
            || str_contains($message, 'quota')
            || str_contains($message, 'rate limit');
    }

    private function isEmptyResultsErrorMessage(string $message): bool
    {
        $message = mb_strtolower($message);

        return str_contains($message, "hasn't returned any results")
            || str_contains($message, 'has not returned any results')
            || str_contains($message, 'no results')
            || str_contains($message, 'zero results')
            || str_contains($message, 'did not match any documents');
    }

    private function results(array $decoded, array $config): array
    {
        $paths = array_values(array_unique(array_filter(array_merge(
            [$config['results_path'] ?? null],
            $config['results_paths'] ?? []
        ))));

        foreach ($paths as $path) {
            $results = Arr::get($decoded, $path, []);

            if (is_array($results) && $this->isList($results)) {
                return $results;
            }
        }

        return $this->isList($decoded) ? $decoded : [];
    }

    private function mapItem(array $item, LeadSource $source, Campaign $campaign, string $query, array $config): array
    {
        $map = $config['field_map'] ?? [];
        $sourceUrl = $this->value($item, $map['source_url'] ?? ['url', 'link']);
        $description = $this->value($item, $map['description'] ?? ['description', 'snippet', 'content']);
        $explicitCompanyName = $this->value($item, ['company_name', 'business_name', 'organization']);
        $companyName = $this->cleanCompanyName(
            $this->value($item, $map['company_name'] ?? ['company_name', 'name', 'title']),
            $config
        );
        $isGroupRequest = (bool) ($config['allows_group_requests'] ?? false);

        if ($isGroupRequest && ! $explicitCompanyName) {
            $companyName = 'Facebook Group Request';
        } elseif ($isGroupRequest && $this->looksLikeBadCompanyName($companyName)) {
            $companyName = 'Facebook Group Request';
        }

        $website = $this->publicWebsite($this->value($item, $map['website'] ?? ['website', 'company_website', 'official_website']));
        $email = $this->value($item, $map['email'] ?? ['email']);
        $phone = $this->value($item, $map['phone'] ?? ['phone', 'telephone']);
        $contactMethods = $this->contactMethods($item, $sourceUrl, $website, $email, $phone);
        $website = $website ?: $this->firstContactMethodValue($contactMethods, ['website']);
        $email = $email ?: $this->firstContactMethodValue($contactMethods, ['email']);
        $phone = $phone ?: $this->firstContactMethodValue($contactMethods, ['phone', 'whatsapp']);
        $campaignContext = $this->campaignContext($campaign, $query);
        $quality = $this->qualitySignals($companyName, $description, $sourceUrl, $query, $campaignContext);

        return [
            'source' => $source->name,
            'source_url' => $sourceUrl,
            'company_name' => $companyName,
            'website' => $website,
            'email' => $email,
            'phone' => $phone,
            'contact_methods' => $contactMethods,
            'location' => $this->value($item, $map['location'] ?? ['location', 'address']),
            'raw_data' => array_filter(array_merge($item, [
                'query' => $query,
                'description' => $description,
                'country' => $campaignContext['country'],
                'city' => $campaignContext['city'],
                'industry' => $campaignContext['industry'],
                'campaign' => $campaign->name,
                'candidate_quality_score' => $quality['score'],
                'candidate_quality_signals' => $quality['signals'],
                'source_reference' => $config['source_reference'] ?? $source->provider ?? $source->name,
                'platform' => $config['platform'] ?? $source->provider,
                'candidate_type' => $isGroupRequest ? 'facebook_group_request' : 'business_profile',
                'public_profile_url' => $sourceUrl,
                'contact_methods' => $contactMethods,
            ]), fn ($value) => $value !== null && $value !== ''),
        ];
    }

    private function allowedResult(array $item, array $config): bool
    {
        $url = strtolower((string) ($item['source_url'] ?? ''));
        $companyName = (string) ($item['company_name'] ?? '');

        if ($url === '') {
            return false;
        }

        if ($this->looksLikeBadCompanyName($companyName)) {
            return false;
        }

        foreach ($config['blocked_url_contains'] ?? [] as $blocked) {
            if (is_string($blocked) && $blocked !== '' && str_contains($url, strtolower($blocked))) {
                return false;
            }
        }

        $allowed = array_values(array_filter($config['allowed_url_contains'] ?? []));

        if ($allowed === []) {
            return true;
        }

        foreach ($allowed as $needle) {
            if (is_string($needle) && $needle !== '' && str_contains($url, strtolower($needle))) {
                $qualityScore = (int) ($item['raw_data']['candidate_quality_score'] ?? 0);
                $qualitySignals = (array) ($item['raw_data']['candidate_quality_signals'] ?? []);

                if ($this->hasFatalNegativeSignal($qualitySignals)) {
                    return false;
                }

                if (($config['requires_positive_intent'] ?? false) && ! $this->hasPositiveIntentSignal($qualitySignals)) {
                    return false;
                }

                return $qualityScore >= (int) ($config['min_quality_score'] ?? 10);
            }
        }

        return false;
    }

    private function campaignContext(Campaign $campaign, string $query): array
    {
        $text = mb_strtolower($query);

        return [
            'country' => $this->firstMentioned($campaign->countries ?: [], $text),
            'city' => $this->firstMentioned($campaign->cities ?: [], $text),
            'industry' => $this->firstMentioned($campaign->industries ?: [], $text),
        ];
    }

    private function firstMentioned(array $values, string $text): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && $value !== '' && str_contains($text, mb_strtolower($value))) {
                return $value;
            }
        }

        return null;
    }

    private function contactMethods(array $item, ?string $sourceUrl, ?string $website, ?string $email, ?string $phone): array
    {
        $methods = [];
        $text = implode(' ', $this->flattenScalarValues($item));

        if ($email) {
            $methods[] = $this->contactMethod('email', $email, 'search_field', 0.95);
        }

        foreach ($this->extractEmails($text) as $value) {
            $methods[] = $this->contactMethod('email', $value, 'search_text', 0.9);
        }

        if ($phone) {
            $methods[] = $this->contactMethod('phone', $phone, 'search_field', 0.9);
        }

        foreach ($this->extractWhatsAppContacts($text) as $method) {
            $methods[] = $method;
        }

        foreach ($this->extractPhones($text) as $value) {
            $methods[] = $this->contactMethod('phone', $value, 'search_text', 0.75);
        }

        if ($website = $this->publicWebsite($website)) {
            $methods[] = $this->contactMethod('website', $website, 'search_field', 0.85);
        }

        foreach ($this->extractUrls($text) as $url) {
            $type = $this->contactTypeForUrl($url);
            $confidence = $type === 'website' ? 0.75 : 0.7;
            $methods[] = $this->contactMethod($type, $url, 'search_text', $confidence);
        }

        if ($sourceUrl) {
            $methods[] = $this->contactMethod($this->contactTypeForUrl($sourceUrl), $sourceUrl, 'source_profile', 0.8);
        }

        return $this->uniqueContactMethods($methods);
    }

    private function firstContactMethodValue(array $methods, array $types): ?string
    {
        foreach ($methods as $method) {
            if (in_array($method['type'] ?? null, $types, true) && ! empty($method['value'])) {
                return $method['value'];
            }
        }

        return null;
    }

    private function contactMethod(string $type, ?string $value, string $source, float $confidence, array $extra = []): array
    {
        $value = is_string($value) ? trim($value, " \t\n\r\0\x0B.,;()[]{}<>\"'") : null;

        return array_filter(array_merge([
            'type' => $type,
            'value' => $value,
            'source' => $source,
            'confidence' => $confidence,
        ], $extra), fn ($item) => $item !== null && $item !== '');
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/(?<![A-Z0-9._%+\-])[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}(?![A-Z0-9.\-])/i', $text, $matches);

        return array_values(array_unique(array_map(
            fn ($email) => mb_strtolower(trim($email)),
            $matches[0] ?? []
        )));
    }

    private function extractWhatsAppContacts(string $text): array
    {
        $methods = [];

        preg_match_all('/(?:wa\.me\/|whatsapp\.com\/send\?phone=)(\+?\d{7,15})/i', $text, $matches);

        foreach ($matches[1] ?? [] as $phone) {
            $normalized = $this->normalizePhone($phone);
            $methods[] = $this->contactMethod('whatsapp', $normalized, 'whatsapp_link', 0.95, [
                'url' => 'https://wa.me/' . ltrim((string) $normalized, '+'),
            ]);
        }

        preg_match_all('/whats\s*app|whatsapp/i', $text, $whatsAppMentions, PREG_OFFSET_CAPTURE);

        foreach ($whatsAppMentions[0] ?? [] as $mention) {
            $offset = max(0, (int) $mention[1] - 40);
            $nearby = substr($text, $offset, 120);

            foreach ($this->extractPhones($nearby) as $phone) {
                $methods[] = $this->contactMethod('whatsapp', $phone, 'whatsapp_text', 0.85);
            }
        }

        return $methods;
    }

    private function extractPhones(string $text): array
    {
        $text = preg_replace('/https?:\/\/\S+|www\.\S+|[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', ' ', $text) ?: $text;
        preg_match_all('/(?:\+|00)?\d[\d\s().-]{7,}\d/', $text, $matches);

        $phones = [];

        foreach ($matches[0] ?? [] as $phone) {
            $normalized = $this->normalizePhone($phone);
            $digits = preg_replace('/\D+/', '', (string) $normalized) ?: '';

            if (strlen($digits) < 8 || strlen($digits) > 15) {
                continue;
            }

            if (preg_match('/^(19|20)\d{6,}$/', $digits)) {
                continue;
            }

            $phones[] = $normalized;
        }

        return array_values(array_unique($phones));
    }

    private function extractUrls(string $text): array
    {
        preg_match_all('/(?:https?:\/\/|www\.)[^\s<>"\']+|(?<!@)\b[a-z0-9][a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s<>"\']*)?/i', $text, $matches);

        $urls = [];

        foreach ($matches[0] ?? [] as $url) {
            $normalized = $this->normalizeUrl($url);

            if ($normalized) {
                $urls[] = $normalized;
            }
        }

        return array_values(array_unique($urls));
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url, " \t\n\r\0\x0B.,;()[]{}<>\"'");
        $url = $this->unwrapSearchRedirect($url);

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? rtrim($url, '/') : null;
    }

    private function publicWebsite(?string $url): ?string
    {
        $url = $this->normalizeUrl($url);

        if (! $url || $this->contactTypeForUrl($url) !== 'website') {
            return null;
        }

        return $url;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        return $hasPlus ? '+' . $digits : $digits;
    }

    private function contactTypeForUrl(string $url): string
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = mb_strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_contains($host, 'linkedin.com') => 'linkedin',
            str_contains($host, 'facebook.com') => 'facebook',
            str_contains($host, 'instagram.com') => 'instagram',
            str_contains($host, 'x.com') || str_contains($host, 'twitter.com') => 'x',
            str_contains($host, 'wa.me') || str_contains($host, 'whatsapp.com') => 'whatsapp_link',
            str_contains($host, 'google.') && (str_starts_with($path, '/url') || str_starts_with($path, '/search/about-this-result')) => 'search_artifact',
            $host === 'serpapi.com' => 'search_artifact',
            default => $this->isShortLinkHost($host) ? 'short_link' : 'website',
        };
    }

    private function unwrapSearchRedirect(string $url): string
    {
        $parts = parse_url($url);
        $host = mb_strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || ! str_contains($host, 'google.')) {
            return $url;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        foreach (['url', 'q'] as $key) {
            if (! empty($query[$key]) && is_string($query[$key]) && preg_match('/^https?:\/\//i', $query[$key])) {
                return $query[$key];
            }
        }

        return $url;
    }

    private function uniqueContactMethods(array $methods): array
    {
        $seen = [];
        $unique = [];

        foreach ($methods as $method) {
            $value = $method['value'] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $key = mb_strtolower(($method['type'] ?? 'unknown') . ':' . $value);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $method;
        }

        return $unique;
    }

    private function isShortLinkHost(string $host): bool
    {
        return in_array($host, ['bit.ly', 'tinyurl.com', 't.co', 'lnkd.in', 'goo.gl'], true);
    }

    private function flattenScalarValues(mixed $value): array
    {
        if (is_scalar($value)) {
            $value = trim((string) $value);

            return $value === '' ? [] : [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $values = [];

        foreach ($value as $item) {
            $values = array_merge($values, $this->flattenScalarValues($item));
        }

        return $values;
    }

    private function qualitySignals(?string $companyName, ?string $description, ?string $sourceUrl, string $query, array $campaignContext): array
    {
        $resultText = mb_strtolower(implode(' ', array_filter([$companyName, $description, $sourceUrl])));
        $queryText = mb_strtolower($query);
        $contextText = trim($resultText . ' ' . $queryText);
        $hasDisqualifyingContext = preg_match('/\b(experience with|is a plus|job|jobs|career|careers|hiring|salary|responsibilities|qualifications|login|sign in)\b|وظيفة|وظائف|توظيف|راتب|دوام|خبرة|للعمل|شاغر/u', $resultText) === 1;
        $hasPromotionalSoftwareContext = preg_match('/\b(want to|start today|try our|our crm|with a crm system|crm system, everything|manage your business effortlessly|boost your business|grow your business with|marketingagency|marketing agency|website design|web design|software for your business|build your online|your vision\. our design|anyone need website|need website for their business|seo services|it solutions|erp solutions provider|crm solutions provider|official distributor)\b/i', $resultText) === 1;
        $hasPublicCustomerCtaContext = preg_match('/\b(book now|book appointment|bookings? essential|bookings? via|via our website|tickets?|event|show|performance|register now|registration open|order online|delivery available|membership offer|rooms available|packages|offers?)\b/i', $resultText) === 1;
        $hasExplicitSoftwareRequest = ! $hasDisqualifyingContext
            && ! $hasPromotionalSoftwareContext
            && preg_match('/\b(looking for|need|needs|needed|who can build|quote|quotation|proposal|developer|programmer|software company|web developer|app developer|website|mobile app|web app|booking system|crm|erp|inventory system)\b|محتاج|محتاجة|محتاجين|احتاج|أحتاج|بدي|بدنا|نحتاج|مين\s+(?:بعمل|بيعمل|يعمل)|شركة\s+برمجة|مبرمج|مطور|تصميم\s+موقع|تطبيق|متجر\s+(?:الكتروني|إلكتروني)|نظام\s+(?:حجز|مخزون|محاسبة|ادارة|إدارة)/iu', $resultText) === 1;
        $score = 0;
        $signals = [];

        if (! $this->looksLikeBadCompanyName((string) $companyName)) {
            $score += 3;
            $signals[] = 'clear_company_name';
        }

        if ($sourceUrl && preg_match('/\/(company|showcase|pages?|business|profile)\b|facebook\.com\/[^\/?#]+$|x\.com\/[^\/?#]+$|twitter\.com\/[^\/?#]+$/i', $sourceUrl)) {
            $score += 3;
            $signals[] = 'business_profile_url';
        }

        foreach (array_filter($campaignContext) as $value) {
            if (str_contains($contextText, mb_strtolower($value))) {
                $score += 1;
            }
        }

        if ($hasExplicitSoftwareRequest) {
            $score += 10;
            $signals[] = 'intent:software_request';
        }

        foreach ([
            'book now',
            'book appointment',
            'order online',
            'delivery available',
            'new branch',
            'new project',
            'registration open',
            'reservation',
            'dm to order',
            'call to book',
            'whatsapp ordering',
            'membership offer',
            'tracking',
            'حجز موعد',
            'احجز موعد',
            'اطلب اونلاين',
            'اطلب أونلاين',
            'طلب اونلاين',
            'طلب أونلاين',
            'واتساب طلبات',
            'فرع جديد',
            'افتتاح فرع',
            'التسجيل مفتوح',
        ] as $keyword) {
            if (str_contains($resultText, $keyword)) {
                $score += 3;
                $signals[] = 'intent:' . $keyword;
            }
        }

        foreach (['crm', 'erp', 'automation', 'inventory', 'fleet'] as $keyword) {
            if (! $hasDisqualifyingContext && ! $hasPromotionalSoftwareContext && ! $hasPublicCustomerCtaContext && str_contains($resultText, $keyword) && preg_match('/\b(need|needs|looking for|request|requires?|manual|broken|missing|pain|problem|struggling|replace|integrate|tracking|operations?)\b|يحتاج|نحتاج|نبحث عن|مطلوب نظام|يدوي|مشكلة|نظام مفقود|تتبع|عمليات/u', $resultText)) {
                $score += 2;
                $signals[] = 'intent:' . $keyword;
            }
        }

        foreach (['official', 'business', 'services', 'solutions', 'clinic', 'restaurant', 'agency', 'company', 'center', 'store'] as $keyword) {
            if (str_contains($resultText, $keyword)) {
                $score += 1;
            }
        }

        foreach ([
            'job',
            'jobs',
            'career',
            'careers',
            'hiring',
            'salary',
            'course',
            'training job',
            'login',
            'sign in',
            'marketplace listing',
            'experience with',
            'is a plus',
            'join our team',
            'job description',
            'responsibilities',
            'qualifications',
            'مطلوب موظف',
            'وظيفة',
            'وظائف',
            'توظيف',
            'خبرة في',
            'يفضل',
        ] as $keyword) {
            if (str_contains($resultText, $keyword)) {
                $score -= 8;
                $signals[] = 'negative:' . $keyword;
            }
        }

        if ($hasDisqualifyingContext) {
            $score -= 8;
            $signals[] = 'negative:disqualifying_context';
        }

        if (preg_match('/\b(we offer|our platform|software suite|crm and sales|inventory, finance|ecommerce platform|erp software|crm software)\b/i', $resultText)) {
            $score -= 6;
            $signals[] = 'negative:software_vendor_context';
        }

        if ($hasPromotionalSoftwareContext) {
            $score -= 10;
            $signals[] = 'negative:promotional_software_offer';
        }

        if ($hasPublicCustomerCtaContext) {
            $score -= 10;
            $signals[] = 'negative:public_customer_cta';
        }

        return [
            'score' => max(0, min(20, $score)),
            'signals' => array_values(array_unique($signals)),
        ];
    }

    private function looksLikeBadCompanyName(?string $companyName): bool
    {
        $companyName = trim((string) $companyName);

        if ($companyName === '' || mb_strlen($companyName) < 2 || mb_strlen($companyName) > 90) {
            return true;
        }

        if (preg_match('/[.!?]{1}|,|:|;|\b(experience with|is a plus|from crm|job|hiring|login|sign in)\b/i', $companyName)) {
            return true;
        }

        return str_word_count($companyName) > 12;
    }

    private function hasPositiveIntentSignal(array $signals): bool
    {
        foreach ($signals as $signal) {
            if (is_string($signal) && str_starts_with($signal, 'intent:')) {
                return true;
            }
        }

        return false;
    }

    private function hasFatalNegativeSignal(array $signals): bool
    {
        $fatalSignals = [
            'negative:software_vendor_context',
            'negative:promotional_software_offer',
            'negative:public_customer_cta',
            'negative:disqualifying_context',
            'negative:experience with',
            'negative:is a plus',
            'negative:job',
            'negative:jobs',
            'negative:career',
            'negative:careers',
            'negative:hiring',
            'negative:login',
            'negative:sign in',
            'negative:marketplace listing',
        ];

        foreach ($signals as $signal) {
            if (is_string($signal) && in_array($signal, $fatalSignals, true)) {
                return true;
            }
        }

        return false;
    }

    private function value(array $item, string|array|null $paths): ?string
    {
        foreach ((array) $paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $value = Arr::get($item, $path);

            if (is_scalar($value)) {
                $value = trim((string) $value);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function cleanCompanyName(?string $value, array $config): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach ($config['company_name_suffixes'] ?? [] as $suffix) {
            if (is_string($suffix) && str_ends_with($value, $suffix)) {
                $value = substr($value, 0, -strlen($suffix));
            }
        }

        return trim($value, " \t\n\r\0\x0B-|/");
    }

    private function stringMap(array $values): array
    {
        return array_filter($values, fn ($value) => $value !== null && $value !== '');
    }

    private function isList(array $items): bool
    {
        return $items === [] || array_keys($items) === range(0, count($items) - 1);
    }
}
