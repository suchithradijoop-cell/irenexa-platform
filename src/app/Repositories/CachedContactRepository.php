<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use App\MultiTenancy\TenantContext;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Decorator around EloquentContactRepository (Decorator Pattern): it
 * implements the exact same interface, wraps a real repository instance,
 * and adds caching around one method without changing EloquentContactRepository
 * at all. This is the Open/Closed Principle in practice — we extended
 * behaviour by adding a new class, not by modifying an existing one.
 */
class CachedContactRepository implements ContactRepositoryInterface
{
    /**
     * How long a cached page of contacts stays valid before it is
     * recalculated from MySQL. Kept short deliberately — this cache exists
     * to absorb repeated reads within a short window (a rep refreshing a
     * list page), not to serve minutes-old data as if it were fresh.
     */
    private const TTL_SECONDS = 300;

    public function __construct(
        protected EloquentContactRepository $repository,
        protected TenantContext $tenantContext,
    ) {}

    public function all(): Collection
    {
        // Not cached: an uncommon, unpaginated "give me everything" call.
        // Caching every possible shape of this call is not worth the
        // complexity for how rarely it's used.
        return $this->repository->all();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage('page');

        return Cache::tags($this->tag())->remember(
            $this->cacheKey($page, $perPage),
            self::TTL_SECONDS,
            fn () => $this->repository->paginate($perPage),
        );
    }

    public function find(int $id): ?Contact
    {
        // Not cached: a single indexed lookup is already cheap, and
        // caching it raises the risk of serving stale data immediately
        // after an update to that one record.
        return $this->repository->find($id);
    }

    public function create(array $data): Contact
    {
        $contact = $this->repository->create($data);

        // Invalidation: a new contact just changed what every cached page
        // of this tenant's contact list should contain. We don't know (and
        // shouldn't need to know) every page/per_page combination that's
        // currently cached — flushing the whole tag clears all of them in
        // one call, because every entry cached under this tag was written
        // through the same tag() method below.
        Cache::tags($this->tag())->flush();

        return $contact;
    }

    /**
     * The tag scopes every cache entry (and the flush() above) to exactly
     * this tenant's contacts — the same fail-closed reasoning as
     * TenantScope: an unresolved tenant falls back to -1, never sharing a
     * tag with a real tenant. Flushing this tag can only ever clear this
     * tenant's own cached pages, never another tenant's.
     */
    private function tag(): string
    {
        return sprintf('tenant:%d:contacts', $this->tenantContext->id() ?? -1);
    }

    private function cacheKey(int $page, int $perPage): string
    {
        return sprintf('contacts:page:%d:per_page:%d', $page, $perPage);
    }
}
