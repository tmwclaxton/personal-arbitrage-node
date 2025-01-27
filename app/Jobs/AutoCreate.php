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

    public int $ordersInOneGo = 3;

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
                $total_premium = $template->premium + $surge_premium; // Add surge premium to base premium

                // if it is the weekend add to sell orders and subtract from buy orders to deal with forex market closure
                //!TODO: we should add some configuration around weekend premiums
                // if (Carbon::now()->isWeekend() && $template->currency != 'GBP') {
                //     $total_premium += $template->type == 'sell' ? 0.5 : -0.5;
                // }

                // unpredicatability addition to avoid scalper bots when the market is slow
                if ($surge_premium == 0) {
                    // add or subtract 0.1 or do nothing
                    $total_premium += rand(0, 2) == 0 ? 0 : (rand(0, 1) == 0 ? 0.1 : -0.1);
                }

                $total_premium = round($total_premium, 1);

                $robosats = new \App\WorkerClasses\Robosats();
                $response = $robosats->createOffer(
                    $template->type,
                    $template->currency,
                    $total_premium,
                    $chosen_provider,
                    $template->min_amount,
                    $template->payment_methods,
                    $template->bond_size,
                    $ttl,
                    $template->escrow_time,
                    $template->latitude == 0 ? null : $template->latitude,
                    $template->longitude == 0 ? null : $template->longitude,
                    $template->slug,
                    $template->max_amount == 0 ? null : $template->max_amount,
                );

                $this->ordersInOneGo--;

                sleep(5);
            }

            $template->last_created = Carbon::now();
            $template->last_accepted = Carbon::now();
            $template->save();

            if ($this->ordersInOneGo <= 0) {
                break;
            }
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
        $maxSurgePercentage = 1.5;
        $bucketSizeHours = 3; // Size of each time bucket (e.g., 3 hours)
        $numBuckets = 8;      // Number of buckets to consider (8 * 3 = 24 hours)
        $bucketWeights = [4, 2.9, 2.5, 1.5, 1, 0.7, 0.3, 0.1];
        $bucket_multiplier = 1.25;

        $bucketCounts = array_fill(0, $numBuckets, 0); // Initialize counts for each bucket
        $recentlyAccepted = Offer::where([
            ['status', '=', 14],
            ['posted_offer_template_slug', $template->slug],
        ])->where('created_at', '>', Carbon::now()->subHours($bucketSizeHours * $numBuckets))
            ->get();

        // Count the number of accepted offers in each bucket, check the array key for the bucket
        foreach ($recentlyAccepted as $offer) {
            $bucketIndex = abs(floor((Carbon::now()->diffInHours($offer->created_at) / $bucketSizeHours)));
            // the max and min should be 0 and $numBuckets - 1
            $bucketIndex = min($bucketIndex, $numBuckets - 1); // this step is as sometimes the index is calculated as 7. something
            $bucketCounts[$bucketIndex] += 1;
        }


        // example bucketCounts array for testing
//         $bucketCounts = [1,0,1,0,1,0,0,1];

        $weightedSum = 0;
        $totalWeight = 0;
        for ($i = 0; $i < $numBuckets; $i++) {
            $weightedSum += $bucketCounts[$i] * $bucketWeights[$i] * pow($bucket_multiplier, $i);
            $totalWeight += $bucketWeights[$i];
        }

        if ($totalWeight > 0) {
            $averageOrders = $weightedSum / $totalWeight;
        } else {
            $averageOrders = 0;
        }

        // Adjust sensitivity (0.5 reduces sensitivity)
        $surgePremium = $averageOrders * 0.5;
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
