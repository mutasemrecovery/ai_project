<?php

namespace Tests\Unit\LeadGeneration;

use App\Models\Lead;
use App\Services\LeadGeneration\LeadScoringService;
use PHPUnit\Framework\TestCase;

class LeadScoringServiceTest extends TestCase
{
    public function test_it_calculates_application_owned_score_breakdown(): void
    {
        $lead = new Lead([
            'company_name' => 'Sample Restaurant',
            'country' => 'Jordan',
            'industry' => 'Restaurant',
            'website' => null,
            'detected_need' => 'online ordering',
        ]);

        $score = (new LeadScoringService())->score($lead, [
            'confidence' => 0.9,
            'detected_needs' => ['online ordering', 'WhatsApp ordering'],
            'recommended_services' => ['Laravel development', 'WhatsApp integration'],
            'signals' => [
                ['signal' => 'manual whatsapp-only ordering', 'evidence' => 'https://example.com', 'confidence' => 0.8],
            ],
            'project_type' => 'custom software',
            'estimated_project_size' => 'medium',
            'recommended_priority' => 'high',
        ]);

        $this->assertGreaterThan(0, $score['intent_score']);
        $this->assertGreaterThan(0, $score['business_fit_score']);
        $this->assertGreaterThan(0, $score['project_value_score']);
        $this->assertGreaterThan(0, $score['digital_gap_score']);
        $this->assertLessThanOrEqual(100, $score['lead_score']);
        $this->assertContains($score['priority'], ['low', 'medium', 'high', 'critical']);
    }
}
