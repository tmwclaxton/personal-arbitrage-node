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

    use Dispatchable;

    use InteractsWithQueue;

    public int $timeout = 1500;

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
            $count = Offer::where([['status', '<=', 3], ['posted_offer_template_slug', $template->slug]])->get()->count();
            if ($count >= $template->quantity || $template->last_accepted && $template->cooldown && Carbon::parse($template->last_accepted)->addSeconds($template->cooldown)->isFuture()) {
                continue;
            }

            # check if the template is active
            if ($template->auto_create) {
                $ttl = $template->ttl;

                // if $adminDashboard->scheduler is enabled, then we should adapt the ttl to when end time is if it goes over the end time
                if ($adminDashboard->scheduler) {
                    // $adminDashboard->auto_accept_end_time ($end) is in 24 hour format like 21:00:00 so we need to convert it to a carbon date object of today

                    $end = Carbon::parse($adminDashboard->auto_accept_end_time);
                    $now = Carbon::now();
                    $diff = $now->diffInSeconds($end);
                    // if diff is less than an hour skip
                    if ($diff < 3600) {
                        continue;
                    }

                    if ($diff < $template->ttl) {
                        // round to nearest hour in seconds with a minimum of 1 hour
                        $ttl = max(3600, round($template->ttl / 3600) * 3600);
                    }
                }

                for ($i = 0; $i < $template->quantity - $count; $i++) {
                    $adminDashboard = AdminDashboard::all()->first();
                    $online_providers = json_decode($adminDashboard->provider_statuses, true);
                    $chosen_provider = [];
                    $providers = json_decode($template->provider, true);
                    // dd($providers,$online_providers);
                    foreach ($providers as $provider) {
                        if (array_key_exists($provider, $online_providers) && $online_providers[$provider] !== "false") {
                            $chosen_provider = $provider;
                            break;
                        }
                    }
                    if (empty($chosen_provider)) {
                        continue;
                    }

                    $robosats = new \App\WorkerClasses\Robosats();
                    $response = $robosats->createOffer(
                        $template->type,
                        $template->currency,
                        $template->premium,
                        $chosen_provider,
                        $template->min_amount,
                        $template->payment_methods,
                        $template->bond_size,
                        $ttl,
                        $template->escrow_time,
                        (int) $template->latitude == 0 ? null : $template->latitude,
                        (int) $template->longitude == 0 ? null : $template->longitude,
                        $template->slug,
                        $template->max_amount == 0 ? null : $template->max_amount,
                    );

                    sleep(5);

                }
                $template->last_created = Carbon::now();
                $template->last_accepted = Carbon::now()->addSeconds(600);
                $template->save();


            }

        }
    }


}
