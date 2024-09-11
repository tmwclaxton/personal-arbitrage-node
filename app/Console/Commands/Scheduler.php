<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Scheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'At certain times, the system will automatically turn on and off the auto accept and auto create.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // check if the scheduler is on and panic button is off
        $admin_dashboard = \App\Models\AdminDashboard::all()->first();
        if ($admin_dashboard->scheduler && !$admin_dashboard->panic_button) {
            // get the current time
            $current_time = date('H:i:s');


            // get the start and end time
            $start_time = $admin_dashboard->auto_accept_start_time;
            $end_time = $admin_dashboard->auto_accept_end_time;

            // check if the current time is within the start and end time
            $discord_service = new \App\Services\SlackService();
            if ($current_time >= $start_time && $current_time <= $end_time) {
                // turn on the auto accept
                if (!$admin_dashboard->autoAccept) {
                    $discord_service->sendMessage('Scheduler is turning on auto accept');
                    $admin_dashboard->autoAccept = true;
                    $admin_dashboard->save();
                }
                // turn on the auto create
                if (!$admin_dashboard->autoCreate) {
                    $discord_service->sendMessage('Scheduler is turning on auto create');
                    $admin_dashboard->autoCreate = true;
                    $admin_dashboard->save();
                }
            } else {
                // turn off the auto accept
                if ($admin_dashboard->autoAccept) {
                    $discord_service->sendMessage('Scheduler is turning off auto accept');
                    $admin_dashboard->autoAccept = false;
                    $admin_dashboard->save();
                }

                // turn off the auto create
                if ($admin_dashboard->autoCreate) {
                    $discord_service->sendMessage('Scheduler is turning off auto create');
                    $admin_dashboard->autoCreate = false;
                    $admin_dashboard->save();
                }
            }
        }



    }
}
