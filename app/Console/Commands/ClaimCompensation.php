<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\Models\Robot;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class ClaimCompensation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:claim-compensation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If a robot has earned revenue, claim the compensation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->normalUmbrelCommandCheck()) {
            $robots = Robot::where('earned_rewards', '>', 0)->get();
            if ($robots->isEmpty()) {
                return;
            }
            $adminDashboard = AdminDashboard::all()->first();
            if ($adminDashboard->panicButton || !$adminDashboard->autoReward) {
                return;
            }

            $robosats = new Robosats();
            foreach ($robots as $robot) {
                $robosats->claimCompensation($robot);
            }
        }
    }
}
