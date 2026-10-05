<?php

namespace App\Services\LeadGeneration\Ai;

class LeadAiSchemas
{
    public static function companyAnalysis(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'is_potential_client',
                'confidence',
                'industry',
                'detected_needs',
                'recommended_services',
                'signals',
                'project_type',
                'estimated_project_size',
                'reasoning',
                'recommended_priority',
            ],
            'properties' => [
                'is_potential_client' => ['type' => 'boolean'],
                'confidence' => ['type' => 'number'],
                'industry' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                'detected_needs' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'recommended_services' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'signals' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['signal', 'evidence', 'confidence'],
                        'properties' => [
                            'signal' => ['type' => 'string'],
                            'evidence' => ['type' => ['string', 'null']],
                            'confidence' => ['type' => 'number'],
                        ],
                    ],
                ],
                'project_type' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                'estimated_project_size' => [
                    'type' => 'string',
                    'enum' => ['small', 'medium', 'large', 'unknown'],
                ],
                'reasoning' => ['type' => 'string'],
                'recommended_priority' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high', 'critical'],
                ],
            ],
        ];
    }

    public static function outreachMessage(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['subject', 'body', 'channel', 'message_type', 'evidence_used'],
            'properties' => [
                'subject' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                'body' => ['type' => 'string'],
                'channel' => [
                    'type' => 'string',
                    'enum' => ['email', 'whatsapp', 'linkedin'],
                ],
                'message_type' => [
                    'type' => 'string',
                    'enum' => ['short_email', 'professional_email', 'business_whatsapp', 'linkedin_style'],
                ],
                'evidence_used' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public static function replyClassification(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['category', 'confidence', 'reasoning', 'do_not_contact'],
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => [
                        'interested',
                        'not_interested',
                        'needs_information',
                        'meeting_request',
                        'price_request',
                        'later',
                        'wrong_contact',
                        'unsubscribe',
                        'unknown',
                    ],
                ],
                'confidence' => ['type' => 'number'],
                'reasoning' => ['type' => 'string'],
                'do_not_contact' => ['type' => 'boolean'],
            ],
        ];
    }
}
