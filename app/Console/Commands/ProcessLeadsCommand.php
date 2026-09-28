<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRawLeadJob;
use Illuminate\Console\Command;

class ProcessLeadsCommand extends Command
{
    protected $signature = 'leads:process {--limit=50} {--dry-run}';
    protected $description = 'Process raw leads into normalized leads.';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run: no raw leads were processed.');

            return self::SUCCESS;
        }

        ProcessRawLeadJob::dispatch(null, (int) $this->option('limit'));
        $this->info('Raw lead processing queued.');

        return self::SUCCESS;
    }
}
