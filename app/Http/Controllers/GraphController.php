<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;

class GraphController extends Controller
{
    public function index()
    {
        $dailyVolume = [];
        $offers = Offer::where('status', '99')->get();
        foreach ($offers as $offer) {
            $dailyVolume[$offer->created_at->format('Y-m-d')][] = round($offer->amount);
        }

        // combine all the daily volumes
        foreach ($dailyVolume as $date => $volumes) {
            $dailyVolume[$date] = array_sum($volumes);
        }


        return inertia('Graphs', [
            'dates' => array_keys($dailyVolume),
            'volumes' => array_values($dailyVolume),
        ]);
    }
}
