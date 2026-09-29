<?php

namespace Tests\Unit\LeadGeneration;

use App\Services\LeadGeneration\Discovery\ConfiguredSearchApiConnector;
use App\Services\LeadGeneration\Discovery\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConfiguredSearchApiConnectorTest extends TestCase
{
    public function test_it_rejects_job_like_social_search_snippets(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $quality = $this->invoke($connector, 'qualitySignals', [
            'Experience with SEO, Google Suite, Google Ads, CRM systems, or content marketing is a plus. From CRM and Sales to Inventory, Finance, and eCommerce',
            'Experience with SEO, Google Suite, Google Ads, CRM systems, or content marketing is a plus. From CRM and Sales to Inventory, Finance, and eCommerce',
            'https://www.facebook.com/smartwayjo?locale=zh_CN',
            'site:facebook.com Retail Jordan CRM -jobs -careers -login',
            ['country' => 'Jordan', 'city' => null, 'industry' => 'Retail'],
        ]);

        $this->assertSame(0, $quality['score']);
        $this->assertContains('negative:experience with', $quality['signals']);
        $this->assertContains('negative:is a plus', $quality['signals']);
        $this->assertNotContains('intent:crm', $quality['signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [[
            'source_url' => 'https://www.facebook.com/smartwayjo?locale=zh_CN',
            'company_name' => 'Experience with SEO, Google Suite, Google Ads, CRM systems, or content marketing is a plus. From CRM and Sales to Inventory, Finance, and eCommerce',
            'raw_data' => [
                'candidate_quality_score' => $quality['score'],
                'candidate_quality_signals' => $quality['signals'],
            ],
        ], [
            'allowed_url_contains' => ['facebook.com/'],
            'min_quality_score' => 10,
            'requires_positive_intent' => true,
        ]]);

        $this->assertFalse($allowed);
    }

    public function test_it_accepts_social_results_with_real_operational_intent(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $quality = $this->invoke($connector, 'qualitySignals', [
            'Sample Dental Clinic',
            'Book appointment on WhatsApp. New branch now open in Amman.',
            'https://www.facebook.com/sampleclinic',
            'site:facebook.com Dental Clinic Amman Jordan book appointment -jobs -careers -login',
            ['country' => 'Jordan', 'city' => 'Amman', 'industry' => 'Dental Clinic'],
        ]);

        $this->assertGreaterThanOrEqual(10, $quality['score']);
        $this->assertContains('intent:book appointment', $quality['signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [[
            'source_url' => 'https://www.facebook.com/sampleclinic',
            'company_name' => 'Sample Dental Clinic',
            'raw_data' => [
                'candidate_quality_score' => $quality['score'],
                'candidate_quality_signals' => $quality['signals'],
            ],
        ], [
            'allowed_url_contains' => ['facebook.com/'],
            'min_quality_score' => 10,
            'requires_positive_intent' => true,
        ]]);

        $this->assertTrue($allowed);
    }

    private function invoke(object $object, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
