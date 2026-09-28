<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;

class SearchQueryBuilder
{
    public function buildForCampaign(Campaign $campaign): array
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
                        $queries[] = trim("{$base} {$service}");
                    }

                    foreach (array_slice($keywords, 0, 4) as $keyword) {
                        $queries[] = trim("{$base} {$keyword}");
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($queries)));
    }
}
