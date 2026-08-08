# ADR 004: Workflow Engine — Trigger/Condition/Action Model

**Status:** Accepted
**Date:** 2026-08-08

## Problem

Business automation rules ("when a Deal is won, create a follow-up Activity")
are currently hardcoded directly inside Services. Every tenant may want
different rules, and changing a rule currently means changing code and
redeploying — this does not scale past a handful of tenants.

## Decision

Build a simple rules engine with three parts, stored per-tenant in the
database:

- **Trigger** — a named event string (e.g. `lead.converted`) that marks
  the moment a rule should be checked.
- **Condition** (optional) — a simple, structured check against the data
  involved (e.g. `deal.amount > 10000`).
- **Action** — a named, registered class that performs one specific
  effect (e.g. `CreateActivityAction`), resolved through the Strategy
  pattern (Lesson 9.3) rather than a giant if/else chain.

This phase runs everything **synchronously**, in-process, immediately
after the triggering event — no Queue yet (Queues are Phase 12) and no
Laravel Event/Listener system yet (Phase 13). Those phases will upgrade
this engine to run asynchronously; the trigger/condition/action shape
built here does not need to change when that happens.

## Alternatives Considered

- **A full expression-language rule engine** (parsing arbitrary logic
  strings). Rejected for now — too complex for the current business need,
  and a genuine security risk if not built carefully (arbitrary code
  execution). Revisit only if simple structured conditions prove
  insufficient.
- **Hardcoding new rules per tenant in code.** Rejected — doesn't scale,
  and defeats the purpose of a configurable system.

## Consequences

- Adds a new `workflow_rules` table and a small `Action` registry.
- Every future trigger point (Lead conversion, Deal stage change, etc.)
  needs one line calling into the engine — a cheap, consistent cost.
- Sets up Phase 12 (Queues) and Phase 13 (Events) to plug into this same
  trigger/condition/action shape instead of needing a redesign.
