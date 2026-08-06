<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->contactService->listAll());
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->contactService->createContact($request->validated());

        return response()->json($contact, 201);
    }
}
