# ADR 003: CRM Core Domain Model (Contacts, Companies, Deals)

**Status:** Accepted
**Date:** 2026-08-01

## Problem

We only have a `Lead` — a flat record for someone who might be interested.
Once a Lead becomes a real prospect, we need to track more: the person, the
company they work at, and the actual sales opportunity (amount + stage).
One flat table cannot represent this without heavy duplication.

## Decision

Introduce three new entities:

- **Company** — the business. One Company has many Contacts.
- **Contact** — a real person, tied to one Company.
- **Deal** — a sales opportunity. Belongs to one Contact and one Company.
  Has a `stage` (enum: prospecting, negotiation, won, lost) and an amount.

All three are tenant-scoped, using the same `BelongsToTenant` trait built in
Phase 5 — no exceptions, no new isolation mechanism needed.

## Alternatives Considered

- **Put company_name / contact_name directly on Deal as plain text fields.**
  Rejected — causes duplicate/typo'd company names across deals, and updating
  a company's name would require updating every deal individually.
- **One big "Account" table combining Company + Contact.**
  Rejected — a company can have many contacts (multiple people you talk to
  at the same business); merging them loses that real-world structure.

## Consequences

- More tables and relationships to maintain than a single flat Lead table.
- Requires deciding (Lesson 8.7) how a Lead becomes a Contact + Company + Deal
  — this conversion logic needs its own Service, not ad-hoc code.
- Sets up Phase 8.8 (Activities) to attach notes/calls/tasks to any of these
  three entities via a single polymorphic relationship, instead of three
  separate "notes" tables.
