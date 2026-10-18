<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAuditRequest;
use App\Http\Resources\AuditEntryResource;
use App\Services\AuditTimelineService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditController extends Controller
{
    public function __construct(
        protected AuditTimelineService $timeline,
    ) {}

    public function index(ListAuditRequest $request): AnonymousResourceCollection
    {
        return AuditEntryResource::collection(
            $this->timeline->list($request->toFilter(), $request->integer('per_page', 15)),
        );
    }
}
