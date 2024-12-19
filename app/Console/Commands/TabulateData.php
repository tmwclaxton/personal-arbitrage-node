<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TabulateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tabulate-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate tax owed for that day, combine various fees from Kraken and invoices and calculate total revenue and profit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
