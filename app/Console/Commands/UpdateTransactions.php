<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->normalUmbrelCommandCheck()) {
            // kick off the job
            $job = new \App\Jobs\UpdateTransactions();
            $job->handle();
        }
    }
}
