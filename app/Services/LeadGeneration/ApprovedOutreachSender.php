<?php

namespace App\Services\LeadGeneration;

use App\Mail\LeadOutreachMail;
use App\Models\Lead;
use App\Models\Outreach;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ApprovedOutreachSender
{
    public function __construct(private FollowUpService $followUps)
    {
    }

    public function send(Outreach $outreach): Outreach
    {
        if ($outreach->status !== Outreach::STATUS_APPROVED) {
            throw new RuntimeException('Only approved outreach can be sent.');
        }

        if ($outreach->lead->do_not_contact) {
            throw new RuntimeException('This lead is marked as do-not-contact.');
        }

        $recipient = $outreach->contact?->email ?: $outreach->lead->email;

        if (! $recipient) {
            throw new RuntimeException('No email recipient is available for this outreach.');
        }

        try {
            Mail::to($recipient)->send(new LeadOutreachMail($outreach));

            $outreach->update([
                'status' => Outreach::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ]);

            $outreach->lead->update([
                'status' => Lead::STATUS_CONTACTED,
                'last_contacted_at' => now(),
            ]);

            if ($outreach->lead->followUps()->count() === 0) {
                $this->followUps->scheduleDefaults($outreach->lead, $outreach);
            }
        } catch (Throwable $exception) {
            $outreach->update([
                'status' => Outreach::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $outreach->refresh();
    }
}
