<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->companyService->listAll());
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->createCompany($request->validated());

        return response()->json($company, 201);
    }
}
