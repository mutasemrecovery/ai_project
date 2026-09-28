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
            '(site:linkedin.com/company OR site:linkedin.com/showcase) Restaurant Amman Jordan Online Ordering -jobs -careers',
            '(site:linkedin.com/company OR site:linkedin.com/showcase) Restaurant Amman Jordan WhatsApp ordering -jobs -careers',
        ], $queries);
    }
}
