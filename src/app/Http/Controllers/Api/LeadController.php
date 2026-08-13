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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadConversionService $leadConversionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        // ?per_page=25 on the URL; defaults to 15 if not given.
        // LeadService clamps this to a safe range — the controller
        // doesn't need to know or enforce that limit itself.
        $perPage = $request->integer('per_page', 15);

        // Passing a LengthAwarePaginator (instead of a plain Collection)
        // into ::collection() makes Laravel automatically add "links"
        // and "meta" (current_page, total, per_page, etc.) to the JSON
        // response — no extra code needed for that part.
        return LeadResource::collection($this->leadService->listPaginated($perPage));
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
