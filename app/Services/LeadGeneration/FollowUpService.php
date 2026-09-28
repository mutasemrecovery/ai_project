<?php

namespace App\Services\LeadGeneration;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Outreach;

class FollowUpService
{
    public function scheduleDefaults(Lead $lead, ?Outreach $outreach = null): array
    {
        $delays = [2, 5, 10];
        $followUps = [];

        foreach ($delays as $index => $days) {
            $followUps[] = $lead->followUps()->create([
                'outreach_id' => $outreach?->id,
                'sequence_number' => $index + 1,
                'delay_days' => $days,
                'scheduled_at' => now()->addDays($days),
                'status' => FollowUp::STATUS_PENDING_APPROVAL,
            ]);
        }

        $lead->update(['next_follow_up_at' => now()->addDays($delays[0])]);

        return $followUps;
    }
}
