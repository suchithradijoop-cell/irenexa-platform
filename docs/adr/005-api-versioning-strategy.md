# ADR 005: API Versioning via URL Prefix

**Status:** Accepted
**Date:** 2026-08-10

## Problem

The API currently has no version marker at all (`/api/leads`, not
`/api/v1/leads`). Once real clients (a frontend, a mobile app, third-party
integrations) depend on this API's JSON shape, any breaking change to a
response would silently break every one of them with no warning.

## Decision

Prefix every route with `/api/v1`. When a genuinely breaking change is
needed later (removing a field, changing a field's meaning), it ships as
`/api/v2`, and `/api/v1` keeps working unchanged until clients migrate.

## Alternatives Considered

- **Header-based versioning** (`Accept: application/vnd.irenexa.v1+json`).
  More "correct" by some REST purists' standards, but harder to test by
  hand (curl, Postman defaults), harder to see at a glance in logs, and
  overkill for a project at this stage.
- **No versioning, just be careful not to break things.** Rejected —
  "be careful" is not a strategy. Every real API eventually needs to make
  a breaking change; deciding the mechanism now, before any real clients
  exist, is far cheaper than retrofitting it later.

## Consequences

- All route definitions move under a `/v1` prefix (Lesson 10.4).
- Every future breaking change has a clear, pre-agreed home (`/v2`)
  instead of becoming an ad-hoc decision made under pressure.
- Non-breaking changes (adding a new optional field, adding a new
  endpoint) never require a new version — only removals or meaning
  changes do.
