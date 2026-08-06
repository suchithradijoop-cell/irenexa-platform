<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadConversionService $leadConversionService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->leadService->listAll());
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead($request->validated());

        return response()->json($lead, 201);
    }

    public function convert(ConvertLeadRequest $request, Lead $lead): JsonResponse
    {
        $deal = $this->leadConversionService->convert(
            $lead,
            $request->validated('company_name'),
            $request->validated('deal_title'),
            (float) $request->validated('deal_amount'),
        );

        return response()->json($deal, 201);
    }
}
