<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

// This class holds no real code — it exists only so swagger-php has one
// fixed place to attach the "top of the document" info (title, version,
// servers, how authentication works). Every #[OA\...] attribute anywhere
// in the codebase gets collected together into one spec; this is just
// where the parts that don't belong to any one endpoint live.
#[OA\Info(
    version: '1.0.0',
    title: 'IRENEXA Platform API',
    description: 'Multi-tenant CRM SaaS API — Leads, Contacts, Companies, Deals, Activities, Workflow automation.',
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Version 1',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    description: 'Send the token from POST /login as: Authorization: Bearer {token}',
)]
class OpenApiSpec {}
