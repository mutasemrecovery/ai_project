<?php

namespace Tests\Unit\LeadGeneration;

use App\Models\Campaign;
use App\Models\LeadSource;
use App\Services\LeadGeneration\Discovery\ConfiguredSearchApiConnector;
use App\Services\LeadGeneration\Discovery\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConfiguredSearchApiConnectorTest extends TestCase
{
    public function test_it_treats_empty_search_result_errors_as_no_results(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $this->assertTrue($this->invoke($connector, 'isEmptyResultsErrorMessage', [
            "Google hasn't returned any results for this query.",
        ]));
        $this->assertTrue($this->invoke($connector, 'isEmptyResultsErrorMessage', [
            'Your search did not match any documents.',
        ]));
        $this->assertFalse($this->invoke($connector, 'isEmptyResultsErrorMessage', [
            'You have run out of searches.',
        ]));
    }

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

    public function test_it_rejects_promotional_crm_offers_as_buyer_leads(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $quality = $this->invoke($connector, 'qualitySignals', [
            'GHS Holding',
            'Want to manage your business effortlessly? With a CRM system, everything is organized in one place. Start today! #GHSHolding #GHS #MarketingAgency #website',
            'https://www.facebook.com/ghsholding?locale=ar_AR',
            'site:facebook.com Marketing Agency Jordan CRM -jobs -careers -login',
            ['country' => 'Jordan', 'city' => null, 'industry' => 'Marketing Agency'],
        ]);

        $this->assertContains('negative:promotional_software_offer', $quality['signals']);
        $this->assertNotContains('intent:crm', $quality['signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [[
            'source_url' => 'https://www.facebook.com/ghsholding?locale=ar_AR',
            'company_name' => 'GHS Holding',
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

    public function test_it_rejects_public_booking_promotions_via_existing_website(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $quality = $this->invoke($connector, 'qualitySignals', [
            'Terrapin Theatre',
            'in Amman, Jordan! Book now for @TerrapinTheatre. Bookings essential via our website: bit.ly/2dgnqOO',
            'https://x.com/TerrapinTheatre',
            'site:x.com Automotive Service Amman Jordan book appointment -jobs -careers -login',
            ['country' => 'Jordan', 'city' => 'Amman', 'industry' => 'Automotive Service'],
        ]);

        $this->assertContains('negative:public_customer_cta', $quality['signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [[
            'source_url' => 'https://x.com/TerrapinTheatre',
            'company_name' => 'Terrapin Theatre',
            'raw_data' => [
                'candidate_quality_score' => $quality['score'],
                'candidate_quality_signals' => $quality['signals'],
            ],
        ], [
            'allowed_url_contains' => ['x.com/'],
            'min_quality_score' => 10,
            'requires_positive_intent' => true,
        ]]);

        $this->assertFalse($allowed);
    }

    public function test_it_extracts_contact_methods_from_search_result_text(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $mapped = $this->invoke($connector, 'mapItem', [[
            'url' => 'https://x.com/sampleclinic',
            'title' => 'Sample Clinic',
            'snippet' => 'Need system for booking. Contact info@sampleclinic.jo or WhatsApp +962 79 123 4567. Website: https://sampleclinic.jo/contact',
        ], new LeadSource([
            'name' => 'X Twitter Public Search',
            'provider' => 'twitter',
        ]), new Campaign([
            'name' => 'Clinics Healthcare Automation Buyers',
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Clinic'],
        ]), 'site:x.com Clinic Amman Jordan need booking system', [
            'platform' => 'twitter',
            'source_reference' => 'twitter',
            'allowed_url_contains' => ['x.com/'],
        ]]);

        $this->assertSame('info@sampleclinic.jo', $mapped['email']);
        $this->assertSame('+962791234567', $mapped['phone']);
        $this->assertSame('https://sampleclinic.jo/contact', $mapped['website']);
        $this->assertContains('email', array_column($mapped['contact_methods'], 'type'));
        $this->assertContains('whatsapp', array_column($mapped['contact_methods'], 'type'));
        $this->assertContains('website', array_column($mapped['contact_methods'], 'type'));
    }

    public function test_it_does_not_use_search_artifacts_as_company_website(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $mapped = $this->invoke($connector, 'mapItem', [[
            'url' => 'https://www.facebook.com/groups/ammanbusiness/permalink/123456789',
            'title' => 'Need CRM',
            'snippet' => 'Looking for a CRM implementation partner.',
            'redirect_link' => 'https://www.google.com/url?sa=t&url=CAESgwEB6zswFROf2pJbNPolWZcZJ9Oh8-FTx_5SwEGRNZi2zp0hc9-8v-cuDvLk2QP5bVFQe9Zh4QPEjck7kXH84JB55yG7JNxRr3DrMMPojNlmHoIjalGh2qvIxeYlS6Yy1jhQr4cIFfIjaPCIkxjxqS-MyxR86xT8u3Lx_O1bTPEa-7DPPg&ved=abc',
            'favicon' => 'https://serpapi.com/images/i/very-long-image.webp',
        ], new LeadSource([
            'name' => 'Facebook Public Groups Search',
            'provider' => 'facebook_groups',
        ]), new Campaign([
            'name' => 'Retail Ecommerce Growth Buyers',
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Retail'],
        ]), 'site:facebook.com/groups Amman Jordan "need CRM"', [
            'platform' => 'facebook_groups',
            'source_reference' => 'facebook_groups',
            'allows_group_requests' => true,
        ]]);

        $this->assertNull($mapped['website']);
        $this->assertContains('search_artifact', array_column($mapped['contact_methods'], 'type'));
    }

    public function test_it_accepts_explicit_facebook_group_software_requests(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $mapped = $this->invoke($connector, 'mapItem', [[
            'url' => 'https://www.facebook.com/groups/ammanbusiness/permalink/123456789',
            'title' => 'محتاج شركة برمجة تعمللي متجر الكتروني في عمان',
            'snippet' => 'محتاج شركة برمجة تعمللي متجر الكتروني ونظام طلبات. التواصل واتساب +962 79 123 4567',
        ], new LeadSource([
            'name' => 'Facebook Public Groups Search',
            'provider' => 'facebook_groups',
        ]), new Campaign([
            'name' => 'Retail Ecommerce Growth Buyers',
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Retail'],
        ]), 'site:facebook.com/groups Amman Jordan "محتاج موقع"', [
            'platform' => 'facebook_groups',
            'source_reference' => 'facebook_groups',
            'allows_group_requests' => true,
        ]]);

        $this->assertSame('Facebook Group Request', $mapped['company_name']);
        $this->assertContains('intent:software_request', $mapped['raw_data']['candidate_quality_signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [$mapped, [
            'allowed_url_contains' => ['facebook.com/groups/'],
            'min_quality_score' => 12,
            'requires_positive_intent' => true,
        ]]);

        $this->assertTrue($allowed);
        $this->assertSame('+962791234567', $mapped['phone']);
    }

    public function test_it_rejects_facebook_group_developer_job_posts(): void
    {
        $connector = new ConfiguredSearchApiConnector(new SearchQueryBuilder());

        $quality = $this->invoke($connector, 'qualitySignals', [
            'Facebook Group Request',
            'Hiring web developer full time. Salary based on experience. Send your CV.',
            'https://www.facebook.com/groups/ammanjobs/permalink/123456789',
            'site:facebook.com/groups Amman Jordan "looking for developer"',
            ['country' => 'Jordan', 'city' => 'Amman', 'industry' => 'Retail'],
        ]);

        $this->assertContains('negative:disqualifying_context', $quality['signals']);
        $this->assertNotContains('intent:software_request', $quality['signals']);

        $allowed = $this->invoke($connector, 'allowedResult', [[
            'source_url' => 'https://www.facebook.com/groups/ammanjobs/permalink/123456789',
            'company_name' => 'Facebook Group Request',
            'raw_data' => [
                'candidate_quality_score' => $quality['score'],
                'candidate_quality_signals' => $quality['signals'],
            ],
        ], [
            'allowed_url_contains' => ['facebook.com/groups/'],
            'min_quality_score' => 12,
            'requires_positive_intent' => true,
        ]]);

        $this->assertFalse($allowed);
    }

    private function invoke(object $object, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
