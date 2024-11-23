<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class TaxReturnController
{
    public function index()
    {
        return Inertia::render('TaxReturn');
    }
}
