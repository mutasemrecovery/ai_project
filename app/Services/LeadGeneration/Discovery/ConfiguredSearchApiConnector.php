<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\LeadSource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

class ConfiguredSearchApiConnector implements LeadSourceConnectorInterface
{
    public function __construct(private SearchQueryBuilder $queries)
    {
    }

    public function discover(LeadSource $source, Campaign $campaign, array $options = []): iterable
    {
        $config = $source->configuration ?: [];
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

        $queries = $options['queries'] ?? $this->queries->buildForCampaign($campaign);
        $limit = (int) ($options['limit'] ?? $config['limit'] ?? 25);
        $items = [];

        foreach (array_slice($queries, 0, max(1, $limit)) as $query) {
            try {
                $response = $client->request($config['method'] ?? 'GET', $endpoint, [
                    'query' => array_merge($config['query'] ?? [], [
                        $config['query_parameter'] ?? 'q' => $query,
                    ]),
                    'headers' => $this->authorizationHeaders($config),
                ]);
            } catch (GuzzleException) {
                continue;
            }

            $decoded = json_decode((string) $response->getBody(), true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($this->results($decoded, $config) as $item) {
                $items[] = $this->mapItem($item, $source, $query, $config);

                if (count($items) >= $limit) {
                    return $items;
                }
            }
        }

        return $items;
    }

    private function authorizationHeaders(array $config): array
    {
        $headers = $config['headers'] ?? [];

        if (! empty($config['api_key']) && ! empty($config['api_key_header'])) {
            $headers[$config['api_key_header']] = $config['api_key'];
        }

        return $headers;
    }

    private function results(array $decoded, array $config): array
    {
        $path = $config['results_path'] ?? null;
        $results = $path ? Arr::get($decoded, $path, []) : $decoded;

        return is_array($results) ? $results : [];
    }

    private function mapItem(array $item, LeadSource $source, string $query, array $config): array
    {
        $map = $config['field_map'] ?? [];

        return [
            'source' => $source->name,
            'source_url' => $this->value($item, $map['source_url'] ?? 'url'),
            'company_name' => $this->value($item, $map['company_name'] ?? 'name'),
            'website' => $this->value($item, $map['website'] ?? 'website'),
            'email' => $this->value($item, $map['email'] ?? 'email'),
            'phone' => $this->value($item, $map['phone'] ?? 'phone'),
            'location' => $this->value($item, $map['location'] ?? 'location'),
            'raw_data' => array_merge($item, ['query' => $query]),
        ];
    }

    private function value(array $item, string $path): ?string
    {
        $value = Arr::get($item, $path);

        return is_scalar($value) ? (string) $value : null;
    }
}
