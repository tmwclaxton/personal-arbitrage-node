<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoAccept extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:accept';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find sell offers worth accepting';

    /**
     * Execute the console command.
     */
    public function handle()
    {





    }
}
