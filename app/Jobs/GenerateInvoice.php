<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use App\Services\SlackService;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoice implements ShouldQueue
{
    use Queueable;

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
     */
    public function handle(): void
    {
        if (!$this->adminDashboard->panicButton) {
            if ($this->offer->type === "buy" && ($this->offer->status == 6 || $this->offer->status == 8) && $this->adminDashboard->autoInvoice) {
                $robosats = new Robosats();
                $response = $robosats->updateInvoice($this->offer);
            }
        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - GenerateInvoice.php');
        }
    }
}
