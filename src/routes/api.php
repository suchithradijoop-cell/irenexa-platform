<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/leads', [LeadController::class, 'index']);
Route::post('/leads', [LeadController::class, 'store']);
