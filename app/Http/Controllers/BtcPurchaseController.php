<?php

namespace App\Http\Controllers;

use App\Models\BtcPurchase;
use Illuminate\Http\Request;

class BtcPurchaseController extends Controller
{
    public function index()
    {
        $purchase = BtcPurchase::query();
        return inertia('PurchasesIndex', [
            'purchases' => $purchase->paginate(25)->setPath(route('purchases.index'))->through(fn($purchase)=>[
                "TXID" => $purchase->tx_id,
                "Description" => $purchase->primaryDescription,
                // "Ref ID" => $purchase->ref_id,
                "User Ref" => $purchase->user_ref,
                // "Status" => $purchase->status,
                // "Reason" => $purchase->reason,
                // "Expire Timestamp" => $purchase->expire_timestamp,
                "Close Timestamp" => $purchase->close_timestamp,
                "Description Pair" => $purchase->description_pair,
                "Description Type" => $purchase->description_type,
                "Description Order Type" => $purchase->description_order_type,
                // "Description Price" => $purchase->description_price,
                // "Description Secondary Price" => $purchase->description_secondary_price,
                // "Description Leverage" => $purchase->description_leverage,
                "Description Order" => $purchase->description_order,
                // "Description Close" => $purchase->description_close,
                "Volume" => $purchase->volume,
                "Volume Executed" => $purchase->volume_executed,
                "Cost" => round($purchase->cost,3),
                "Fee" => round($purchase->fee,3),
                "Price" => round($purchase->price),
                "Stop Price" => round($purchase->stop_price),
                "Limit Price" => round($purchase->limit_price),
                // "Miscellaneous" => $purchase->miscellaneous,
                "Flags" => $purchase->flags,
                "Trades" => $purchase->trades,
                // "Created At" => $purchase->created_at,
                // "Updated At" => $purchase->updated_at

            ])->withQueryString(),
            // 'filters' => Request::only(['search'])
        ]);
    }
}
