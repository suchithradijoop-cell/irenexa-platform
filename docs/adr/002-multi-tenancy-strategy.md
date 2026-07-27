# ADR 002: Use Shared Database, Shared Schema for Multi-Tenancy

**Status:** Accepted
**Date:** 2026-07-22

## Problem

How do we isolate each company's (tenant's) data from every other tenant's data,
so that no tenant can ever see or modify another tenant's records?

## Constraints

- Must be affordable to run and operate as a solo founder.
- Must be simple enough to build, understand, and maintain alone.
- Needs a strong, enforceable guarantee against data leaks between tenants.
- Project goal requires building the isolation mechanism ourselves, not relying
  on a third-party package, so the internals are fully understood.

## Options Considered

**(a) Shared database, shared schema** — one database, one set of tables, every
tenant-owned table has a `tenant_id` column. Isolation is enforced in application
code (scopes, middleware).

**(b) Shared database, separate schema per tenant** — one database server, but
each tenant gets its own schema/namespace of tables. Stronger isolation, but
migrations and maintenance must be repeated per schema.

**(c) Separate database per tenant** — each tenant gets an entirely separate
database. Maximum isolation, but expensive and operationally heavy at scale.

## Decision

**(a) Shared database, shared schema.**

This is the industry-standard starting point for B2B SaaS products at this
stage, it is the cheapest to run, and — most importantly for this project — it
is the approach where the isolation logic is written by hand in application
code (global scopes, middleware, model events), rather than delegated to
infrastructure. That matches the explicit goal of understanding multi-tenancy
internals rather than installing a package that hides them.

## Consequences

- Every tenant-owned table must include a `tenant_id` column.
- Every query against a tenant-owned table must be automatically scoped to the
  current tenant — this must be enforced by the framework layer (Eloquent
  global scopes), not left to individual developers to remember on each query.
- A single missed scope is a real security bug (cross-tenant data leak), so
  this mechanism must be tested explicitly (see Lesson 5.6).
- If IRENEXA later needs stronger isolation for enterprise customers, this
  decision can be revisited — some tenants could be moved to option (b) or (c)
  without changing the rest of the application, since the isolation logic is
  already centralized behind scopes and repositories.
