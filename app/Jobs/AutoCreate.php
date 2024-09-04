<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AutoCreate implements ShouldQueue
{
    use Queueable;

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
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard->autoCreate || $adminDashboard->panicButton) {
            return;
        }
        # grab all templates
        $templates = \App\Models\PostedOfferTemplate::all();
        foreach ($templates as $template) {
            // check if last_created is set and cooldown is set and if the cooldown has passed
            if ($template->last_created && $template->cooldown && Carbon::parse($template->last_created)->addSeconds($template->cooldown)->isFuture()) {
                continue;
            }

            # check if the template is active
            if ($template->auto_create) {
                # check if the template quantity is less than matching offers
                $count = Offer::where([['status', '<=', 3], ['posted_offer_template_id', $template->id]])->get()->count();
                if ($template->quantity > $count) {

                    for ($i = 0; $i < $template->quantity - $count; $i++) {
                        $robosats = new \App\WorkerClasses\Robosats();
                        $response = $robosats->createSellOffer(
                            $template->currency,
                            $template->premium,
                            $template->provider,
                            $template->min_amount,
                            $template->payment_methods,
                            $template->bond_size,
                            $template->id,
                            $template->ttl,
                            $template->max_amount == 0 ? null : $template->max_amount,
                        );

                    }
                    $template->last_created = now();
                    $template->save();

                }
            }

        }
    }


}
