<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadGeneration\LeadAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $leadId)
    {
    }

    public function handle(LeadAnalysisService $analysis): void
    {
        $lead = Lead::findOrFail($this->leadId);
        $analysis->analyze($lead);
    }
}
