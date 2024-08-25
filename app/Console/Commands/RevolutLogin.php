<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RevolutLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revolut-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger suave container to login to revolut';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        # check if there are any offers in status 9 or 10
        $offers = \App\Models\Offer::where('status', 9)->orWhere('status', 10)->get();
        if ($offers->count() > 0) {
            $job = new \App\Jobs\RevolutLogin();
            $job->handle();
        }


    }
}
