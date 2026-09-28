<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Models\Outreach;
use App\Services\LeadGeneration\Ai\AiService;
use App\Services\LeadGeneration\Ai\LeadAiSchemas;

class OutreachGenerationService
{
    public function __construct(private AiService $ai)
    {
    }

    public function generate(Lead $lead, string $messageType = 'professional_email'): Outreach
    {
        $response = $this->ai->generateJson(
            'outreach_generation',
            $this->systemPrompt(),
            $this->userPrompt($lead, $messageType),
            LeadAiSchemas::outreachMessage(),
            $lead->id
        );

        $data = $response->data;

        return $lead->outreaches()->create([
            'channel' => $data['channel'],
            'message_type' => $data['message_type'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'personalization_evidence' => $data['evidence_used'],
            'status' => Outreach::STATUS_PENDING_APPROVAL,
        ]);
    }

    private function systemPrompt(): string
    {
        return 'Generate B2B outreach for Recovery, a software development company. Use only evidence provided. Do not claim a site is broken, outdated, or missing unless the evidence says that.';
    }

    private function userPrompt(Lead $lead, string $messageType): string
    {
        return json_encode([
            'requested_message_type' => $messageType,
            'company' => [
                'name' => 'Recovery',
                'services' => [
                    'Laravel Development',
                    'Flutter Development',
                    'Web Development',
                    'Mobile Applications',
                    'API Development',
                    'Admin Dashboards',
                    'AI Integration',
                    'AI Automation',
                    'WhatsApp Integration',
                    'CRM',
                    'ERP',
                    'SaaS MVP Development',
                    'Custom Software',
                ],
            ],
            'lead' => [
                'company_name' => $lead->company_name,
                'contact_name' => $lead->contact_name,
                'contact_role' => $lead->contact_role,
                'industry' => $lead->industry,
                'country' => $lead->country,
                'city' => $lead->city,
                'detected_need' => $lead->detected_need,
                'detected_services' => $lead->detected_services,
                'signals' => $lead->business_signals,
                'ai_summary' => $lead->ai_summary,
                'source_url' => $lead->source_url,
            ],
        ], JSON_PRETTY_PRINT);
    }
}
