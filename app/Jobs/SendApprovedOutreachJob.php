<?php

namespace App\Jobs;

use App\Models\Outreach;
use App\Services\LeadGeneration\ApprovedOutreachSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendApprovedOutreachJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $outreachId)
    {
    }

    public function handle(ApprovedOutreachSender $sender): void
    {
        $sender->send(Outreach::findOrFail($this->outreachId));
    }
}
