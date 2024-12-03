<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecuta el comando para procesar publicaciones programadas
        $schedule->command('posts:process-scheduled')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/scheduled-posts.log')); // Registra salida en un log
    }
}
