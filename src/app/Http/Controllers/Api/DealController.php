<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDealRequest;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->dealService->listAll());
    }

    public function store(StoreDealRequest $request): JsonResponse
    {
        $deal = $this->dealService->createDeal($request->validated());

        return response()->json($deal, 201);
    }
}
