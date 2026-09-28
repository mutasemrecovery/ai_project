<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverLeadsJob;
use App\Models\Campaign;
use App\Models\LeadSource;
use App\Services\LeadGeneration\Discovery\LeadDiscoveryService;
use App\Services\LeadGeneration\Discovery\SearchQueryBuilder;
use Illuminate\Console\Command;

class DiscoverLeadsCommand extends Command
{
    protected $signature = 'leads:discover {--campaign=} {--limit=50} {--source=} {--dry-run} {--debug} {--sync}';
    protected $description = 'Discover raw leads from enabled lead sources and campaigns.';

    public function handle(LeadDiscoveryService $discovery, SearchQueryBuilder $queryBuilder): int
    {
        $campaignId = $this->option('campaign') ? (int) $this->option('campaign') : null;
        $limit = (int) $this->option('limit');
        $source = $this->option('source') ?: null;
        $debug = (bool) $this->option('debug');

        if ($this->option('dry-run')) {
            $items = $discovery->discover($campaignId, $source, $limit, true);
            $this->table(['company_name', 'source_url', 'source', 'query'], $items->map(fn ($item) => [
                $item['company_name'] ?? null,
                $item['source_url'] ?? null,
                $item['source'] ?? null,
                $item['raw_data']['query'] ?? null,
            ]));

            if ($debug || $items->isEmpty()) {
                $this->diagnostics($campaignId, $source, $queryBuilder);
            }

            return self::SUCCESS;
        }

        if ($this->option('sync') || env('LEADS_DISCOVERY_RUN_INLINE', false)) {
            $items = $discovery->discover($campaignId, $source, $limit);
            $this->info("Discovered {$items->count()} raw leads.");

            return self::SUCCESS;
        }

        DiscoverLeadsJob::dispatch($campaignId, $source, $limit);
        $this->info('Lead discovery queued.');

        return self::SUCCESS;
    }

    private function diagnostics(?int $campaignId, ?string $source, SearchQueryBuilder $queryBuilder): void
    {
        $campaigns = Campaign::query()
            ->where('enabled', true)
            ->when($campaignId, fn ($query, int $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get();

        $sources = LeadSource::query()
            ->where('enabled', true)
            ->when($source, function ($query, string $source) {
                $query->where(function ($query) use ($source) {
                    $query->where('type', $source)
                        ->orWhere('provider', $source)
                        ->orWhere('name', $source);
                });
            })
            ->orderBy('name')
            ->get();

        $searchConfig = config('lead_generation.search', []);
        $firstSource = $sources->first();
        $firstCampaign = $campaigns->first();
        $sourceConfig = array_replace_recursive($searchConfig, $firstSource?->configuration ?: []);
        $sampleQueries = $firstCampaign ? array_slice($queryBuilder->buildForCampaign($firstCampaign, $sourceConfig), 0, 3) : [];

        $this->newLine();
        $this->warn('Discovery diagnostics');
        $this->table(['check', 'value'], [
            ['enabled_campaigns', (string) $campaigns->count()],
            ['matching_enabled_sources', (string) $sources->count()],
            ['first_campaign', $firstCampaign?->name ?: 'none'],
            ['first_source', $firstSource?->name ?: 'none'],
            ['search_endpoint', (string) ($searchConfig['endpoint'] ?: 'missing')],
            ['search_api_key', ! empty($searchConfig['api_key']) ? 'set' : 'missing'],
            ['api_key_query_parameter', (string) ($searchConfig['api_key_query_parameter'] ?: 'missing')],
            ['results_path', (string) ($searchConfig['results_path'] ?: 'auto')],
        ]);

        if ($sampleQueries !== []) {
            $this->line('Sample queries:');

            foreach ($sampleQueries as $query) {
                $this->line('- ' . $query);
            }
        }
    }
}
