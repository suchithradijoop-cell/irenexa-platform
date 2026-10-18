<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:login')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

Route::middleware(['auth:sanctum', 'tenant', 'throttle:api'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert']);

    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/search', [ContactController::class, 'search']);
    Route::post('/contacts', [ContactController::class, 'store']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);

    Route::get('/deals', [DealController::class, 'index']);
    Route::post('/deals', [DealController::class, 'store']);

    Route::post('/contacts/{contact}/activities', [ActivityController::class, 'storeForContact']);
    Route::post('/companies/{company}/activities', [ActivityController::class, 'storeForCompany']);
    Route::post('/deals/{deal}/activities', [ActivityController::class, 'storeForDeal']);

    // Tenant audit timeline, admins only (Lesson 15.5). Reads through the
    // repository interface, so it works with whichever store is active.
    Route::get('/audit', [AuditController::class, 'index']);

    // The caller's own in-app inbox (Lesson 14.5). No {user} in any URL:
    // whose inbox it is always comes from the authenticated token.
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
