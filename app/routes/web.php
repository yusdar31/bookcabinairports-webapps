<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Bookcabin
|--------------------------------------------------------------------------
*/

// Public
Route::get('/', fn () => redirect('/login'));

// Auth pages
Route::get('/login', fn () => view('auth.login'))->name('login');

// Protected (session auth)
Route::middleware('auth')->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->middleware('role:kasir,manajer');
    Route::get('/booking/create', fn () => view('booking.create'))->middleware('role:resepsionis,manajer');
    Route::get('/dashboard', fn () => 'Dashboard coming soon')->middleware('role:manajer');
    Route::post('/logout', fn () => (auth()->logout() ?: redirect('/login')))->name('logout');
});
