<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\WorkerClasses\Robosats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class UpdateProviders implements ShouldQueue
{
    use Queueable;

    public $timeout = 149;

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
        $robosats = new Robosats();
        $providers = $robosats->providers;
        // foreach provider in providers
        $responses = [];
        foreach ($providers as $provider) {

            $adminDashboard = AdminDashboard::all()->first();
            $headers["Cookie"] = "UMBREL_PROXY_TOKEN=" . $adminDashboard->umbrel_token;
            // $response = Http::get('http://' . env('UMBREL_IP') .':12596/mainnet/' . $provider . '/api/info/');
            try {
                $response = Http::withHeaders($headers)->get('http://' . env('UMBREL_IP') . ':12596/mainnet/' . $provider . '/api/info/');
            } catch (\Exception $e) {
                $responses[$provider] = false;
                continue;
            }
            $status = $response->status();

            // if status is 200
            if ($status === 200) {
                $responses[$provider] = $response->json();
            } else {
                $responses[$provider] = false;
            }

            $adminDashboard->provider_statuses = json_encode($responses);
            $adminDashboard->save();

        }
    }
}
