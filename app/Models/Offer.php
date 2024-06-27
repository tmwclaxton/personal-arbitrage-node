<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function acceptSellOffer()
    {
        // send dude revtag

    }

    public function confirmPayment()
    {
        // do reply to guy or click something
    }

}
