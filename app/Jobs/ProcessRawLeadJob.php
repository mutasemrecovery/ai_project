<?php

namespace App\Jobs;

use App\Models\RawLead;
use App\Repositories\LeadGeneration\RawLeadRepository;
use App\Services\LeadGeneration\LeadIntakeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRawLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?int $rawLeadId = null, public int $limit = 50)
    {
    }

    public function handle(LeadIntakeService $intake, RawLeadRepository $rawLeads): void
    {
        $items = $this->rawLeadId
            ? RawLead::whereKey($this->rawLeadId)->get()
            : $rawLeads->pending($this->limit);

        foreach ($items as $rawLead) {
            try {
                if (! $rawLead->company_name && ! $rawLead->website && ! $rawLead->email && ! $rawLead->phone) {
                    $rawLeads->markRejected($rawLead, 'Missing company, website, email, and phone.');
                    continue;
                }

                $intake->promoteRawLead($rawLead);
            } catch (Throwable $exception) {
                $rawLeads->markFailed($rawLead, $exception->getMessage());
            }
        }
    }
}
