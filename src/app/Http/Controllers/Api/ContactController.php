<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ContactResource::collection(
            $this->contactService->listPaginated($request->integer('per_page', 15)),
        );
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->contactService->createContact($request->validated());

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }
}
