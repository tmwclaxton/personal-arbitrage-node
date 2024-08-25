<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;

class GraphController extends Controller
{
    public function index()
    {
        $dailyVolume = [];
        $dailySatProfit = [];
        $offers = Offer::where('status', '14')->get();
        foreach ($offers as $offer) {
            $dailyVolume[$offer->created_at->format('Y-m-d')][] = round($offer->accepted_offer_amount);
            $dailySatProfit[$offer->created_at->format('Y-m-d')][] = round($offer->accepted_offer_profit_sat);
        }

        // combine all the daily volumes
        foreach ($dailyVolume as $date => $volumes) {
            $dailyVolume[$date] = array_sum($volumes);
            $dailySatProfit[$date] = array_sum($dailySatProfit[$date]);
        }


        return inertia('Graphs', [
            'dates' => array_keys($dailyVolume),
            'volumes' => array_values($dailyVolume),
            'profits' => array_values($dailySatProfit),
        ]);
    }
}
