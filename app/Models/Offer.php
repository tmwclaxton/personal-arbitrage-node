<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function robots()
    {
        return Robot::all()->where('offer_id', $this->id);
    }
}
