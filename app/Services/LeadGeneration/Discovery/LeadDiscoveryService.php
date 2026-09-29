<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\LeadSource;
use App\Repositories\LeadGeneration\CampaignRepository;
use App\Repositories\LeadGeneration\LeadSourceRepository;
use App\Services\LeadGeneration\LeadIntakeService;
use Illuminate\Support\Collection;

class LeadDiscoveryService
{
    public function __construct(
        private CampaignRepository $campaigns,
        private LeadSourceRepository $sources,
        private LeadIntakeService $intake,
        private ManualLeadSourceConnector $manualConnector,
        private ConfiguredSearchApiConnector $searchApiConnector
    ) {
    }

    public function discover(?int $campaignId = null, ?string $sourceType = null, int $limit = 50, bool $dryRun = false): Collection
    {
        $created = collect();
        $this->searchApiConnector->beginSearchRun((int) config('lead_generation.search.requests_per_run', 1));

        foreach ($this->campaigns->enabled($campaignId) as $campaign) {
            foreach ($this->sources->enabled($sourceType) as $source) {
                foreach ($this->connectorFor($source)->discover($source, $campaign, ['limit' => $limit, 'dry_run' => $dryRun]) as $candidate) {
                    $attributes = array_merge($candidate, [
                        'lead_source_id' => $source->id,
                        'campaign_id' => $campaign->id,
                        'source' => $candidate['source'] ?? $source->name,
                    ]);

                    $created->push($dryRun ? $attributes : $this->intake->storeRawLead($attributes));

                    if ($created->count() >= $limit) {
                        return $created;
                    }
                }

                if (! $dryRun) {
                    $this->sources->touchLastRun($source);
                }
            }
        }

        return $created;
    }

    private function connectorFor(LeadSource $source): LeadSourceConnectorInterface
    {
        return match ($source->type) {
            'search_api', 'business_directory_api', 'job_board_api', 'startup_directory_api' => $this->searchApiConnector,
            default => $this->manualConnector,
        };
    }
}
