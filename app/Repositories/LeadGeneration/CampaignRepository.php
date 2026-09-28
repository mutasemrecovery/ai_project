<?php

namespace App\Repositories\LeadGeneration;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository
{
    public function enabled(?int $campaignId = null, int $limit = 50): Collection
    {
        return Campaign::query()
            ->where('enabled', true)
            ->when($campaignId, fn ($query, int $id) => $query->whereKey($id))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function findEnabled(int $id): ?Campaign
    {
        return Campaign::query()
            ->where('enabled', true)
            ->find($id);
    }
}
