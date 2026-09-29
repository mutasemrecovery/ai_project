<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\LeadSource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ConfiguredSearchApiConnector implements LeadSourceConnectorInterface
{
    public function __construct(private SearchQueryBuilder $queries)
    {
    }

    public function discover(LeadSource $source, Campaign $campaign, array $options = []): iterable
    {
        $config = $this->sourceConfig($source);
        $endpoint = $config['endpoint'] ?? null;

        if (! $endpoint) {
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
        $items = [];

        foreach (array_slice($queries, 0, $maxQueries) as $query) {
            try {
                $response = $client->request($config['method'] ?? 'GET', $endpoint, [
                    'query' => array_merge($this->queryParameters($config), [
                        $config['query_parameter'] ?? 'q' => $query,
                    ]),
                    'headers' => $this->authorizationHeaders($config),
                ]);
            } catch (GuzzleException $exception) {
                Log::warning('Lead discovery search request failed.', [
                    'source' => $source->name,
                    'provider' => $source->provider,
                    'endpoint' => $endpoint,
                    'query' => $query,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            $decoded = json_decode((string) $response->getBody(), true);

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
                Log::warning('Lead discovery search returned an API error.', [
                    'source' => $source->name,
                    'provider' => $source->provider,
                    'endpoint' => $endpoint,
                    'query' => $query,
                    'error' => $decoded['error'],
                ]);

                continue;
            }

            foreach ($this->results($decoded, $config) as $item) {
                $mapped = $this->mapItem($item, $source, $campaign, $query, $config);

                if (! $this->allowedResult($mapped, $config)) {
                    continue;
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
        $companyName = $this->cleanCompanyName(
            $this->value($item, $map['company_name'] ?? ['company_name', 'name', 'title']),
            $config
        );
        $campaignContext = $this->campaignContext($campaign, $query);
        $quality = $this->qualitySignals($companyName, $description, $sourceUrl, $query, $campaignContext);

        return [
            'source' => $source->name,
            'source_url' => $sourceUrl,
            'company_name' => $companyName,
            'website' => $this->value($item, $map['website'] ?? ['website', 'company_website', 'official_website']),
            'email' => $this->value($item, $map['email'] ?? ['email']),
            'phone' => $this->value($item, $map['phone'] ?? ['phone', 'telephone']),
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
                'public_profile_url' => $sourceUrl,
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

    private function qualitySignals(?string $companyName, ?string $description, ?string $sourceUrl, string $query, array $campaignContext): array
    {
        $resultText = mb_strtolower(implode(' ', array_filter([$companyName, $description, $sourceUrl])));
        $queryText = mb_strtolower($query);
        $contextText = trim($resultText . ' ' . $queryText);
        $hasDisqualifyingContext = preg_match('/\b(experience with|is a plus|job|jobs|career|careers|hiring|salary|responsibilities|qualifications|login|sign in)\b/i', $resultText) === 1;
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
            if (! $hasDisqualifyingContext && str_contains($resultText, $keyword) && preg_match('/\b(need|needs|looking for|request|requires?|manual|manage|tracking|operations?|sales|customers?)\b|يحتاج|نحتاج|نبحث عن|مطلوب نظام|إدارة|ادارة|عملاء|مبيعات|مخزون|تتبع|عمليات/u', $resultText)) {
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

        if (preg_match('/\b(we offer|our platform|software suite|crm and sales|inventory, finance|ecommerce platform|erp software|crm software)\b/i', $resultText)) {
            $score -= 6;
            $signals[] = 'negative:software_vendor_context';
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
