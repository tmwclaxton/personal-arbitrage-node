<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\WorkerClasses\LightningNode;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\GenerateInvoice();
        $job->handle();
    }
}
