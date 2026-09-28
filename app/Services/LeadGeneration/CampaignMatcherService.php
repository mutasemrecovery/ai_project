<?php

namespace App\Services\LeadGeneration;

use App\Models\Campaign;

class CampaignMatcherService
{
    public function matches(Campaign $campaign, array $leadData): bool
    {
        if (! $campaign->enabled) {
            return false;
        }

        if (! $this->matchesAny($campaign->countries, $leadData['country'] ?? null)) {
            return false;
        }

        if (! $this->matchesAny($campaign->cities, $leadData['city'] ?? null)) {
            return false;
        }

        if (! $this->matchesAny($campaign->industries, $leadData['industry'] ?? null)) {
            return false;
        }

        if (($leadData['lead_score'] ?? null) !== null && (int) $leadData['lead_score'] < (int) $campaign->minimum_score) {
            return false;
        }

        $haystack = $this->searchableText($leadData);

        if (! $this->containsAny($haystack, $campaign->keywords)) {
            return false;
        }

        if ($this->containsAny($haystack, $campaign->negative_keywords, false)) {
            return false;
        }

        return true;
    }

    private function matchesAny(?array $allowedValues, ?string $candidate): bool
    {
        if (empty($allowedValues)) {
            return true;
        }

        if ($candidate === null || $candidate === '') {
            return false;
        }

        return in_array(mb_strtolower($candidate), array_map('mb_strtolower', $allowedValues), true);
    }

    private function containsAny(string $haystack, ?array $needles, bool $emptyMatches = true): bool
    {
        if (empty($needles)) {
            return $emptyMatches;
        }

        foreach ($needles as $needle) {
            if (is_string($needle) && $needle !== '' && str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function searchableText(array $leadData): string
    {
        $values = [
            $leadData['company_name'] ?? null,
            $leadData['industry'] ?? null,
            $leadData['description'] ?? null,
            $leadData['detected_need'] ?? null,
        ];

        foreach (['detected_services', 'business_signals'] as $field) {
            if (! empty($leadData[$field]) && is_array($leadData[$field])) {
                $values = array_merge($values, $leadData[$field]);
            }
        }

        return mb_strtolower(implode(' ', array_filter($values, 'is_string')));
    }
}
