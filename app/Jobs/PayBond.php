<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use App\Services\DiscordService;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PayBond implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Offer $offer;

    protected AdminDashboard $adminDashboard;

    // time to wait for the job to complete
    public int $timeout = 300;
    /**
     * Create a new job instance.
     */
    public function __construct(Offer $offer, AdminDashboard $adminDashboard)
    {
        $this->offer = $offer;
        $this->adminDashboard = $adminDashboard;
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        if (!$this->adminDashboard->panicButton) {


            $transaction = Transaction::where('offer_id', $this->offer->id)->first();
            $invoice = $transaction->bond_invoice;
            $lightningNode = new LightningNode();
            if ($this->offer->status === 3 && !$this->offer->my_offer || ($this->offer->my_offer && $this->offer->status === 0)) {
                (new DiscordService)->sendMessage('Paid bond for offer ' . $this->offer->robosatsId);
                $offer = $this->offer;
                $offer->accepted = true;
                $offer->save();
                $lightningNode->payInvoice($invoice);
            }
        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - PayBond.php');
        }
    }
}
