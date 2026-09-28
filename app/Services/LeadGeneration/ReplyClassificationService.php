<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Services\LeadGeneration\Ai\AiService;
use App\Services\LeadGeneration\Ai\LeadAiSchemas;

class ReplyClassificationService
{
    public function __construct(private AiService $ai)
    {
    }

    public function classify(Lead $lead, string $replyBody): array
    {
        $response = $this->ai->generateJson(
            'reply_classification',
            'Classify inbound replies to sales outreach. If the reply asks to unsubscribe or stop messages, set do_not_contact to true.',
            json_encode([
                'lead' => [
                    'company_name' => $lead->company_name,
                    'email' => $lead->email,
                    'status' => $lead->status,
                ],
                'reply_body' => $replyBody,
            ], JSON_PRETTY_PRINT),
            LeadAiSchemas::replyClassification(),
            $lead->id
        );

        $data = $response->data;

        if ($data['do_not_contact'] || $data['category'] === 'unsubscribe') {
            $lead->update([
                'do_not_contact' => true,
                'status' => Lead::STATUS_IGNORED,
            ]);
        } elseif ($data['category'] !== 'unknown') {
            $lead->update(['status' => $this->statusForCategory($data['category'])]);
        }

        return $data;
    }

    private function statusForCategory(string $category): string
    {
        return match ($category) {
            'interested', 'needs_information', 'price_request' => Lead::STATUS_INTERESTED,
            'meeting_request' => Lead::STATUS_MEETING,
            'not_interested', 'wrong_contact' => Lead::STATUS_LOST,
            'later' => Lead::STATUS_REPLIED,
            default => Lead::STATUS_REPLIED,
        };
    }
}
