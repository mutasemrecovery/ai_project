<?php

namespace App\Jobs;

use App\Services\LeadGeneration\Discovery\LeadDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiscoverLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $campaignId = null,
        public ?string $sourceType = null,
        public int $limit = 50
    ) {
    }

    public function handle(LeadDiscoveryService $discovery): void
    {
        $discovery->discover($this->campaignId, $this->sourceType, $this->limit);
    }
}
