<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (env('LEADS_DISCOVERY_SCHEDULE_ENABLED', true)) {
            $schedule->command('leads:discover --limit=' . env('LEADS_DISCOVERY_LIMIT', 50) . $this->inlineOption('LEADS_DISCOVERY_RUN_INLINE'))
                ->cron(env('LEADS_DISCOVERY_CRON', '0 * * * *'));
        }

        if (env('LEADS_PROCESSING_SCHEDULE_ENABLED', true)) {
            $schedule->command('leads:process --limit=' . env('LEADS_PROCESSING_LIMIT', 100) . $this->inlineOption('LEADS_PROCESSING_RUN_INLINE'))
                ->cron(env('LEADS_PROCESSING_CRON', '*/15 * * * *'));
        }

        if (env('LEADS_ANALYSIS_SCHEDULE_ENABLED', true)) {
            $schedule->command('leads:analyze --limit=' . env('LEADS_ANALYSIS_LIMIT', 25) . $this->inlineOption('LEADS_ANALYSIS_RUN_INLINE'))
                ->cron(env('LEADS_ANALYSIS_CRON', '*/30 * * * *'));
        }

        if (env('LEADS_REPORT_SCHEDULE_ENABLED', true)) {
            $schedule->command('leads:report')
                ->cron(env('LEADS_REPORT_CRON', '0 8 * * *'));
        }
    }

    private function inlineOption(string $envKey): string
    {
        return env($envKey, false) ? ' --sync' : '';
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
