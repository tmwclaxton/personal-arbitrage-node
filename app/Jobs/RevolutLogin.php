<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RevolutLogin implements ShouldQueue
{
    use Queueable;

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
        # hit POST suave-container:8000/revolut-login

        $url = 'http://sauve-py:' . env('SUAVE_PORT', 8000) . '/revolut-login';
        Http::post($url, [
            'auto_bal_flag' => true,
        ]);

    }
}
