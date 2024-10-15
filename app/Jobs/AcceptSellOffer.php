<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Services\SlackService;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AcceptSellOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected Offer $offer;

    protected AdminDashboard $adminDashboard;

    protected mixed $amount;

    /**
     * Create a new job instance.
     */
    public function __construct(Offer $offer, AdminDashboard $adminDashboard, $amount = null)
    {
        $this->offer = $offer;
        $this->adminDashboard = $adminDashboard;
        $this->amount = $amount;

    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        if (!$this->adminDashboard->panicButton) {
            $slackService = new SlackService();

            $robosats = new Robosats();
            $slackService->sendMessage('Auto Accepting Offer: ' . $this->offer->robosatsId);
            $robosats->acceptOffer($this->offer->robosatsId, $this->amount);
        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - AcceptSellOffer.php');
        }
    }

}
