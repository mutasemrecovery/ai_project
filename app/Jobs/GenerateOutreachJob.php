<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadGeneration\OutreachGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOutreachJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $leadId, public string $messageType = 'professional_email')
    {
    }

    public function handle(OutreachGenerationService $outreach): void
    {
        $lead = Lead::findOrFail($this->leadId);
        $outreach->generate($lead, $this->messageType);
    }
}
