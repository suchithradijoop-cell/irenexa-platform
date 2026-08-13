<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDealRequest;
use App\Http\Resources\DealResource;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return DealResource::collection($this->dealService->listAll());
    }

    public function store(StoreDealRequest $request): JsonResponse
    {
        $deal = $this->dealService->createDeal($request->validated());

        return (new DealResource($deal))->response()->setStatusCode(201);
    }
}
