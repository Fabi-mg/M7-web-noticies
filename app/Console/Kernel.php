<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CreateNewsCommand;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CreateNewsCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('news:create')->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
