<?php

namespace App\Console;

use App\Console\Commands\Dispatch;
use App\Console\Commands\HandleTransactions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Stringable;
use Spatie\SlackAlerts\Facades\SlackAlert;

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
        // Clear transactions
        $schedule->command(HandleTransactions::class, ['abandoned', '--action clear', '--source paystack', '--perpage 100', '--persistent'])
            // ->twiceDaily(1, 13)
            // ->everyMinute()
            ->hourly()
            ->withoutOverlapping()
            ->onSuccess(function (Stringable $output) {
                SlackAlert::message($output);
            })
            ->onFailure(function (Stringable $output) {
                SlackAlert::message($output);
            });

        $schedule->command(Dispatch::class)
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->onSuccess(function (Stringable $output) {
                    SlackAlert::message($output);
                })
                ->onFailure(function (Stringable $output) {
                    SlackAlert::message($output);
                });
        // $schedule->command('inspire')->hourly();
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
