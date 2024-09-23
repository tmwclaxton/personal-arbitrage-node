<?php

namespace App\Console\Commands;

use App\Services\UmbrelService;
use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class PingUmbrelCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:umbrel-token-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the umbrel token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->refreshUmbrelCommandCheck()) {
            $umbrelService = new UmbrelService();
            $umbrelService->resetProxyToken();
        } else {
            $this->error('Umbrel IP or password not set');
        }
    }
}
