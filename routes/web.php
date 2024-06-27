<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/testing', function () {
    $robosats = new Robosats();
    // $response = $robosats->request('api/book/');
    $response = $robosats->getBookOffers();

    $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
    $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');
    return [
        'negativeBuyOffers' => $negativeBuyOffers,
        'positiveSellOffers' => $positiveSellOffers
    ];
});

Route::get('/home2', function () {

    $robosats = new Robosats();
    // $response = $robosats->request('api/book/');
    $response = $robosats->getBookOffers();

    $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
    $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');

    return view('welcome', [
        'negativeBuyOffers' => $negativeBuyOffers,
        'positiveSellOffers' => $positiveSellOffers
    ]);
});

Route::post('/initiateBuyOffers', function () {

});


require __DIR__.'/auth.php';
