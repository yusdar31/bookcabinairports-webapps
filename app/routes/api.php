<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Bookcabin
|--------------------------------------------------------------------------
|
| Prefix: /api
| Middleware: api (throttle + stateless)
|
*/

// --- Public ---
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]));

// --- Webhooks (no auth, signature-verified) ---
Route::post('/webhooks/midtrans', [PaymentController::class, 'midtransWebhook']);

// --- Auth ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web');

// --- Protected routes ---
Route::middleware('auth:web')->group(function () {

    Route::get('/me', fn () => request()->user());

    // Room availability (resepsionis + manajer)
    Route::middleware('role:resepsionis,manajer')
        ->get('/rooms/availability', [BookingController::class, 'availability']);

    // Bookings (resepsionis + manajer)
    Route::middleware('role:resepsionis,manajer')->prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::post('/{booking}/check-in', [BookingController::class, 'checkIn']);
        Route::post('/{booking}/check-out', [BookingController::class, 'checkOut']);
        Route::post('/{booking}/pay', [PaymentController::class, 'createPayment']);
    });

    // POS Transactions (kasir + manajer)
    Route::middleware('role:kasir,manajer')->prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::post('/sync', [TransactionController::class, 'syncOffline']);
    });

    // Admin Dashboard + Reports (manajer)
    Route::middleware('role:manajer')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/reports/revenue', [ReportController::class, 'revenue']);
        Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);
        Route::get('/reports/export/transactions', [ReportController::class, 'exportTransactions']);
        Route::get('/reports/export/bookings', [ReportController::class, 'exportBookings']);
    });
});
