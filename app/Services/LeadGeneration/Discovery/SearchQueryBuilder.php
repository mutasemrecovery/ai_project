<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;

class SearchQueryBuilder
{
    public function buildForCampaign(Campaign $campaign, array $sourceConfig = []): array
    {
        $countries = $campaign->countries ?: ['Jordan', 'Saudi Arabia', 'UAE', 'Qatar', 'Kuwait', 'Bahrain', 'Oman'];
        $cities = $campaign->cities ?: [null];
        $industries = $campaign->industries ?: ['business'];
        $services = $campaign->services ?: ['software development'];
        $keywords = $campaign->keywords ?: [];

        $queries = [];

        foreach ($countries as $country) {
            foreach ($cities as $city) {
                foreach ($industries as $industry) {
                    $location = trim(implode(' ', array_filter([$city, $country])));
                    $base = trim("{$industry} {$location}");

                    foreach (array_slice($services, 0, 4) as $service) {
                        $queries[] = $this->formatQuery(trim("{$base} {$service}"), $sourceConfig);
                    }

                    foreach (array_slice($keywords, 0, 4) as $keyword) {
                        $queries[] = $this->formatQuery(trim("{$base} {$keyword}"), $sourceConfig);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($queries)));
    }

    private function formatQuery(string $query, array $sourceConfig): string
    {
        return trim(implode(' ', array_filter([
            $this->siteFilterExpression($sourceConfig),
            $sourceConfig['query_prefix'] ?? null,
            $query,
            $sourceConfig['query_suffix'] ?? null,
        ])));
    }

    private function siteFilterExpression(array $sourceConfig): ?string
    {
        $filters = array_values(array_filter($sourceConfig['site_filters'] ?? []));

        if ($filters === []) {
            return null;
        }

        if (count($filters) === 1) {
            return $filters[0];
        }

        return '(' . implode(' OR ', $filters) . ')';
    }
}
