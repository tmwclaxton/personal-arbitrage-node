<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\DiscordService;
use App\Services\RevolutService;
use App\Services\WiseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WisePaymentListener implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $adminDashboard = \App\Models\AdminDashboard::all()->first();
        if ($adminDashboard->panicButton) {
            return;
        }

        $wiseService = new WiseService();

        $profiles = $wiseService->getClient()->profiles->all();

        $wiseService = new \App\Services\WiseService();
        $response = $wiseService->getActivities($profiles[0]['id']);
        $activities = $response['activities'];
        foreach ($activities as $activity) {
            if (
                $activity['type'] === "TRANSFER" &&
                // $activity['description'] !== "<strong>Toby Matthew William Claxton</strong>" &&
                $activity['status'] === "COMPLETED" &&
                str_contains($activity['primaryAmount'], '+') &&
                $activity['createdOn'] > Carbon::now()->subHour(1)
            ) {
                // $activity['primaryAmount'] = '<positive>+ 200 EUR</positive>' -> 200 EUR
                $activity['formattedAmount'] = trim(str_replace('+', '', str_replace(['<positive>', '</positive>'], '', $activity['primaryAmount'])));

                // if secondaryAmount is not null, then we need to overwrite the formattedAmount with the secondaryAmount
                if ($activity['secondaryAmount'] !== "") {
                    $activity['formattedAmount'] = $activity['secondaryAmount'];
                }

                // now that we have a formattedAmount in the form x.x CURRENCY, we need to split it into amount and currency
                $activity['amount'] = explode(' ', $activity['formattedAmount'])[0];
                // if amount is 0, then we skip this activity
                if ($activity['amount'] == 0) {
                    continue;
                }

                $activity['currency'] = explode(' ', $activity['formattedAmount'])[1];

                // add a column for sender  "title" => "<strong>Igor Pinto Borges</strong>"
                $activity['sender'] = trim(str_replace(['<strong>', '</strong>'], '', $activity['title']));

                $payment = new \App\Models\Payment();
                $payment->payment_method = 'Wise';
                $payment->platform_transaction_id = $activity['id'];

                if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
                    continue;
                }

                $payment->payment_currency = $activity['currency'];
                $payment->payment_amount = $activity['amount'];
                $payment->platform_account_id = $activity['sender'];
                $payment->platform_description = $activity['description'] != "" ? $activity['description'] : $activity['sender'];
                $payment->platform_entity = json_encode($activity);
                //'2024-08-25T23:20:30.084Z'
                $parsedDate = Carbon::parse($activity['createdOn']);
                $payment->payment_date = $parsedDate->toDateTimeString();

                $payment->save();

                $discordService = new DiscordService();
                $message = 'Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Wise';
                # if there is a description, append it to the message
                if ($payment->platform_description) {
                    $message .= ' with description: ' . $payment->platform_description;
                }
                # if there is a payment reference, append it to the message
                if ($payment->payment_reference) {
                    $message .= ' with reference: ' . $payment->payment_reference;
                }
                $discordService->sendMessage($message);


            }
        }

    }
}
