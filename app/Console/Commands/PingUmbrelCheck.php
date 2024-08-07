<?php

namespace App\Console\Commands;

use App\Services\UmbrelService;
use Illuminate\Console\Command;

class PingUmbrelCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ping-umbrel-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if proxy token is still valid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $umbrelService = new UmbrelService();
        $umbrelService->ping();
    }
}
