# ADR 009: MongoDB for the Audit Stream, Behind a Repository Interface

**Status:** Accepted
**Date:** 2026-10-24

## Problem

The audit stream (what happened, to which record, with what details) was
stored in a MySQL table. Its profile differs from the rest of the data: it
is append-only, grows without bound, never needs joins or updates, and each
event type carries a different payload shape. We wanted to see whether a
document database fits that profile better, without betting the whole
application on a second database.

## Decision

1. **Relational data stays in MySQL** (leads, contacts, companies, deals,
   users, notifications). It needs joins, constraints and transactions.
2. **The audit stream may live in MongoDB**, as an `AuditEntry` document in
   the `audit_entries` collection, with a compound index on
   `{tenant_id: 1, created_at: -1}` that matches the only query shape
   (one tenant, newest first).
3. **The store is hidden behind `AuditLogRepositoryInterface`.** Two
   implementations (`EloquentAuditLogRepository`, `MongoAuditLogRepository`)
   are selected by `AUDIT_STORE` in `config/audit.php`; the container
   binding is the single place that reads it. An unknown value throws.
   Switching stores is an environment change, not a code change.
4. **Tenant isolation does not depend on the store.** Both models share the
   `BelongsToTenant` trait: a global scope filters every query and the
   tenant is stamped on create. MongoDB has no row-level security, so the
   application remains responsible, and an integration test proves it.
5. **The timeline API (`GET /api/v1/audit`) is store-agnostic.** Filters
   travel in an immutable `AuditFilter` whose `applyTo()` uses only the
   query API both stores share. Inputs are validated as plain scalars, which
   rejects operator-shaped values such as `?event[$ne]=x` (NoSQL injection).
6. **Index creation is an explicit, idempotent command**
   (`audit:ensure-indexes`), not a migration, because migrations also run in
   environments and test suites that have no MongoDB.
7. **Tests do not require MongoDB by default.** The suite pins
   `AUDIT_STORE=mysql`; binding behaviour is tested without a connection;
   a real-MongoDB integration test runs only when `RUN_MONGO_TESTS=1`.

## Alternatives Considered

- **Keep everything in MySQL with a JSON column.** Simpler, and entirely
  adequate at this scale. Rejected as the *only* option because it would
  leave Phase 15's question (when does a document store earn its cost?)
  unanswered, but it remains the default (`AUDIT_STORE=mysql`).
- **Move all data to MongoDB.** Rejected: the core data is relational.
- **Write to both stores.** Rejected: dual writes can diverge and need
  reconciliation logic we have no need for.

## Consequences

- A second database to run, back up, monitor and secure. This cost is only
  justified at a much larger event volume; until then MySQL is the sensible
  default and MongoDB is an opt-in.
- No foreign keys or cascading deletes across the two stores: deleting a
  tenant no longer removes its audit documents automatically, so retention
  and cleanup become an explicit job.
- Entries from the two stores are not mixed: switching `AUDIT_STORE` starts
  a new history in the other store. A migration/backfill would be required to
  consolidate, and is not needed yet.
- Local development gains `mongodb` and `mongo-express` services; the PHP
  image installs the `mongodb` extension from a pinned tarball because PEAR's
  downloader crashes on PHP 8.3.
