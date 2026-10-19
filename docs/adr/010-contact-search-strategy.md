# ADR 010: Contact Search: MySQL FULLTEXT by Default, Meilisearch Behind an Interface

**Status:** Accepted
**Date:** 2026-10-26

## Problem

Users need to find contacts by typing part of a name or email.
`WHERE name LIKE '%x%'` cannot use a normal index (it scans every row), has
no relevance ordering, and tolerates no typos. We also have to keep search
tenant-safe: a result must never contain another company's contact.

## Decision

1. **Search sits behind a `ContactSearcher` interface**, separate from
   `ContactRepositoryInterface`. Storing contacts and searching them are
   different responsibilities with different technologies.
2. **`SEARCH_DRIVER` selects the engine.** One container binding reads it;
   an unknown value throws (same approach as `AUDIT_STORE`, ADR 009).
   - `mysql` (default): FULLTEXT index on `name, email, job_title`.
   - `meilisearch`: a separate engine with typo tolerance and ranking.
3. **User text never reaches the engine as syntax.** `SearchQuery` keeps
   only letters and digits and rebuilds the operators itself, so boolean-mode
   operators cannot be injected. The text is always a bound parameter.
4. **Tenant safety does not depend on the engine.**
   - MySQL: `Contact::query()` carries `TenantScope`.
   - Meilisearch: every document stores `tenant_id` (a filterable
     attribute); the filter is added in one place, from `TenantContext`,
     never from the request; with no tenant the searcher returns nothing
     (fail closed); final rows are loaded from MySQL through `TenantScope`,
     so even a leaked index id is dropped.
5. **MySQL is the source of truth; the index is a rebuildable copy.** A
   `ContactObserver` queues `SyncContactToSearchIndexJob` after commit. The
   job carries IDs only, sets the tenant, reads the current row, and is
   idempotent. `php artisan search:setup --reindex` repairs any drift.
6. **Meilisearch is called through its REST API with Laravel's HTTP
   client**, not an SDK or Laravel Scout, so every call is explicit and
   there are no new dependencies. Calls throw on failure so jobs retry.
7. **Tests need no engine.** The suite pins `SEARCH_DRIVER=mysql` (SQLite
   uses a LIKE fallback), and the Meilisearch path is tested with
   `Http::fake()` by asserting what we send and how we handle replies.

## Alternatives Considered

- **Keep `LIKE '%x%'`.** Rejected: full scans, no ranking.
- **Meilisearch/Elasticsearch only.** Rejected as the default: a new service
  to run for a need that FULLTEXT covers at our current size.
- **Laravel Scout.** A good package, but it hides the queue, tenant and
  sync details we need to understand and explain. Revisit if we need many
  searchable models.
- **One index per tenant.** Stronger isolation, but index management grows
  with the tenant count. Shared index plus a mandatory filter was chosen.

## Consequences

- Eventual consistency: after a change, search can lag by the queue delay
  (and by the length of any Horizon outage).
- A second system to run, secure and monitor when `meilisearch` is used.
  Production needs a real master key and a private network.
- The SQLite fallback differs slightly from FULLTEXT; real FULLTEXT is
  verified manually and, if CI gets a MySQL service, by an opt-in test.
- Moving to Elasticsearch later means one new `ContactSearcher`
  implementation and one new sync client; the rest of the app is untouched.
