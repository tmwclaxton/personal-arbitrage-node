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
    public function handle(): void
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
