<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use App\Services\ReportingService;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ConfirmPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Offer $offer;

    protected AdminDashboard $adminDashboard;

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
            $robosats = new Robosats();
            // set the auto_confirm_at to 5 minutes from now

            // if adminDashboard->adverts_enabled is true, set auto_confirm_at to 5 minutes from now
            if ($this->adminDashboard->adverts_enabled) {
                $this->offer->auto_confirm_at = Carbon::now()->addMinutes(5);
            } else {
                $this->offer->auto_confirm_at = Carbon::now()->addSecond(15);
            }

            if ($this->adminDashboard->email_reporting_enabled) {

                $offer_amount = round($transaction->offer->accepted_offer_amount,2);
                $currency = $transaction->offer->currency;

                $formatted_amount = $offer_amount ." ". $currency;

                ReportingService::sendReportingEmail($formatted_amount, $currency);

                $this->offer->auto_confirm_at = Carbon::now()->addMinutes(
                    $this->adminDashboard->email_reporting_auto_delay
                );
            }

            $this->offer->save();

            // send advert to the counterparty
            $robosats->advertise($this->offer);
        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - ConfirmPayment.php');
        }
    }
}
