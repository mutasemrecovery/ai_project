<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;

class LeadScoringService
{
    public function score(Lead $lead, array $analysis = []): array
    {
        $signals = $analysis['signals'] ?? $lead->business_signals ?? [];
        $needs = $analysis['detected_needs'] ?? [];
        $services = $analysis['recommended_services'] ?? $lead->detected_services ?? [];

        $intent = $this->intentScore($signals, $needs, $analysis);
        $businessFit = $this->businessFitScore($lead, $services, $analysis);
        $projectValue = $this->projectValueScore($lead, $analysis);
        $digitalGap = $this->digitalGapScore($signals, $lead);

        $total = $intent + $businessFit + $projectValue + $digitalGap;

        return [
            'intent_score' => $intent,
            'business_fit_score' => $businessFit,
            'project_value_score' => $projectValue,
            'digital_gap_score' => $digitalGap,
            'lead_score' => min(100, $total),
            'priority' => $this->priority($total, $analysis['recommended_priority'] ?? null),
        ];
    }

    private function intentScore(array $signals, array $needs, array $analysis): int
    {
        $score = count($needs) > 0 ? 10 : 0;
        $text = $this->flatten($signals) . ' ' . implode(' ', $needs) . ' ' . ($analysis['reasoning'] ?? '');

        foreach (['hiring', 'looking for', 'need', 'automation', 'mvp', 'booking', 'ordering', 'software'] as $keyword) {
            if (str_contains(mb_strtolower($text), $keyword)) {
                $score += 4;
            }
        }

        $confidence = (float) ($analysis['confidence'] ?? 0);

        return min(30, $score + (int) round($confidence * 6));
    }

    private function businessFitScore(Lead $lead, array $services, array $analysis): int
    {
        $targetCountries = ['jordan', 'saudi arabia', 'uae', 'qatar', 'kuwait', 'bahrain', 'oman'];
        $score = in_array(mb_strtolower((string) $lead->country), $targetCountries, true) ? 8 : 0;
        $score += $lead->industry || ($analysis['industry'] ?? null) ? 5 : 0;
        $score += min(8, count($services) * 2);
        $score += $lead->company_name ? 2 : 0;
        $score += $lead->website || $lead->phone || $lead->email ? 2 : 0;

        return min(25, $score);
    }

    private function projectValueScore(Lead $lead, array $analysis): int
    {
        $score = 6;
        $size = mb_strtolower((string) ($analysis['estimated_project_size'] ?? $lead->company_size));

        $score += match ($size) {
            'large' => 10,
            'medium' => 7,
            'small' => 4,
            default => 2,
        };

        if (str_contains(mb_strtolower((string) $analysis['project_type']), 'custom')) {
            $score += 4;
        }

        return min(20, $score);
    }

    private function digitalGapScore(array $signals, Lead $lead): int
    {
        $text = mb_strtolower($this->flatten($signals) . ' ' . $lead->detected_need);
        $score = $lead->website ? 3 : 10;

        foreach (['no website', 'outdated', 'broken', 'manual', 'whatsapp-only', 'no online', 'no mobile', 'weak digital'] as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 4;
            }
        }

        return min(25, $score);
    }

    private function priority(int $score, ?string $aiPriority): string
    {
        if ($score >= 90 || $aiPriority === Lead::PRIORITY_CRITICAL) {
            return Lead::PRIORITY_CRITICAL;
        }

        if ($score >= 75 || $aiPriority === Lead::PRIORITY_HIGH) {
            return Lead::PRIORITY_HIGH;
        }

        if ($score >= 50 || $aiPriority === Lead::PRIORITY_MEDIUM) {
            return Lead::PRIORITY_MEDIUM;
        }

        return Lead::PRIORITY_LOW;
    }

    private function flatten(array $items): string
    {
        return collect($items)
            ->map(fn ($item) => is_array($item) ? implode(' ', array_filter($item, 'is_scalar')) : $item)
            ->filter(fn ($item) => is_scalar($item))
            ->implode(' ');
    }
}
