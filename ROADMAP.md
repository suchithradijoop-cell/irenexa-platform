# IRENEXA Platform — Learning & Build Roadmap

**Company:** IRENEXA Technologies
**Product:** IRENEXA Platform — Enterprise Multi-Tenant CRM SaaS
**Goal:** (1) Interview-ready senior Laravel portfolio, (2) Foundation of a real commercial SaaS
**Pace:** ~2 hours/day, one concept at a time, never rushed

---

## 🗣️ Teaching Style Note (important — read every session)
Use **very simple English**. Short sentences. No fancy words. No heavy jargon.
Explain like talking to a friend, not writing a technical document.
Use everyday examples (shopping, food, daily life) before code examples.

## 📍 Currently On

**Phase 7 — Authorization: ✅ COMPLETE**
**Phase 8 — CRM Core**
Status: Not started — lessons not yet broken down

---

## Status Legend
- ✅ Done
- 🔄 In Progress
- ⬜ Not Started

---

## Phase 0 — Planning & Engineering Mindset 🔄

| # | Lesson | Status |
|---|--------|--------|
| 0.1 | Why Planning Beats Coding (Engineering Mindset) | ✅ |
| 0.2 | Understanding IRENEXA the Product (Domain: CRM/SaaS B2B) | ✅ |
| 0.3 | From Requirements to Architecture (functional vs non-functional requirements) | ✅ |
| 0.4 | Engineering Principles Preview (SOLID, Clean Architecture — conceptual only) | ✅ |
| 0.5 | Professional Git Workflow & Commit Discipline | ✅ |
| 0.6 | Definition of Done & Documentation Discipline (ADRs, README) | ✅ |

**Phase 0 complete.**

## Phase 1 — Professional Development Environment 🔄
Docker (PHP-FPM, Nginx, MySQL 8.4, Redis) already scaffolded. Remaining:

