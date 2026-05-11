<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminRoomController;
use App\Http\Controllers\Api\AdminReservationController;
use App\Http\Controllers\Api\AdminSeasonalRateController;
use App\Http\Controllers\Api\MercadoPagoCheckoutController;
use App\Http\Controllers\Api\MercadoPagoWebhookController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room:slug}', [RoomController::class, 'show']);
Route::get('/rooms/{room:slug}/calendar', [AvailabilityController::class, 'calendar']);
Route::get('/rooms/{room:slug}/availability', [AvailabilityController::class, 'show']);

Route::post('/availability/check', [AvailabilityController::class, 'check']);
Route::post('/pricing/calculate', [PricingController::class, 'calculate']);

Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
Route::post('/mercado-pago/checkout-preference', [MercadoPagoCheckoutController::class, 'store']);
Route::post('/mercado-pago/webhook', [MercadoPagoWebhookController::class, 'handle']);
Route::post('/stripe/checkout-session', [MercadoPagoCheckoutController::class, 'store']);
Route::post('/stripe/webhook', [MercadoPagoWebhookController::class, 'handle']);

Route::prefix('/admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware('admin.api')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        Route::patch('/rooms/{room:slug}', [AdminRoomController::class, 'update']);
        Route::get('/rooms/{room:slug}/schedule', [AdminRoomController::class, 'schedule']);
        Route::post('/rooms/{room:slug}/blocks', [AdminRoomController::class, 'storeBlock']);
        Route::get('/rooms/{room:slug}/seasonal-rates', [AdminSeasonalRateController::class, 'index']);
        Route::post('/rooms/{room:slug}/seasonal-rates', [AdminSeasonalRateController::class, 'store']);
        Route::patch('/blocks/{roomBlock}', [AdminRoomController::class, 'updateBlock']);
        Route::delete('/blocks/{roomBlock}', [AdminRoomController::class, 'destroyBlock']);
        Route::patch('/seasonal-rates/{seasonalRate}', [AdminSeasonalRateController::class, 'update']);
        Route::delete('/seasonal-rates/{seasonalRate}', [AdminSeasonalRateController::class, 'destroy']);

        Route::post('/rooms/{room:slug}/reservations', [AdminReservationController::class, 'store']);
        Route::patch('/reservations/{reservation}', [AdminReservationController::class, 'update']);
        Route::delete('/reservations/{reservation}', [AdminReservationController::class, 'destroy']);
    });
});
