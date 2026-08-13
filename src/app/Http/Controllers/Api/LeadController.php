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
use OpenApi\Attributes as OA;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadConversionService $leadConversionService,
    ) {}

    #[OA\Get(
        path: '/leads',
        summary: 'List leads for the current tenant',
        tags: ['Leads'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of leads'),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ],
    )]
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

    #[OA\Post(
        path: '/leads',
        summary: 'Create a new lead',
        tags: ['Leads'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Prospect'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+91 98765 43210'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Lead created'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead($request->validated());

        // ->response() turns the Resource into a real JsonResponse so we
        // can still set the 201 status code — a plain "return new
        // LeadResource($lead)" would default to 200, which is wrong for
        // a successful creation.
        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/leads/{lead}/convert',
        summary: 'Convert a lead into a Company, Contact, and Deal',
        tags: ['Leads'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_name', 'deal_title', 'deal_amount'],
                properties: [
                    new OA\Property(property: 'company_name', type: 'string', example: 'Acme Inc'),
                    new OA\Property(property: 'deal_title', type: 'string', example: 'Annual contract'),
                    new OA\Property(property: 'deal_amount', type: 'number', format: 'float', example: 12000),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Deal created from the converted lead'),
            new OA\Response(response: 403, description: 'Not allowed to create a deal'),
            new OA\Response(response: 404, description: 'Lead not found'),
        ],
    )]
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
