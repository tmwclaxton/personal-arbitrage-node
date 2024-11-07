<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class GuideController
{
    public function index()
    {
        return Inertia::render('Guide');
    }
}
