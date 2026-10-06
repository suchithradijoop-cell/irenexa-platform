# ADR 007: Domain Events, and Tenant Context in Asynchronous Code

**Status:** Accepted
**Date:** 2026-10-14

## Problem

`LeadConversionService` called the WorkflowEngine directly. Every new
reaction to "a lead was converted" (audit trail, notifications) would have
required editing that service and adding another dependency to it.

Moving reactions onto queue workers created a second, security-relevant
problem: tenant isolation depends on `TenantContext`, which is set by HTTP
middleware. A queue worker has no HTTP request, so no middleware runs, and
the worker is a long-lived process handling many jobs.

## Decision

1. **Announce facts with events.** The service dispatches `LeadConverted`
   (past tense, immutable, `readonly` properties) and knows nothing about
   who reacts. Reactions are listeners: `FireWorkflowRules`, `WriteAuditLog`.
   Adding a reaction means adding a file, not editing the service.
2. **Queue the listeners and wait for commit.** Listeners implement
   `ShouldQueue` and `ShouldHandleEventsAfterCommit`, with retries and
   backoff, so a slow or failing reaction never blocks or undoes the
   conversion.
3. **Events and jobs carry plain IDs, never Eloquent models.** A model in a
   queued payload is re-fetched when it is unserialized, before the handler
   runs, through a fail-closed `TenantScope` with no tenant, and returns
   nothing.
4. **Async code establishes its own tenant first**, via
   `TenantContext::setById()`, before loading any tenant-scoped record.
5. **`TenantContext` is a scoped binding, not a singleton**, so it is reset
   after every request and every queued job; one job's tenant can never leak
   into the next job in the same worker process.

## Alternatives Considered

- **Keep direct calls in the service.** Simple, but every new reaction edits
  and enlarges the service (violates Open/Closed).
- **Pass models in events (`SerializesModels`).** Convenient, but breaks
  under tenant scoping as described above.
- **A tenant-aware queue middleware that restores context automatically.**
  Attractive and likely a future refactor; deferred to avoid hidden magic
  while the team is still learning the flow.

## Consequences

- New reactions are additive and independently retried; one failing
  listener does not affect the others.
- Indirection: reading the service no longer shows what happens next.
  Mitigated by `php artisan event:list`, and by `LeadConvertedEventTest`
  asserting both listeners stay registered.
- Every new async handler must call `setById()` first. A forgotten call
  fails closed (no rows) rather than leaking, because of `TenantScope`.
