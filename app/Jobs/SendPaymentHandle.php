<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\RobosatsChatMessage;
use App\Models\Transaction;
use App\Services\SlackService;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentHandle implements ShouldQueue
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

            if (!($this->offer->status == 9 && $this->adminDashboard->autoMessage)) {
                return;
            }

            // grab all robosats_chat_messages and see if there are any messages with the same user_nick as the robot nickname
            // if there are, we don't send the payment handle
            $messages = RobosatsChatMessage::where('offer_id', $this->offer->id)->get();
            $robot = $this->offer->robots()->first();
            $robotNickname = $robot->nickname;
            $robotMessages = $messages->where('user_nick', $robotNickname);
            if ($robotMessages->count() > 0) {
                return;
            }


            $robosats = new Robosats();

            $robot = $this->offer->robots()->first();

            [$message, $secondaryMessage] = $this->createMessageContent();

            if (isset($message) && $message !== '') {
                $robosats->webSocketCommunicate($this->offer, $robot, $message);
            }
            if (isset($secondaryMessage) && $secondaryMessage !== '') {
                sleep(5);
                $robosats->webSocketCommunicate($this->offer, $robot, $secondaryMessage);
            }


        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - SendPaymentHandle.php');
        }
    }

    private function createMessageContent()
    {
        $robot = $this->offer->robots()->first();
        // check if there is a template associated with the offer
        $template = $this->offer->templates()->first();
        if ($template) {
            // check if disable_all_messages is set to true
            if ($template->disable_all_messages) {
                // if it is then we don't send any messages
                (new SlackService)->sendMessage('Automated messages are disabled for template ' . $template->slug, $this->offer->slack_channel_id, 'bold');
                return ['', ''];
            }

            // check if there is a custom message for the template
            if ($template->custom_message) {
                (new SlackService)->sendMessage('Sending custom message using template ' . $template->slug, $this->offer->slack_channel_id, 'blockquotes');
                $message = $template->custom_message;
                return [$message, ''];
            }
        }


        // types b&m, b&t, s&m, s&t
        // if we are the seller, we need to send all payment method handles we have set up
        //  1. either by creating a message from the name and handle of the payment method
        //  2. or by using the custom buy message field if it is set

        // if we are the taker and buyer, we need to ask for the handle of our preferred payment method
        //  1. if we have a custom message set, use that for the preferred payment method

        $slackService = new SlackService();
        $paymentMethodsArray = json_decode($robot->offer->payment_methods);
        // Fetching the available handles for the payment methods
        $paymentMethods = PaymentMethod::whereIn('name', $paymentMethodsArray)->orderByDesc('preference')->get();

        // if there are payment methods with the same preference randomise the order of those payment methods to avoid always sending the same payment method
        $paymentMethodGroups = $paymentMethods->groupBy('preference');
        $paymentMethods = collect();
        foreach ($paymentMethodGroups as $paymentMethodGroup) {
            $paymentMethods = $paymentMethods->merge($paymentMethodGroup->shuffle());
        }

        if ($paymentMethods->isEmpty() || $paymentMethods->count() === 0) {
            // If no payment methods match, we can return or handle the case accordingly
            $slackService = new SlackService();
            $slackService->sendMessage('Error: No matching payment methods found for the offer with ID ' . $this->offer->robosatsId, $this->offer->slack_channel_id, 'bold');
        }

        $message = '';
        $secondaryMessage = '';
        if ($this->offer->type === 'sell') {
            // Building the final message
            if ($paymentMethods->count() == 1) {
                $paymentMethod = $paymentMethods->first();
                // check if there is either a custom message || name and handle
                if (!$paymentMethod->custom_sell_message && !($paymentMethod->name && $paymentMethod->handle)) {
                    $slackService->sendMessage("*Missing both custom message / name and handle for payment method " . $paymentMethod->name . "*");
                    $slackService->sendMessage("*Failed to send payment handle for offer with ID " . $this->offer->robosatsId . "*", $this->offer->slack_channel_id);
                    return ['', ''];
                }

                if ($paymentMethod->custom_sell_message) {
                    $message = $paymentMethod->custom_sell_message;
                } else {
                    $message = 'Hey! My ' . $paymentMethod->name . ' is ' . $paymentMethod->handle;
                }
            } else {
                if ($this->offer->my_offer) {
                    // Plural case: "My handles are: Revolut: ... - Wise: ..."
                    $formattedHandles = [];
                    foreach ($paymentMethods as $paymentMethod) {
                        if (!$paymentMethod->custom_sell_message && !($paymentMethod->name && $paymentMethod->handle)) {
                            $slackService->sendMessage("*Missing both custom message / name and handle for payment method " . $paymentMethod->name . "*");
                            $slackService->sendMessage("*Failed to send payment handle for offer with ID " . $this->offer->robosatsId . "*", $this->offer->slack_channel_id);
                            return ['', ''];
                        }
                        if ($paymentMethod->custom_sell_message) {
                            $formattedHandles[] = $paymentMethod->custom_sell_message . "\n";
                        } else {
                            $formattedHandles[] = "$paymentMethod->name: $paymentMethod->handle \n";
                        }
                    }
                    $message = "Hey! My handles are: \n\n" . implode("\n", $formattedHandles);
                } else {
                    // if it's not our offer, the onus is not on us to provide every handle listed, so we just provide the first one we can based on preference
                    foreach ($paymentMethods as $paymentMethod) {
                        if ($paymentMethod->custom_sell_message) {
                            $message = $paymentMethod->custom_sell_message;
                        } else if ($paymentMethod->name && $paymentMethod->handle) {
                            $message = 'Hey! My ' . $paymentMethod->name . ' is ' . $paymentMethod->handle;
                        }

                        if ($message) {
                            // to avoid the "kindly state which payment method you will be using" message we should set $paymentMethods to the first payment method that has a message
                            $paymentMethods = collect([$paymentMethod]);
                            break;
                        }
                    }

                    if (!$message) {
                        $slackService->sendMessage("*Failed to send payment handle for offer with ID " . $this->offer->robosatsId . " due to missing custom message / name and handle for all payment methods available.", $this->offer->slack_channel_id);
                        return ['', ''];
                    }
                }
            }

            if ($this->adminDashboard->ask_for_reference) {
                // Append the order ID reference
                $message .= "\nIf possible, please put this number somewhere in the payment reference (" . $this->offer->id . "). " .
                    "This is just to help me match your payment to your order, but is totally optional. Cheers!";
            } else {
                $message .= "\nCheers!";
            }

            if ($paymentMethods->count() > 1) {
                // Add a final note if there are multiple handles
                $secondaryMessage = "Also kindly state which payment method you will be using. Thanks! \n\n";
            }

            $secondaryMessage .= "Additionally I do not accept payments from multiple accounts, LLCs or accounts with names that sound like old people!"
            . " In other words if you're not paying for the purpose of stacking sats, please cancel the trade now."
            . " - Otherwise your payments will be returned and I will insist on collaborative cancel!"
            .  " Finally payments may take 1-2 hours to confirm to ensure transaction has settled.";

            (new SlackService)->sendMessage("Expect a payment for " . round($robot->offer->accepted_offer_amount, 2) . " " . $robot->offer->currency
                . " from one of these payment methods: " . $robot->offer->payment_methods .
                " soon! Once received, confirm the payment by typing !confirm " . $this->offer->robosatsId . " in the chat.", $this->offer->slack_channel_id);


            return [$message, $secondaryMessage];

        } else if ($this->offer->type === 'buy') {

            if ($this->offer->my_offer) {
                $message = 'Hello! What payment method do you wish to receive payment on and what is your handle?';
            } else {
                // grab the first payment method
                foreach ($paymentMethods as $paymentMethod) {
                    if ($paymentMethod->custom_buy_message) {
                        $message = $paymentMethod->custom_buy_message;
                    } else {
                        $message = 'Hello! I would like to use ' . $paymentMethod->name . ' for payment. What are your details / handle?';
                    }
                    break;
                }
            }

            $slackService->sendMessage(">You will need to send a payment to this counterparty on " . $paymentMethod->name . " " . " for " . round($robot->offer->accepted_offer_amount, 2)
                . " " . $robot->offer->currency . " soon!", $this->offer->slack_channel_id);
            return [$message, ''];

        }

    }

}
