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
use mysql_xdevapi\Collection;

class AutoCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1500;

    public function handle(): void
    {
        $adminDashboard = AdminDashboard::first();
        if (!$adminDashboard->autoCreate || $adminDashboard->panicButton) {
            return;
        }

        $templates = \App\Models\PostedOfferTemplate::all();

        foreach ($templates as $template) {
            if (!$this->shouldCreateOffer($template, $adminDashboard)) {
                continue;
            }

            $ttl = $this->calculateTTL($template, $adminDashboard);


            for ($i = 0; $i < $this->getOfferCreationCount($template); $i++) {
                $chosen_provider = $this->getChosenProvider($template, $adminDashboard);
                if (empty($chosen_provider)) {
                    continue;
                }

                $surge_premium = $this->calculateSurgePremium($template);
                $total_premium = round($template->premium + $surge_premium,1); // Add surge premium to base premium

                // unpredicatability addition to avoid scalper bots
                // add or subtract 0.1 or do nothing
                $total_premium += rand(0, 2) == 0 ? 0 : (rand(0, 1) == 0 ? 0.1 : -0.1);

                $robosats = new \App\WorkerClasses\Robosats();
                $response = $robosats->createOffer(
                    $template->type,
                    $template->currency,
                    $total_premium, // Use total premium
                    $chosen_provider,
                    $template->min_amount,
                    $template->payment_methods,
                    $template->bond_size,
                    $ttl,
                    $template->escrow_time,
                    $template->latitude == 0 ? null : $template->latitude, // Simplified ternary
                    $template->longitude == 0 ? null : $template->longitude, // Simplified ternary
                    $template->slug,
                    $template->max_amount == 0 ? null : $template->max_amount,
                );

                sleep(5);
            }

            $template->last_created = Carbon::now();
            $template->last_accepted = Carbon::now(); // No need to add 0 seconds
            $template->save();
        }
    }



    private function shouldCreateOffer($template, $adminDashboard): bool
    {
        $count = Offer::where([['status', '<=', 3], ['posted_offer_template_slug', $template->slug]])->count();
        return $template->auto_create &&
            $count < $template->quantity &&
            (!($template->last_accepted && $template->cooldown) ||
                !Carbon::parse($template->last_accepted)->addSeconds($template->cooldown)->isFuture());
    }

    private function calculateTTL($template, $adminDashboard): int
    {
        $ttl = $template->ttl;
        if ($adminDashboard->scheduler) {
            $end = Carbon::parse($adminDashboard->auto_accept_end_time);
            $diff = Carbon::now()->diffInSeconds($end);
            if ($diff > 3600 && $diff < $template->ttl) {
                $ttl = max(3600, round($diff / 3600) * 3600);
            }
        }
        return $ttl;
    }

    private function getOfferCreationCount($template): int
    {
        $count = Offer::where([['status', '<=', 3], ['posted_offer_template_slug', $template->slug]])->count();
        return $template->quantity - $count;

    }


    private function getChosenProvider($template, $adminDashboard): string
    {
        $online_providers = json_decode($adminDashboard->provider_statuses, true);
        $providers = json_decode($template->provider, true);

        if ($template->randomise_provider) {
            shuffle($providers);
        }

        foreach ($providers as $provider) {
            if (isset($online_providers[$provider]) && $online_providers[$provider] !== "false") {
                return $provider;
            }
        }
        return '';
    }

    private function calculateSurgePremium($template): float
    {
        $recentlyAccepted = Offer::where([
            ['status', '=', 14],
            ['posted_offer_template_slug', $template->slug],
        ])->where('created_at', '>', Carbon::now()->subDay()) // Use subDay() for 24 hours
        ->get();

        // If no offers accepted in the last 24 hours, no surge
        if ($recentlyAccepted->isEmpty()) {
            return 0.0;
        }

        // the closer the each offer is to the current time, the higher the surge premium for that offer should be
        $increases = [];
        foreach ($recentlyAccepted as $offer) {
            $timeDiff = round(Carbon::now()->diffInHours($offer->created_at)); // Get the difference in hours
            // if timeDiff is 0 set to 1 to avoid division by zero
            $timeDiff = $timeDiff == 0 ? 1 : $timeDiff;

            // so the timediff will be negative, but less negative the closer the offer is to the current time,
            // square removes the negative


            // we need to make the punishment more severe for larger values so we divide by the square of the time difference
            $increases[] = 1 / ($timeDiff * $timeDiff);
        }

        // Sum all the increases to get the total surge premium
        $surgePremium = array_sum($increases) / 1; // Sum all the increases to get the total surge premium

        $surgePremium = $surgePremium * 2; // Double the surge premium cause too small

        // Cap the surge premium at a maximum percentage (e.g., 250%), adjustable as needed
        $maxSurgePercentage = 2.5; // 250%
        $maxSurgePremium = $template->premium * $maxSurgePercentage;
        $surgePremium = min($surgePremium, $maxSurgePremium); // Keep it within the cap

        // if the order is a buy order, the surge premium should be negative
        if ($template->type == 'sell') {
            $surgePremium = abs($surgePremium);
        } else {
            $surgePremium = -abs($surgePremium);
        }

        return $surgePremium;
    }
}