| Item | Status |
|---|---|
| docker-compose.yml (app, nginx, mysql, redis) | ✅ |
| Dockerfile (PHP 8.3-fpm + extensions) | ✅ |
| Nginx config | ✅ |
| Laravel skeleton installed (composer.json, package.json) | ✅ |
| .env configured & verified | ✅ |
| Laravel Pint config | ✅ |
| PHPStan (Larastan) config | ✅ |
| Git hooks / pre-commit checks | ✅ (custom .githooks/, no CaptainHook — Docker couldn't see .git) |
| Root README with setup instructions | ⬜ |
| Docker containers actually running (mysql, redis, nginx, app) | ✅ |
| Laravel skeleton + Docker files committed to git | ✅ |

### Phase 1 Lessons

| # | Lesson | Status |
|---|--------|--------|
| 1.1 | The .env File (Environment Variables) | ✅ |
| 1.2 | Automatic Code Checkers (Pint + PHPStan) | ✅ |
| 1.3 | Git Hooks (stop bad code before it's committed) | ✅ |
| 1.4 | First Real Commit — Laravel skeleton, Docker files, cleanup | ✅ (done ahead, while unblocking Docker) |

**Phase 1 complete.**

## Phase 2 — Laravel Internals 🔄

| # | Lesson | Status |
|---|--------|--------|
| 2.1 | The Request Lifecycle (how a request travels through Laravel) | ✅ |
| 2.2 | The Service Container (auto-building objects for you) | ✅ |
| 2.3 | Service Providers (how features register themselves) | ✅ |
| 2.4 | Facades (what they really are, behind the magic) | ✅ |
| 2.5 | Middleware Deep Dive | ✅ |
| 2.6 | Config & Environment Loading Order | ✅ |

**Phase 2 complete.**
## Phase 3 — PHP Advanced 🔄

| # | Lesson | Status |
|---|--------|--------|
| 3.1 | Interfaces & Abstract Classes | ✅ |
| 3.2 | Traits (reusing code without inheritance headaches) | ✅ |
| 3.3 | Enums (replacing "magic strings") | ✅ |
| 3.4 | Constructor Property Promotion & Readonly Properties | ✅ |
| 3.5 | Exception Handling & Custom Exceptions | ✅ |
| 3.6 | Strict Types & Type Declarations (working with PHPStan) | ✅ |

**Phase 3 complete.**
## Phase 4 — Architecture (Clean Architecture, Repository, Service Layer) 🔄

| # | Lesson | Status |
|---|--------|--------|
| 4.1 | What Is "Architecture," Really? (layers, why fat controllers happen) | ✅ |
| 4.2 | The Repository Pattern | ✅ |
| 4.3 | The Service Layer | ✅ |
| 4.4 | Form Requests & Clean Boundaries Between Layers | ✅ |
| 4.5 | Building a Real Feature End-to-End (Controller + Service + Repository, real code) | ✅ |
| 4.6 | Clean Architecture Recap & IRENEXA Folder Structure | ✅ |

**Phase 4 complete.**
## Phase 5 — Multi-Tenancy (built from scratch, no packages) 🔄

| # | Lesson | Status |
|---|--------|--------|
| 5.1 | What Is Multi-Tenancy, and Which Approach Should We Use? | ✅ |
| 5.2 | Tenants Table & "Who Is The Current Tenant Right Now?" | ✅ |
| 5.3 | Middleware: Resolving the Tenant From the Request | ✅ |
| 5.4 | Global Scopes: Automatically Filtering Every Query by Tenant | ✅ |
| 5.5 | Automatically Stamping New Records with the Tenant ID | ✅ |
| 5.6 | Testing Tenant Isolation & Retrofitting the Lead Feature | ✅ |

**Phase 5 complete.**
## Phase 6 — Authentication 🔄

| # | Lesson | Status |
|---|--------|--------|
| 6.1 | Authentication Fundamentals: Sessions vs Tokens | ✅ |
| 6.2 | Password Hashing & Why We Never Store Plain Passwords | ✅ |
| 6.3 | Building Registration (POST /api/register) | ✅ |
| 6.4 | Building Login & Issuing API Tokens (Laravel Sanctum) | ✅ |
| 6.5 | Protecting Routes: auth:sanctum & Getting the Current User | ✅ |
| 6.6 | Connecting Auth to Multi-Tenancy: Which Tenant Does This User Belong To? | ✅ |

**Phase 6 complete.**
## Phase 7 — Authorization ✅

| # | Lesson | Status |
|---|--------|--------|
| 7.1 | Authentication vs Authorization: The Real Difference | ✅ |
| 7.2 | Laravel Gates: Simple Yes/No Permission Checks | ✅ |
| 7.3 | Laravel Policies: Organizing Permissions Per Model | ✅ |
| 7.4 | Roles: Admin vs Regular User (within a tenant) | ✅ |
| 7.5 | Wiring Policies into Form Requests' authorize() | ✅ |
| 7.6 | Testing Authorization | ✅ |

**Phase 7 complete.**
## Phase 8 — CRM Core ⬜
## Phase 9 — Workflow Engine ⬜
## Phase 10 — API (REST, API-first, Swagger/OpenAPI) ⬜
## Phase 11 — Redis ⬜
## Phase 12 — Queue ⬜
## Phase 13 — Events ⬜
## Phase 14 — Notifications ⬜
## Phase 15 — MongoDB ⬜
## Phase 16 — Search ⬜
## Phase 17 — Payments ⬜
## Phase 18 — AI ⬜
## Phase 19 — Testing (Unit + Feature) ⬜
## Phase 20 — CI/CD (GitHub Actions) ⬜
## Phase 21 — Cloud (AWS S3, deployment) ⬜
## Phase 22 — Monitoring ⬜
## Phase 23 — Performance ⬜
## Phase 24 — System Design ⬜
## Phase 25 — Commercial SaaS ⬜

---

## Session Log

| Date | Lesson | Summary |
|---|---|---|
| 2026-07-19 | Roadmap created | Audited repo: Laravel skeleton + Docker env present, no custom app code or lesson history existed. Roadmap created to track progress going forward. |
| 2026-07-19 | Lesson 0.1 done | Engineering mindset (think before coding). Re-taught in simple English per user request. Homework: write Problem/What-could-go-wrong for one small feature. |
| 2026-07-19 | Lesson 0.2 done | What IRENEXA is: CRM, B2B, SaaS, multi-tenancy (apartment building analogy). Homework: list of things IRENEXA should store for a sales team. |
| 2026-07-19 | Lesson 0.3 done | Functional vs non-functional requirements (restaurant analogy). Homework: 2 functional + 2 non-functional needs for "add customer" feature. |
| 2026-07-19 | Lesson 0.4 done | SOLID + Clean Architecture preview (kitchen analogy). Homework: find which SOLID rule the Lesson 0.1 bad export code breaks. |
| 2026-07-19 | Lesson 0.5 done | Git workflow: commit message format (type: description), branching (draft copy analogy). Homework: write 3 example commit messages. |
| 2026-07-19 | Lesson 0.6 done | Definition of Done + ADRs (documentation). Phase 0 complete (6/6 lessons). Homework: write ADR for choosing Docker. |
| 2026-07-22 | Repo audit | Found real git history exists (2 commits) but Laravel app + Docker files are still untracked. Found a stray src/error.txt (just a saved copy of the homepage HTML, safe to remove). Found REDIS_HOST=127.0.0.1 in .env — wrong for Docker, fixed to REDIS_HOST=redis. Also set APP_NAME=IRENEXA. |
| 2026-07-22 | Lesson 1.1 done | .env explained, real bug found & fixed (REDIS_HOST). |
| 2026-07-22 | Lesson 1.2 done | Pint + PHPStan (Larastan) explained and configured (level 5). User ran composer update, docker compose up --build -d — got everything running. Pint: PASS. PHPStan: no errors. |
| 2026-07-22 | Lesson 1.4 done (early) | Committed everything properly in 5 clean, topic-based commits via GitHub Desktop: docker environment, laravel skeleton, code quality tooling, roadmap, gitignore fix + composer.lock. Also found and fixed a real gap: .gitignore was missing storage/framework/* cache exclusions — 40+ compiled Blade view cache files + PHPStan cache almost got committed. |
| 2026-07-22 | Lesson 1.3 done | Tried CaptainHook (composer-based git hooks) — failed because Docker container can't see the host's .git folder, and PHP only exists inside Docker while Git hooks trigger on the host. Pivoted to plain shell scripts in .githooks/ + `git config core.hooksPath .githooks`. Also fixed a GitHub Desktop auth mismatch (signed in as wrong account, blocking push). **Phase 1 complete.** |
| 2026-07-22 | Phase 2 complete | Request Lifecycle, Service Container, Service Providers, Facades, Middleware, Config/env loading — all concept lessons, no code changes. |
| 2026-07-22 | Phase 3 complete | Interfaces, Traits, Enums, Constructor Promotion & Readonly, Exceptions, Strict Types — all concept lessons, no code changes. |
| 2026-07-22 | Lesson 4.5 done | Built the first real end-to-end feature: LeadStatus enum, leads migration, Lead model, LeadRepositoryInterface + EloquentLeadRepository, RepositoryServiceProvider (new, bound in bootstrap/providers.php), LeadService, StoreLeadRequest, Api\LeadController, routes/api.php (new — also wired into bootstrap/app.php's withRouting). Also fixed port conflicts along the way: MySQL 3306→3307 (XAMPP local MySQL conflict) and Nginx 80→8080 (XAMPP Apache conflict). Tested live via curl POST /api/leads — full chain confirmed working, status auto-set to "new" by the business rule. |
| 2026-07-22 | Phase 4 complete | Lesson 4.6 recap + real Customer-feature homework plan. Full architecture (Route→Form Request→Controller→Service→Repository→DB) proven end-to-end with the Lead feature. |
| 2026-07-22 | Lesson 5.1 done | ADR 002 written and saved (docs/adr/002-multi-tenancy-strategy.md) — decided shared database, shared schema for tenant isolation. |
| 2026-07-22 | Lesson 5.2 done | Built tenants table, Tenant model, TenantContext singleton (registered in AppServiceProvider). |
| 2026-07-22 | Lesson 5.3 done | Built ResolveTenant middleware (X-Tenant header), aliased as 'tenant', applied to lead routes. Tested live: missing header → 400, valid header → passes through. |
| 2026-07-22 | Lesson 5.4 done | Added tenant_id to leads (new migration + FK), built TenantScope + BelongsToTenant trait, attached to Lead model. Hit and resolved a real partial-migration failure (orphaned column from a failed FK constraint) and a Fillable-blocked mass assignment (fixed with forceCreate). Proved tenant isolation live: two tenants, two leads, each request only ever saw its own tenant's data. |
| 2026-07-22 | Lesson 5.5 done | Extended BelongsToTenant to auto-stamp tenant_id on creation via the `creating` model event. Hit a real PHPStan finding (undefined property on generic Model type in the trait closure) — fixed using getAttribute()/setAttribute() instead of magic property access. Two-layer defense confirmed: Form Request strips unexpected tenant_id from input, trait auto-stamps the trusted one. |
| 2026-07-22 | Lesson 5.6 done | Added TenantFactory, LeadFactory, and TenantIsolationTest (2 feature tests: cross-tenant read isolation, anti-spoofing on create). Hit a real test failure — direct `create()` in test setup was silently stripped by Fillable protection, same gotcha as Lesson 5.4's Tinker tests — fixed with `forceCreate()`. All 4 tests passing. **Phase 5 complete — multi-tenancy built from scratch, proven live and automatically tested.** |
