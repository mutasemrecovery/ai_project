<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverLeadsJob;
use App\Services\LeadGeneration\Discovery\LeadDiscoveryService;
use Illuminate\Console\Command;

class DiscoverLeadsCommand extends Command
{
    protected $signature = 'leads:discover {--campaign=} {--limit=50} {--source=} {--dry-run}';
    protected $description = 'Discover raw leads from enabled lead sources and campaigns.';

    public function handle(LeadDiscoveryService $discovery): int
    {
        $campaignId = $this->option('campaign') ? (int) $this->option('campaign') : null;
        $limit = (int) $this->option('limit');
        $source = $this->option('source') ?: null;

        if ($this->option('dry-run')) {
            $items = $discovery->discover($campaignId, $source, $limit, true);
            $this->table(['company_name', 'website', 'source'], $items->map(fn ($item) => [
                $item['company_name'] ?? null,
                $item['website'] ?? null,
                $item['source'] ?? null,
            ]));

            return self::SUCCESS;
        }

        DiscoverLeadsJob::dispatch($campaignId, $source, $limit);
        $this->info('Lead discovery queued.');

        return self::SUCCESS;
    }
}
