<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StudentAuthController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {

    // ── Public front routes ───────────────────────────────────────────────
    Route::get('/', [HomeController::class, 'index'])->name('home');

});

Route::get('/', function () {
    return redirect('/' . app()->getLocale());
});
