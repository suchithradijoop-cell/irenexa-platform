<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Contact;
use App\MultiTenancy\TenantContext;
use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class MeilisearchContactSearcher implements ContactSearcher
{
    public function __construct(
        private readonly MeilisearchClient $client,
        private readonly TenantContext $tenantContext,
    ) {}

    public function search(SearchQuery $query, int $perPage): PaginatorContract
    {
        $page = Paginator::resolveCurrentPage('page');
        $tenantId = $this->tenantContext->id();

        // Fail closed, like TenantScope: no known tenant means NO results.
        // The index has no global scope, so this check is what stands
        // between a bug and another company's customers.
        if ($tenantId === null) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $result = $this->client->searchContacts(
            implode(' ', $query->words),
            $tenantId,
            $page,
            $perPage,
        );

        // The engine only tells us WHICH contacts match and in what order.
        // The real rows come from MySQL, through the TenantScope: a second
        // safety layer, and it means we never show stale index data.
        $contacts = Contact::query()
            ->whereIn('id', $result['ids'])
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($result['ids'] as $id) {
            if ($contacts->has($id)) {
                $ordered[] = $contacts->get($id);
            }
        }

        return new LengthAwarePaginator($ordered, $result['total'], $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }
}
