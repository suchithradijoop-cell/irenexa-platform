<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * A deliberately thin wrapper over Meilisearch's REST API. We use Laravel's
 * HTTP client instead of an SDK so every call is visible and explainable:
 * the engine is just an HTTP service. Every call throws on failure, so a
 * queued job that uses it is retried instead of silently losing an update.
 */
class MeilisearchClient
{
    private function http(): PendingRequest
    {
        $key = config('search.meilisearch.key');

        return Http::baseUrl((string) config('search.meilisearch.url'))
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->when(
                is_string($key) && $key !== '',
                fn (PendingRequest $request) => $request->withToken((string) $key),
            );
    }

    private function index(): string
    {
        return (string) config('search.meilisearch.contacts_index');
    }

    /**
     * Which fields are searched, and which may be used in filters. The
     * tenant_id MUST be filterable: it is how every query is fenced to one
     * tenant. Settings are applied asynchronously by Meilisearch.
     */
    public function applyContactSettings(): void
    {
        $this->http()->patch('/indexes/'.$this->index().'/settings', [
            'searchableAttributes' => ['name', 'email', 'job_title'],
            'filterableAttributes' => ['tenant_id'],
        ])->throw();
    }

    /**
     * Insert or update documents (same id = replaced).
     *
     * @param  list<array<string, mixed>>  $documents
     */
    public function addDocuments(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $this->http()
            ->post('/indexes/'.$this->index().'/documents?primaryKey=id', $documents)
            ->throw();
    }

    public function deleteDocument(int $id): void
    {
        $response = $this->http()->delete('/indexes/'.$this->index().'/documents/'.$id);

        // 404 = the index does not exist yet; nothing to delete.
        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    /**
     * Search inside ONE tenant. The tenant id is an int, so it cannot carry
     * filter syntax; callers can never choose the filter.
     *
     * @return array{ids: list<int>, total: int}
     */
    public function searchContacts(string $phrase, int $tenantId, int $page, int $perPage): array
    {
        $response = $this->http()->post('/indexes/'.$this->index().'/search', [
            'q' => $phrase,
            'filter' => 'tenant_id = '.$tenantId,
            'page' => $page,
            'hitsPerPage' => $perPage,
            'attributesToRetrieve' => ['id'],
        ])->throw();

        /** @var list<array{id: int}> $hits */
        $hits = $response->json('hits', []);

        return [
            'ids' => array_map(static fn (array $hit): int => (int) $hit['id'], $hits),
            'total' => (int) $response->json('totalHits', 0),
        ];
    }
}
