<?php

namespace App\Models;

use App\WorkerClasses\Robosats;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function transaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function robots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Robot::class);
    }

    public function templates()
    {
        return PostedOfferTemplate::where('slug', $this->posted_offer_template_slug);
    }


    // if the offer is a buy offer (will show up as sell for the counterparty)
    // and it is our offer, then we need to change the profit to a negative number
    // public function fixProfitSigns(): void
    // {
    //     if ($this->type == "buy" && !$this->my_offer) {
    //         $this->satoshi_amount_profit = abs($this->satoshi_amount_profit);
    //         $this->min_satoshi_amount_profit = abs($this->min_satoshi_amount_profit);
    //         $this->max_satoshi_amount_profit = abs($this->max_satoshi_amount_profit);
    //         $this->accepted_offer_profit_sat = abs($this->accepted_offer_profit_sat);
    //
    //         $this->save();
    //     }
    // }





}
