<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Robot;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateRobots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:robots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh robots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // where accepted and created at is less than 2 days
        $offers = Offer::where('accepted', true)->where('created_at', '>=', now()->subDays(2))->get();
        $robots = Robot::whereIn('offer_id', $offers->pluck('id'))->get();
        foreach ($robots as $robot) {
            $robosats = new Robosats();
            $response = $robosats->updateRobot($robot);
        }
    }
}
