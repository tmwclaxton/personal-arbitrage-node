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
            if ($current_time >= $start_time && $current_time <= $end_time) {
                Log::info('Turning on the auto accept and auto create');
                // turn on the auto accept
                $admin_dashboard->autoAccept = true;
                $admin_dashboard->autoCreate = true;
                $admin_dashboard->save();
            } else {
                Log::info('Turning off the auto accept and auto create');
                // turn off the auto accept
                $admin_dashboard->autoAccept = false;
                $admin_dashboard->autoCreate = false;
                $admin_dashboard->save();
            }
        } else {
            Log::info('Scheduler is off or panic button is on, turning off the auto accept and auto create');
            // turn off the auto accept
            $admin_dashboard->auto_accept = false;
            $admin_dashboard->auto_create = false;
            $admin_dashboard->save();
        }



    }
}
