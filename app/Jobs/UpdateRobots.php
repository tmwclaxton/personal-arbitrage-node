<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Robot;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateRobots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */

    // timeout 180 seconds
    public int $timeout = 180;
    public function handle(): void
    {
        // where accepted and created at is less than 2 days
        $robots = Robot::where('created_at', '>', now()->subDays(2))->get();
        foreach ($robots as $robot) {
            $robosats = new Robosats();
            $response = $robosats->updateRobot($robot);
        }
    }
}
