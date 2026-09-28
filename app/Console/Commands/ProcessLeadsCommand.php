<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRawLeadJob;
use Illuminate\Console\Command;

class ProcessLeadsCommand extends Command
{
    protected $signature = 'leads:process {--limit=50} {--dry-run} {--sync}';
    protected $description = 'Process raw leads into normalized leads.';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run: no raw leads were processed.');

            return self::SUCCESS;
        }

        if ($this->option('sync') || env('LEADS_PROCESSING_RUN_INLINE', false)) {
            app()->call([new ProcessRawLeadJob(null, (int) $this->option('limit')), 'handle']);
            $this->info('Raw lead processing completed.');

            return self::SUCCESS;
        }

        ProcessRawLeadJob::dispatch(null, (int) $this->option('limit'));
        $this->info('Raw lead processing queued.');

        return self::SUCCESS;
    }
}
