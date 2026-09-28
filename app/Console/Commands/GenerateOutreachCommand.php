<?php

namespace App\Console\Commands;

use App\Jobs\GenerateOutreachJob;
use App\Models\Lead;
use Illuminate\Console\Command;

class GenerateOutreachCommand extends Command
{
    protected $signature = 'leads:generate-outreach {--limit=25} {--type=professional_email} {--dry-run}';
    protected $description = 'Generate pending-approval outreach for qualified leads.';

    public function handle(): int
    {
        $leads = Lead::whereIn('status', [Lead::STATUS_QUALIFIED, Lead::STATUS_APPROVED])
            ->where('do_not_contact', false)
            ->doesntHave('outreaches')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($this->option('dry-run')) {
            $this->table(['id', 'company'], $leads->map(fn (Lead $lead) => [$lead->id, $lead->company_name]));

            return self::SUCCESS;
        }

        $leads->each(fn (Lead $lead) => GenerateOutreachJob::dispatch($lead->id, (string) $this->option('type')));
        $this->info("Queued {$leads->count()} outreach generation jobs.");

        return self::SUCCESS;
    }
}
