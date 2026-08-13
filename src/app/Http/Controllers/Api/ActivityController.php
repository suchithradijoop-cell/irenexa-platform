<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function __construct(
        protected ActivityService $activityService,
    ) {}

    // Three thin methods, not one clever generic one — each parameter is
    // typed to a real model, so route model binding (and its automatic
    // tenant scoping from Lesson 8.7) works normally for every one of
    // them. All three just forward to the same Service method.

    public function storeForContact(StoreActivityRequest $request, Contact $contact): JsonResponse
    {
        $activity = $this->activityService->logActivity($contact, $request->validated());

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }

    public function storeForCompany(StoreActivityRequest $request, Company $company): JsonResponse
    {
        $activity = $this->activityService->logActivity($company, $request->validated());

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }

    public function storeForDeal(StoreActivityRequest $request, Deal $deal): JsonResponse
    {
        $activity = $this->activityService->logActivity($deal, $request->validated());

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }
}
