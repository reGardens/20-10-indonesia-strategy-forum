<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeddingApiController;
use App\Http\Controllers\Api\CoupleApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\GuestApiController;
use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\RsvpApiController;
use App\Http\Controllers\Api\SettingApiController;
use App\Http\Controllers\Api\WishApiController;

// Public API Routes (accessible by guests & frontend router)
Route::middleware(['web'])->group(function () {
    Route::get('/templates', function () {
        $templates = \App\Models\CustomTemplate::all()->map(function ($t) {
            return [
                'id' => $t->slug,
                'name' => $t->name,
                'description' => $t->description,
                'thumbnail_url' => $t->thumbnail_url ?: '/templates/default-custom.png',
                'is_custom' => (bool) $t->is_custom,
                'config' => $t->config,
            ];
        })->toArray();

        return response()->json($templates);
    });

    Route::get('/wedding/registry/exists/{slug}', [WeddingApiController::class, 'exists']);
    
    Route::prefix('/wedding/{slug}')->group(function () {
        Route::get('/couple', [CoupleApiController::class, 'show']);
        Route::get('/events', [EventApiController::class, 'index']);
        Route::get('/media', [MediaApiController::class, 'index']);
        Route::get('/payments', [PaymentApiController::class, 'show']);
        Route::get('/wishes', [WishApiController::class, 'index']);
        Route::post('/wishes', [WishApiController::class, 'store']);
        Route::post('/rsvp', [RsvpApiController::class, 'store']);
    });
});

// Protected CMS API Routes (authenticated session required)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user/profile', function () {
        $user = auth()->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'allowed_features' => $user->allowed_features ?? [
                "undangan_digital",
                "rsvp_online",
                "informasi_acara",
                "love_story",
                "manajemen_tamu",
                "amplop_digital",
                "galeri",
                "countdown",
                "qr_checkin",
                "live_streaming",
                "ucapan_doa",
                "wishlist"
            ]
        ]);
    });

    Route::get('/wedding/registry', [WeddingApiController::class, 'index']);
    Route::get('/wedding/registry/{slug}', [WeddingApiController::class, 'show']);
    Route::post('/wedding/registry', [WeddingApiController::class, 'store']);
    Route::delete('/wedding/registry/{slug}', [WeddingApiController::class, 'destroy']);

    Route::prefix('/wedding/{slug}')->group(function () {
        Route::post('/couple', [CoupleApiController::class, 'store']);

        Route::post('/events', [EventApiController::class, 'store']);
        Route::get('/events/{id}', [EventApiController::class, 'show']);
        Route::put('/events/{id}', [EventApiController::class, 'update']);
        Route::delete('/events/{id}', [EventApiController::class, 'destroy']);

        Route::get('/guests', [GuestApiController::class, 'index']);
        Route::post('/guests', [GuestApiController::class, 'store']);
        Route::post('/guests/batch', [GuestApiController::class, 'batchStore']);
        Route::get('/guests/{id}', [GuestApiController::class, 'show']);
        Route::put('/guests/{id}', [GuestApiController::class, 'update']);
        Route::delete('/guests/{id}', [GuestApiController::class, 'destroy']);
        Route::post('/guests/{id}/checkin', [GuestApiController::class, 'checkin']);

        Route::post('/media', [MediaApiController::class, 'store']);
        Route::delete('/media/{id}', [MediaApiController::class, 'destroy']);

        Route::post('/payments', [PaymentApiController::class, 'store']);

        Route::get('/rsvp', [RsvpApiController::class, 'index']);
        Route::put('/rsvp/{id}/status', [RsvpApiController::class, 'updateStatus']);
        Route::get('/rsvp/summary', [RsvpApiController::class, 'summary']);

        Route::get('/settings', [SettingApiController::class, 'show']);
        Route::post('/settings', [SettingApiController::class, 'store']);

        Route::put('/wishes/{id}/status', [WishApiController::class, 'updateStatus']);
        Route::delete('/wishes/{id}', [WishApiController::class, 'destroy']);
    });
});
