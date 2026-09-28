<?php

namespace App\Console\Commands;

use App\Services\LeadGeneration\PipelineReportService;
use Illuminate\Console\Command;

class LeadReportCommand extends Command
{
    protected $signature = 'leads:report {--dry-run}';
    protected $description = 'Show lead generation pipeline summary.';

    public function handle(PipelineReportService $reports): int
    {
        $stats = $reports->dashboardStats();

        $this->table(['Metric', 'Value'], collect($stats)->map(fn ($value, $key) => [$key, $value]));

        return self::SUCCESS;
    }
}
