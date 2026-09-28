<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadGeneration\ReplyClassificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmailReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $leadId, public string $replyBody)
    {
    }

    public function handle(ReplyClassificationService $classifier): void
    {
        $classifier->classify(Lead::findOrFail($this->leadId), $this->replyBody);
    }
}
