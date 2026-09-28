<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeLeadJob;
use App\Models\Lead;
use Illuminate\Console\Command;

class AnalyzeLeadsCommand extends Command
{
    protected $signature = 'leads:analyze {--limit=25} {--dry-run}';
    protected $description = 'Queue AI analysis for unanalyzed leads.';

    public function handle(): int
    {
        $leads = Lead::whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_ANALYZING])
            ->limit((int) $this->option('limit'))
            ->get();

        if ($this->option('dry-run')) {
            $this->table(['id', 'company'], $leads->map(fn (Lead $lead) => [$lead->id, $lead->company_name]));

            return self::SUCCESS;
        }

        $leads->each(fn (Lead $lead) => AnalyzeLeadJob::dispatch($lead->id));
        $this->info("Queued {$leads->count()} lead analysis jobs.");

        return self::SUCCESS;
    }
}
