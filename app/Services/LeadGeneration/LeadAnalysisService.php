<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Services\LeadGeneration\Ai\AiService;
use App\Services\LeadGeneration\Ai\LeadAiSchemas;

class LeadAnalysisService
{
    public function __construct(
        private AiService $ai,
        private LeadScoringService $scoring
    ) {
    }

    public function analyze(Lead $lead): Lead
    {
        $lead->forceFill(['status' => Lead::STATUS_ANALYZING])->save();

        $response = $this->ai->generateJson(
            'company_analysis',
            $this->systemPrompt(),
            $this->userPrompt($lead),
            LeadAiSchemas::companyAnalysis(),
            $lead->id
        );

        $analysis = $response->data;
        $scores = $this->scoring->score($lead, $analysis);
        $minimumScore = (int) ($lead->campaign?->minimum_score ?? 55);
        $status = $analysis['is_potential_client'] && $scores['lead_score'] >= $minimumScore
            ? Lead::STATUS_QUALIFIED
            : Lead::STATUS_IGNORED;

        $lead->update(array_merge($scores, [
            'industry' => $lead->industry ?: $analysis['industry'],
            'detected_need' => implode(', ', $analysis['detected_needs']),
            'detected_services' => $analysis['recommended_services'],
            'business_signals' => $analysis['signals'],
            'ai_summary' => $this->summary($analysis),
            'ai_reasoning' => $analysis['reasoning'],
            'status' => $status,
        ]));

        return $lead->refresh();
    }

    private function systemPrompt(): string
    {
        return 'You qualify public business leads for a software development company. Do not invent facts. Every important signal must include evidence from the provided lead data or source URLs.';
    }

    private function userPrompt(Lead $lead): string
    {
        return json_encode([
            'company_name' => $lead->company_name,
            'website' => $lead->website,
            'country' => $lead->country,
            'city' => $lead->city,
            'industry' => $lead->industry,
            'description' => $lead->description,
            'source' => $lead->source,
            'source_url' => $lead->source_url,
            'source_reference' => $lead->source_reference,
            'campaign' => $lead->campaign?->name,
            'campaign_minimum_score' => $lead->campaign?->minimum_score,
            'target_services' => [
                'Laravel development',
                'PHP development',
                'Flutter mobile applications',
                'API development',
                'Admin dashboards',
                'CRM',
                'ERP',
                'SaaS development',
                'AI integration',
                'AI automation',
                'WhatsApp integrations',
                'Restaurant ordering systems',
                'Booking systems',
                'E-commerce systems',
                'Custom business software',
            ],
            'pre_ai_business_signals' => $lead->business_signals ?: [],
        ], JSON_PRETTY_PRINT);
    }

    private function summary(array $analysis): string
    {
        return trim(sprintf(
            'Potential client: %s. Needs: %s. Services: %s.',
            $analysis['is_potential_client'] ? 'yes' : 'no',
            implode(', ', $analysis['detected_needs']),
            implode(', ', $analysis['recommended_services'])
        ));
    }
}
