<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:transactions';

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
        // update all current transactions where status != 14, 12, 17, 18, 99
        $transactions = Transaction::whereNotIn('status', [14, 12, 17, 18, 99])->get();
        foreach ($transactions as $transaction) {
            $offer = $transaction->offer;
            $robosats = new Robosats();
            $response = $robosats->updateTransactionStatus($offer);
        }
    }
}
