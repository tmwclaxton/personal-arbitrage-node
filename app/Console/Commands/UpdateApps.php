<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-apps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update apps on the phone';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        # check if there are any offers in status 9 or 10
        $offers = \App\Models\Offer::where([['status', '=', 9], ['auto_confirm_at' , '!=', null]])->orWhere([['status', '=', 10], ['auto_confirm_at' , '=', null]])->get();
        if ($offers->count() == 0) {

            $url = 'http://' . env('SUAVE_HOST', 'suave-py') .':' . env('SUAVE_PORT', 8000) . '/update-apps';
            Http::get($url);
        }

    }
}
