<?php

namespace App\Models;

use App\WorkerClasses\Robosats;
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
        return $this->hasMany(Robot::class);
    }



}
