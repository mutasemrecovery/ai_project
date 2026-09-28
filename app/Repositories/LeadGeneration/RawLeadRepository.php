<?php

namespace App\Repositories\LeadGeneration;

use App\Models\Lead;
use App\Models\RawLead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class RawLeadRepository
{
    public function create(array $attributes): RawLead
    {
        return RawLead::create($this->onlyFillable($attributes));
    }

    public function pending(int $limit = 50): Collection
    {
        return RawLead::query()
            ->where('status', RawLead::STATUS_NEW)
            ->oldest('discovered_at')
            ->limit($limit)
            ->get();
    }

    public function markProcessed(RawLead $rawLead, Lead $lead): RawLead
    {
        $rawLead->forceFill([
            'lead_id' => $lead->id,
            'status' => RawLead::STATUS_PROCESSED,
            'processed_at' => now(),
            'failure_reason' => null,
        ])->save();

        return $rawLead->refresh();
    }

    public function markDuplicate(RawLead $rawLead, Lead $lead): RawLead
    {
        $rawLead->forceFill([
            'lead_id' => $lead->id,
            'status' => RawLead::STATUS_DUPLICATE,
            'processed_at' => now(),
            'failure_reason' => null,
        ])->save();

        return $rawLead->refresh();
    }

    public function markRejected(RawLead $rawLead, string $reason): RawLead
    {
        $rawLead->forceFill([
            'status' => RawLead::STATUS_REJECTED,
            'processed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        return $rawLead->refresh();
    }

    public function markFailed(RawLead $rawLead, string $reason): RawLead
    {
        $rawLead->forceFill([
            'status' => RawLead::STATUS_FAILED,
            'processed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        return $rawLead->refresh();
    }

    private function onlyFillable(array $attributes): array
    {
        return Arr::only($attributes, (new RawLead())->getFillable());
    }
}
