<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LogsController
{
    public function index()
    {
            return Inertia::render('Errors', [
                'errors' => [
                    'data' => []
                ]
            ]);
    }
}
