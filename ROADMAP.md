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

**Phase 1 — Professional Development Environment**
**Lesson 1.2 — Automatic Code Checkers (Pint + PHPStan)**
Status: In progress (started 2026-07-22)

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
| .env configured & verified | 🔄 (Lesson 1.1 in progress) |
| Laravel Pint config | ⬜ |
| PHPStan (Larastan) config | ⬜ |
| Git hooks / pre-commit checks | ⬜ |
| Root README with setup instructions | ⬜ |

### Phase 1 Lessons

| # | Lesson | Status |
|---|--------|--------|
| 1.1 | The .env File (Environment Variables) | ✅ |
| 1.2 | Automatic Code Checkers (Pint + PHPStan) | 🔄 |
| 1.3 | Git Hooks (stop bad code before it's committed) | ⬜ |
| 1.4 | First Real Commit — Laravel skeleton, Docker files, cleanup | ⬜ |

## Phase 2 — Laravel Internals ⬜
## Phase 3 — PHP Advanced ⬜
## Phase 4 — Architecture (Clean Architecture, Repository, Service Layer) ⬜
## Phase 5 — Multi-Tenancy (built from scratch, no packages) ⬜
## Phase 6 — Authentication ⬜
## Phase 7 — Authorization ⬜
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
