<?php

namespace Tests\Unit\LeadGeneration;

use App\Models\Campaign;
use App\Services\LeadGeneration\Discovery\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class SearchQueryBuilderTest extends TestCase
{
    public function test_it_applies_social_source_site_filters_to_campaign_queries(): void
    {
        $campaign = new Campaign([
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Restaurant'],
            'services' => ['Online Ordering'],
            'keywords' => ['WhatsApp ordering'],
        ]);

        $queries = (new SearchQueryBuilder())->buildForCampaign($campaign, [
            'site_filters' => ['site:linkedin.com/company', 'site:linkedin.com/showcase'],
            'query_suffix' => '-jobs -careers',
        ]);

        $this->assertSame([
            '(site:linkedin.com/company OR site:linkedin.com/showcase) Restaurant Amman Jordan WhatsApp ordering -jobs -careers',
            '(site:linkedin.com/company OR site:linkedin.com/showcase) Restaurant Amman Jordan Online Ordering -jobs -careers',
        ], $queries);
    }

    public function test_it_can_expand_campaign_queries_from_source_templates(): void
    {
        $campaign = new Campaign([
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Clinic'],
            'services' => ['Booking System'],
            'keywords' => ['book appointment'],
        ]);

        $queries = (new SearchQueryBuilder())->buildForCampaign($campaign, [
            'site_filters' => ['site:facebook.com'],
            'query_suffix' => '-jobs',
            'query_templates' => [
                '{industry} {location} {keyword}',
                '{industry} {location} {service}',
                '{industry} {location} يحتاج نظام',
            ],
        ]);

        $this->assertContains('site:facebook.com Clinic Amman Jordan book appointment -jobs', $queries);
        $this->assertContains('site:facebook.com Clinic Amman Jordan يحتاج نظام -jobs', $queries);
        $this->assertContains('site:facebook.com Clinic Amman Jordan Booking System -jobs', $queries);
    }

    public function test_it_does_not_render_empty_keyword_or_service_quotes(): void
    {
        $campaign = new Campaign([
            'countries' => ['Jordan'],
            'cities' => ['Amman'],
            'industries' => ['Retail'],
            'services' => ['Inventory System'],
            'keywords' => ['branches'],
        ]);

        $queries = (new SearchQueryBuilder())->buildForCampaign($campaign, [
            'site_filters' => ['site:x.com'],
            'query_suffix' => '-login',
            'query_templates' => [
                '{industry} {location} "{keyword}"',
                '{industry} {location} "{service}"',
                '{industry} {location} need system',
            ],
        ]);

        $this->assertContains('site:x.com Retail Amman Jordan "branches" -login', $queries);
        $this->assertContains('site:x.com Retail Amman Jordan "Inventory System" -login', $queries);
        $this->assertContains('site:x.com Retail Amman Jordan need system -login', $queries);

        foreach ($queries as $query) {
            $this->assertStringNotContainsString('""', $query);
        }
    }
}
