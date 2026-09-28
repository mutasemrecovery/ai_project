<?php

namespace App\Repositories\LeadGeneration;

use App\Models\LeadSource;
use Illuminate\Database\Eloquent\Collection;

class LeadSourceRepository
{
    public function enabled(?string $type = null, int $limit = 50): Collection
    {
        return LeadSource::query()
            ->where('enabled', true)
            ->when($type, fn ($query, string $sourceType) => $query->where('type', $sourceType))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function findEnabled(int $id): ?LeadSource
    {
        return LeadSource::query()
            ->where('enabled', true)
            ->find($id);
    }

    public function touchLastRun(LeadSource $leadSource): LeadSource
    {
        $leadSource->forceFill(['last_run_at' => now()])->save();

        return $leadSource->refresh();
    }
}
