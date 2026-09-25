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

        return Cache::remember(
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
        return $this->repository->create($data);
    }

    /**
     * Tenant ID is part of the key on purpose. Without it, Tenant A's
     * cached page 1 of contacts would be served straight to Tenant B —
     * the same class of cross-tenant leak the TenantScope bug (Session
     * Log, 2026-08-13) was, just moved from the database layer into the
     * cache layer instead.
     *
     * Falls back to -1 (never a real tenant ID) if the tenant somehow
     * isn't resolved yet, for the same fail-closed reason TenantScope
     * does the same thing: an unknown tenant must never share a cache key
     * with a known one.
     */
    private function cacheKey(int $page, int $perPage): string
    {
        return sprintf(
            'tenant:%d:contacts:page:%d:per_page:%d',
            $this->tenantContext->id() ?? -1,
            $page,
            $perPage,
        );
    }
}
