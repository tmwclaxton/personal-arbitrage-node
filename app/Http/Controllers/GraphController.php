<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\PostedOfferTemplate;
use App\WorkerClasses\HelperFunctions;
use Illuminate\Http\Request;

class GraphController extends Controller
{
    public function index()
    {
        // Initialize arrays to store the grouped data
        $dailyVolumeByCurrency = [];
        $dates = [];

        // Fetch all offers with status 13 or 14
        $offers = Offer::whereIn('status', ['14', '13','15'])->get();

        // Loop through each offer
        foreach ($offers as $offer) {
            // Get the offer's date and currency
            $date = $offer->created_at->format('Y-m-d');
            $currency = $offer->currency;

            // Add the date to the list of dates if not already added
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }

            // Initialize the currency entry for the date if it doesn't exist
            if (!isset($dailyVolumeByCurrency[$currency])) {
                $dailyVolumeByCurrency[$currency] = [];
            }

            // Initialize the volume for the date if it doesn't exist for the currency
            if (!isset($dailyVolumeByCurrency[$currency][$date])) {
                $dailyVolumeByCurrency[$currency][$date] = 0;
            }

            // Accumulate the volume for that date and currency
            $dailyVolumeByCurrency[$currency][$date] += round($offer->accepted_offer_amount);
        }

        // Now we need to fill in missing dates for each currency with a volume of 0
        foreach ($dailyVolumeByCurrency as $currency => &$volumes) {
            foreach ($dates as $date) {
                if (!isset($volumes[$date])) {
                    $volumes[$date] = 0;  // If a date is missing, set the volume to 0
                }
            }

            // Sort volumes by date to ensure they are in the correct order
            ksort($volumes);
        }

        // Prepare the data to pass to the frontend
        $volumesByCurrency = [];
        unset($volumes);
        foreach ($dailyVolumeByCurrency as $currency => $volumes) {
            $volumesByCurrency[$currency] = array_values($volumes); // Convert from associative array to indexed array
        }

        $dailySatProfit = [];
        foreach ($offers as $offer) {
            $dailySatProfit[$offer->created_at->format('Y-m-d')][] = round($offer->accepted_offer_profit_sat);
        }

        foreach ($dailySatProfit as $date => $profits) {
            $dailySatProfit[$date] = array_sum($dailySatProfit[$date]);
        }

        // using helper function to convert satoshi to GBP
        // grab primary currency from admin dashboard
        $adminDashboard = AdminDashboard::all()->first();
        $primaryCurrency = $adminDashboard->primary_currency;
        $dailyFiatProfit = [];
        $helper = new HelperFunctions();
        foreach ($dailySatProfit as $date => $profit) {
            $dailyFiatProfit[$date] = $helper->convertCurrency($helper->satoshiToBtc($profit), 'BTC', $primaryCurrency);
        }

        $dailyPremium = [];
        foreach ($offers as $offer) {
            $dailyPremium[$offer->created_at->format('Y-m-d')][] = $offer->accepted_offer_profit_sat / $offer->accepted_offer_amount_sat * 100;
        }

        foreach ($dailyPremium as $date => $volumes) {
            $dailyPremium[$date] = array_sum($volumes) / count($volumes);
        }

        // Calculate the ratio between make and take i.e. the flag of my_offer
        $dailyRatioBetweenMakeAndTake = [];
        foreach ($offers as $offer) {
            $dailyRatioBetweenMakeAndTake[$offer->created_at->format('Y-m-d')][] = $offer->my_offer;
        }

        foreach ($dailyRatioBetweenMakeAndTake as $date => $ratios) {
            $dailyRatioBetweenMakeAndTake[$date] = array_sum($ratios) / count($ratios);
        }

        // Calculate the popularity of each template
        $templatePopularity = [];
        $templateSlugs = PostedOfferTemplate::all()->pluck('slug')->toArray();


        foreach ($templateSlugs as $templateId) {
            $templatePopularity[$templateId] = Offer::where('posted_offer_template_slug', $templateId)->whereIn('status', ['14', '13','15'])->count();
        }

        // Prepare the data to pass to the frontend
        $templatePopularityForBarChart = [];
        foreach ($templatePopularity as $templateId => $popularity) {
            $templatePopularityForBarChart[$templateId] = $popularity;
        }





        return inertia('Graphs', [
            'dates' => $dates, // Dates in chronological order
            'volumesByCurrency' => $volumesByCurrency, // Volume data organized by currency
            'profits' => array_values($dailySatProfit),
            'profitsInFiat' => array_values($dailyFiatProfit),
            'primaryCurrency' => $primaryCurrency,
            'averagePremiums' => array_values($dailyPremium),
            'ratiosBetweenMakeAndTake' => array_values($dailyRatioBetweenMakeAndTake),
            'templateIds' => $templateSlugs,
            'templatePopularity' => array_values($templatePopularityForBarChart),
        ]);
    }


}
