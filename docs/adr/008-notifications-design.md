# ADR 008: Notifications — Channels, Recipients and Inbox Isolation

**Status:** Accepted
**Date:** 2026-10-22

## Problem

Tenant admins need to know when something important happens (a lead is
converted into a deal). We need a way to tell people that does not slow
the original request, does not leak across tenants, respects a user's
choice about email, and gives the client an in-app inbox.

## Decision

1. **Notifications are a reaction, not part of the business transaction.**
   `NotifyAdminsOfDealCreated` is a third listener on `LeadConverted`
   (ADR 007); the conversion service knows nothing about it.
2. **Two channels from one notification class.** `DealCreatedNotification`
   writes the in-app inbox (database channel, always) and sends email
   (mail channel, only if the recipient's `notify_by_email` is true). The
   choice is made per recipient inside `via()`.
3. **The notification itself is queued.** Each recipient and channel is an
   independent job, so a failed email is retried alone and nobody is
   notified twice. Payloads are plain values, never tenant-scoped models.
4. **Recipients are chosen with an explicit tenant filter.** `User` has no
   global tenant scope (authentication must find a user before the tenant
   is known), so `NotifyAdminsOfDealCreated` filters by tenant and role
   itself, and a test guards it.
5. **The `notifications` table has no `tenant_id`.** A notification belongs
   to one user and a user to one tenant; isolation follows the recipient.
6. **The inbox API never takes an owner from the URL.** The owner is the
   authenticated user; every repository method requires a `User`, and a
   foreign notification id returns 404.

## Alternatives Considered

- **Send notifications synchronously from the service.** Couples business
  logic to delivery and makes slow mail slow the request.
- **One queued job notifying all admins.** A failure on one recipient
  retries the whole job and duplicates mail for the others.
- **Add `tenant_id` to `notifications`.** Redundant today; revisit if a
  tenant-wide notification feed is ever needed (a join through users works
  until then).
- **`/users/{id}/notifications`.** Invites ID-swapping (IDOR) for no gain.

## Consequences

- New notifications are additive: a notification class plus a listener.
- Local email is caught by Mailpit; no mail can leave a development
  machine.
- Every recipient-selection query on a model without a global tenant scope
  must filter by tenant explicitly and have an isolation test.
