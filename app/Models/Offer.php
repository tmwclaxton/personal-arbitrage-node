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

    public function templates(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PostedOfferTemplate::class);
    }



}
