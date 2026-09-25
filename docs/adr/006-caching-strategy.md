# ADR 006: Caching via a Repository Decorator, Not Inline in the Service

**Status:** Accepted
**Date:** 2026-09-16

## Problem

Read-heavy endpoints (e.g. `GET /api/v1/contacts`) hit MySQL on every request
even when the underlying data hasn't changed between requests. As traffic
grows, this is unnecessary database load for data that's safe to serve
slightly stale (seconds to a few minutes old).

We need a caching strategy that: (1) doesn't require every Service to
remember to cache correctly, (2) doesn't leak one tenant's cached data to
another, and (3) doesn't permanently couple our data-access code to Redis
specifically.

## Decision

Cache at the Repository layer, using the Decorator Pattern: a new
`CachedContactRepository` implements `ContactRepositoryInterface`, wraps a
real `EloquentContactRepository` instance, and adds a cache-aside read
(`Cache::remember()`) around the one method worth caching (`paginate()`).
`RepositoryServiceProvider` binds the interface to the decorator instead of
the plain Eloquent implementation, so every consumer of the interface
(`ContactService`, and anything built against it later) gets caching for
free with no code changes on their side.

Every cache key includes the current tenant ID
(`tenant:{id}:contacts:page:{page}:per_page:{perPage}`), sourced from
`TenantContext`, with the same fail-closed fallback (`-1`) TenantScope uses
when the tenant is somehow unresolved.

## Alternatives Considered

- **Cache inline inside `ContactService`.** Rejected — mixes business logic
  with an infrastructure concern, and any other Service reading contacts
  later would have to remember to add the same caching logic itself, with
  no guarantee it gets the tenant-scoped key right.
- **Cache inside `EloquentContactRepository` directly.** Rejected — that
  class's only job should be "how do we talk to MySQL." Adding caching
  there means every future change to caching strategy (a different TTL
  rule, disabling caching in tests) requires editing the class every other
  repository method also depends on.
- **A global HTTP-response cache (e.g. cache the whole JSON response by
  URL).** Rejected for now — simpler to reason about at first glance, but
  much harder to invalidate correctly per-tenant, and it caches the
  Resource-shaped response rather than the underlying paginator, coupling
  the cache to the API's current JSON shape.

## Consequences

- Adding caching to another resource (Companies, Deals) means writing one
  more decorator class + one changed binding — the existing
  `EloquentCompanyRepository`/`EloquentDealRepository` classes and their
  Services are untouched. This is the Open/Closed Principle in practice.
- Cache invalidation (what happens on create/update) is handled separately
  (Lesson 11.4) — caching reads and invalidating on writes are two distinct
  concerns, each with its own failure mode, and are deliberately not
  solved in the same class.
- If Redis is ever swapped for another cache backend, only Laravel's
  `config/cache.php` changes — `CachedContactRepository` calls the `Cache`
  facade, not Redis directly, so it doesn't know or care which store is
  behind it.
