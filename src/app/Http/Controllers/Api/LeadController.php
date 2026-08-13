<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Resources\DealResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadConversionService $leadConversionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        // Returning a Resource collection directly (not wrapped in
        // response()->json()) is the normal Laravel style — Laravel
        // knows how to turn a JsonResource into a proper JSON response
        // on its own, with the correct Content-Type header.
        return LeadResource::collection($this->leadService->listAll());
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead($request->validated());

        // ->response() turns the Resource into a real JsonResponse so we
        // can still set the 201 status code — a plain "return new
        // LeadResource($lead)" would default to 200, which is wrong for
        // a successful creation.
        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function convert(ConvertLeadRequest $request, Lead $lead): JsonResponse
    {
        $deal = $this->leadConversionService->convert(
            $lead,
            $request->validated('company_name'),
            $request->validated('deal_title'),
            (float) $request->validated('deal_amount'),
        );

        return (new DealResource($deal))->response()->setStatusCode(201);
    }
}
