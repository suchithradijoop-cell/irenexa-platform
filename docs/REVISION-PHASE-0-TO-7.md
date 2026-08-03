# IRENEXA — Full Revision: Phase 0 to Phase 7

This is a study document. It covers everything we built and learned from
Phase 0 through Phase 7, in the same simple-English style used in every
lesson. Use this to revise before interviews, or any time you need to
remember "why did we build it this way?"

---

## Phase 0 — Planning & Engineering Mindset (concepts only, no code)

**The big idea:** think before you code. A senior engineer spends more time
understanding the problem than typing.

- **0.1 Engineering Mindset** — before writing any feature, ask "what is the
  problem?" and "what could go wrong?" Jumping straight to code is how bugs
  and rework happen.
- **0.2 What IRENEXA Is** — a B2B, multi-tenant CRM SaaS. "Multi-tenant"
  means many companies (tenants) share the same app and database, but each
  one's data is invisible to the others — like an apartment building: one
  building, separate locked apartments.
- **0.3 Requirements** — functional requirements are *what the system must
  do* ("a user can add a customer"). Non-functional requirements are *how
  well it must do it* ("must respond within 200ms", "must not leak data
  between tenants").
- **0.4 SOLID & Clean Architecture (preview)** — a preview of the rules we'd
  apply for real starting in Phase 4: Single Responsibility, dependency
  direction, separating "what to do" from "how to do it."
- **0.5 Git Workflow** — commit message format `type: description` (e.g.
  `feat:`, `fix:`, `chore:`, `docs:`, `test:`). Small, topic-based commits,
  not one giant commit.
- **0.6 Definition of Done & ADRs** — a feature isn't "done" until it's
  tested, documented, and reviewed. An ADR (Architecture Decision Record)
  writes down *why* we chose something, so future-you (or an interviewer)
  can see the reasoning, not just the result.

**Interview soundbite:** "I plan and document decisions before coding
because it's cheaper to change a plan than to rewrite shipped code."

---

## Phase 1 — Professional Development Environment

**The big idea:** a consistent, automated environment catches mistakes
before they reach production — and before a human has to notice them.

### Docker

We run the app inside containers instead of installing PHP/MySQL/Redis
directly on the machine. Why: "works on my machine" problems disappear,
because everyone (and CI) runs the exact same environment.

```yaml
# docker-compose.yml (relevant parts)
services:
  app:      # PHP-FPM 8.3 — runs our Laravel code
  nginx:    # web server — receives HTTP requests, hands PHP files to app
  mysql:    # MySQL 8.4 — our database
  redis:    # Redis 7 — cache/queues (used from Phase 11 onward)
  adminer:  # a browser-based database viewer, for convenience
```

Host ports were changed (`3307:3306` for MySQL, `8080:80` for nginx)
because XAMPP was already using the standard ports 3306/80 on the host
machine. Inside Docker's own network, containers still talk to each other
on the normal ports (`mysql:3306`) — only the *host-facing* door number
changed.

### Code quality tools

- **Laravel Pint** — auto-formats code style (spacing, brackets) so every
  file looks the same regardless of who wrote it.
- **PHPStan (via Larastan)** — a static analyzer. It reads your code
  *without running it* and finds real bugs: wrong types, undefined
  properties, impossible comparisons. We run it at **level 5** — strict
  enough to matter, not so strict it drowns us in noise while learning.

### Git hooks — built by hand

We tried a Composer package (CaptainHook) first. It failed because Docker
only mounts the `src/` folder, not the parent `.git/` folder — and Git
hooks run on the *host*, where PHP doesn't even exist (PHP only lives
inside Docker). So we built plain shell scripts instead:

```sh
# .githooks/pre-commit
echo "Running Pint (code style check)..."
docker compose exec -T app vendor/bin/pint --dirty
if [ $? -ne 0 ]; then
    echo "Pint made changes or found a problem. Review, add, commit again."
    exit 1
fi

echo "Running PHPStan (bug check)..."
docker compose exec -T app vendor/bin/phpstan analyse --no-progress --memory-limit=512M
if [ $? -ne 0 ]; then
    echo "PHPStan found a problem. Fix it before committing."
    exit 1
fi

exit 0
```

`docker compose exec -T app ...` runs a command *inside* the running `app`
container from the host — this is how a host-triggered Git hook can still
use PHP tools that only exist in Docker. Activated with
`git config core.hooksPath .githooks`.

**Interview soundbite:** "I automated code quality checks into the commit
process itself, so bad code physically cannot be committed — it's not a
matter of remembering to run a linter."

---

## Phase 2 — Laravel Internals (concepts only, no new code)

**The big idea:** understand what Laravel is doing *before* your code runs,
so debugging isn't guesswork.

- **Request Lifecycle** — every HTTP request goes through one entry point,
  gets matched to a route, passes through middleware (layers of checks),
  reaches a controller, and a response goes back out through those same
  middleware layers.
- **Service Container** — Laravel's "auto-assembler." When a class needs
  another class (a dependency), you type-hint it in the constructor, and
  Laravel builds and hands it to you automatically. This is called
  **Dependency Injection** — the alternative (`new SomeClass()` typed
  directly inside your code) hard-wires the class to one specific
  implementation and makes testing very hard.
- **Service Providers** — the "setup code" that runs before your app
  handles any request. `register()` is for *declaring* bindings (which
  concrete class to use for an interface); `boot()` is for code that needs
  everything else already registered first (e.g. defining Gates).
- **Facades** (`Route::`, `Hash::`, `Gate::`) — these look like static
  method calls, but they're not really static. Behind the scenes, a Facade
  asks the Service Container for a real object and forwards the call to it
  via PHP's `__callStatic` magic method. Convenient syntax, real object
  underneath.
- **Middleware** — code that runs *before* (and sometimes after) your
  controller, for cross-cutting concerns that apply to many routes:
  authentication (`auth:sanctum`), our own `ResolveTenant` (`tenant`).
- **Config / .env loading** — `.env` holds environment-specific secrets and
  settings (never committed to Git); `config/*.php` files read from `.env`
  with safe defaults, and this is what your code should actually reference
  (`config('app.name')`, not `env('APP_NAME')` directly, outside of config
  files) so config caching works correctly in production.

**Interview soundbite:** "I understand Laravel's request lifecycle well
enough to know exactly where to add a check — middleware for
cross-cutting concerns, Form Requests for input validation, Policies for
per-record authorization — instead of stuffing everything into the
controller."

---

## Phase 3 — PHP Advanced (concepts only, no new code)

**The big idea:** these language features aren't decoration — each one
exists to prevent a specific category of bug.

- **Interfaces** — a contract: "any class implementing this interface must
  have these methods." Lets us write code against the *interface*
  (`LeadRepositoryInterface`) instead of a specific class, so swapping
  the real implementation later doesn't break anything depending on it.
- **Traits** — reusable chunks of behavior you can mix into multiple
  unrelated classes (`BelongsToTenant` is used by any model that needs
  tenant isolation, without repeating that logic in every model).
- **Enums** (backed by `string`) — a fixed, named list of allowed values
  (`LeadStatus::New`, `UserRole::Admin`). Prevents typos like `'admind'`
  that a plain string could never catch, and PHP/PHPStan can verify every
  case is handled.
- **Constructor Property Promotion + `readonly`** — `public function
  __construct(protected LeadRepositoryInterface $leads) {}` declares,
  types, and assigns a property in one line instead of four. `readonly`
  means a property can be set once (in the constructor) and never
  changed again — protects against accidental mutation.
- **Custom Exceptions** — throwing a specific, named exception
  (`ValidationException`, not generic `Exception`) lets calling code (or
  Laravel itself) catch and handle that *specific* failure differently
  from any other error.
- **`declare(strict_types=1)` + type declarations** — without this, PHP
  silently converts types for you (`"5"` becomes `5`). With it, a type
  mismatch throws immediately instead of quietly corrupting data. This is
  also required for PHPStan to reason about your code accurately.

**Interview soundbite:** "I use enums instead of raw strings for anything
with a fixed set of valid values, because it moves a whole category of bug
— typos in status strings — from runtime to write-time."

---

## Phase 4 — Architecture: the Lead feature, end-to-end

**The big idea:** every feature follows the same shape —
**Route → Form Request → Controller → Service → Repository → Model** —
so any two engineers can predict where to find (or add) any piece of logic.

```
Route::post('/leads', [LeadController::class, 'store']);
        │
        ▼
StoreLeadRequest   (validates input, decides IF this request is allowed at all)
        │
        ▼
LeadController::store()   (thin — just calls the Service, formats the response)
        │
        ▼
LeadService::createLead()   (business rules live HERE, not in the controller)
        │
        ▼
LeadRepositoryInterface → EloquentLeadRepository   (the only place touching Eloquent directly)
        │
        ▼
Lead model → leads table
```

### Why split it this way (SOLID, in practice)

- **Single Responsibility** — the controller's only job is to translate
  HTTP in and out. It has no idea *how* a lead gets created.
- **Dependency Inversion** — `LeadService` depends on
  `LeadRepositoryInterface` (an interface), never on
  `EloquentLeadRepository` (the concrete class) directly. If we ever
  switched database engines, only the Repository changes — the Service
  and Controller never would.

### The code

```php
// app/Repositories/Contracts/LeadRepositoryInterface.php
interface LeadRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Lead;
    public function create(array $data): Lead;
}
```
The contract. Anything that wants to work with leads talks to *this*, not
to Eloquent directly.

```php
// app/Repositories/EloquentLeadRepository.php
class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function all(): Collection      { return Lead::all(); }
    public function find(int $id): ?Lead   { return Lead::find($id); }
    public function create(array $data): Lead { return Lead::create($data); }
}
```
The only file in the whole app allowed to call `Lead::` directly for
these operations. Everyone else goes through the interface.

```php
// app/Providers/RepositoryServiceProvider.php
public function register(): void
{
    $this->app->bind(LeadRepositoryInterface::class, EloquentLeadRepository::class);
    $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
}
```
This is the ONE line that connects the interface to its real
implementation. Change this line, and every class using
`LeadRepositoryInterface` gets a different implementation with zero other
changes — that's the entire point of Dependency Inversion.

```php
// app/Services/LeadService.php
class LeadService
{
    public function __construct(protected LeadRepositoryInterface $leads) {}

    public function createLead(array $data): Lead
    {
        // Business rule: every new lead always starts as "New" status,
        // no matter who creates it or how (web form, API, import).
        $data['status'] = LeadStatus::New;
        return $this->leads->create($data);
    }
}
```
The business rule ("new leads always start as New") lives in exactly one
place. Whether the request came from a web form, a public API, or a bulk
CSV import in the future, this rule always applies — because they'd all
call this same method.

```php
// app/Http/Requests/StoreLeadRequest.php
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
```
`authorize()` runs before `rules()` — a security guard before the
receptionist. `rules()` defines what "valid data" means for this one
endpoint.

```php
// app/Http/Controllers/Api/LeadController.php
class LeadController extends Controller
{
    public function __construct(protected LeadService $leadService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->leadService->listAll());
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead($request->validated());
        return response()->json($lead, 201);
    }
}
```
Deliberately thin. No SQL, no business rules, no validation logic — just
"take validated input, hand it to the Service, return a response." This is
what "no fat controllers" means in practice.

```php
// app/Models/Lead.php
#[Fillable(['name', 'email', 'phone', 'status'])]
class Lead extends Model
{
    use HasFactory, BelongsToTenant;

    protected function casts(): array
    {
        return ['status' => LeadStatus::class];
    }
}
```
`#[Fillable(...)]` is an allow-list for mass assignment — only these
fields can ever be set via `Lead::create($arrayFromUserInput)`. Anything
not listed (like `tenant_id`) is silently dropped, even if a malicious
request tries to include it. `casts()` turns the raw `status` string from
the database into a real `LeadStatus` enum automatically whenever you read
`$lead->status`.

**Interview soundbite:** "Controllers only translate HTTP to method calls.
Business rules live in Services. Database access lives behind Repository
interfaces. This means I can unit-test business logic without a database,
and swap persistence without touching business logic."

---

## Phase 5 — Multi-Tenancy (built from scratch, no package)

**The big idea:** every tenant's data must be automatically, unforgettably
isolated — not something a developer has to remember to filter on every
single query.

### ADR 002 decision

We chose **shared database, shared schema**: one database, one `leads`
table, one `users` table — every tenant-owned row just has a `tenant_id`
column. The alternative (a separate database per tenant) is far more
expensive to operate and much harder for a solo founder to maintain.

### The four pieces

```php
// app/MultiTenancy/TenantContext.php
class TenantContext
{
    protected ?Tenant $tenant = null;
    public function set(Tenant $tenant): void { $this->tenant = $tenant; }
    public function get(): ?Tenant { return $this->tenant; }
    public function id(): ?int { return $this->tenant?->id; }
}
```
A simple box that holds "which tenant is this request for?" Registered as
a **singleton** (one shared instance per request, not a fresh one every
time it's asked for) in `AppServiceProvider`, so every part of the app
reads the same answer.

```php
// app/Http/Middleware/ResolveTenant.php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (! $user || ! $user->tenant_id) {
        abort(403, 'This account is not linked to a tenant.');
    }

    $tenant = Tenant::find($user->tenant_id);
    if (! $tenant) {
        abort(404, 'Tenant not found.');
    }

    $this->tenantContext->set($tenant);
    return $next($request);
}
```
This runs on every protected route, early. It figures out the tenant from
the **logged-in user** (never from a client-supplied header — a header can
be faked; a verified login token cannot) and stores it in `TenantContext`
for the rest of the request to use.

```php
// app/MultiTenancy/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();
        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }
    }
}
```
A **Global Scope** — Laravel automatically adds `WHERE tenant_id = ?` to
*every* query on any model using it. Nobody has to remember to add this
filter by hand; it is structurally impossible to forget.

```php
// app/MultiTenancy/BelongsToTenant.php
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id')) {
                $model->setAttribute('tenant_id', app(TenantContext::class)->id());
            }
        });
    }
}
```
Two jobs in one trait: attach the read-side filter (`TenantScope`), and
auto-stamp `tenant_id` on every new row via the `creating` event — so a
developer building a new feature can't forget to set it, and a malicious
request can't override it (the Form Request's `Fillable` list already
strips any `tenant_id` sent by the client; this trait sets the *real* one
regardless). This is **defense in depth**: two independent layers, so one
mistake alone can't cause a leak.

`getAttribute()` / `setAttribute()` are used instead of the magic
`$model->tenant_id` property specifically because PHPStan can't verify
magic properties exist on the generic `Model` type hint — using the real
typed methods keeps static analysis clean.

**Interview soundbite:** "I built multi-tenancy myself instead of using a
package specifically so I understand every layer: how the tenant is
identified, how reads are filtered, how writes are stamped, and why doing
it in two independent places (strip + auto-stamp) is safer than one."

---

## Phase 6 — Authentication (Laravel Sanctum)

**The big idea:** authentication answers "who are you?" — a completely
separate question from authorization ("what are you allowed to do?"),
which Phase 7 covers.

```php
// app/Http/Requests/RegisterRequest.php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'tenant_slug' => ['required', 'string', 'exists:tenants,slug'],
    ];
}
```
`'confirmed'` requires a matching `password_confirmation` field.
`tenant_slug` lets a new user say *which company* they belong to at
registration — a human-readable identifier (`acme-corp`), not the raw
internal `tenant_id` number.

```php
// app/Services/AuthService.php
public function register(array $data): User
{
    $tenant = Tenant::where('slug', $data['tenant_slug'])->firstOrFail();
    unset($data['tenant_slug']);
    $data['tenant_id'] = $tenant->id;

    return $this->users->create($data);
}

public function login(array $credentials): string
{
    $user = User::where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    return $user->createToken('api-token')->plainTextToken;
}
```
`register()` translates the human-friendly `tenant_slug` into the real
`tenant_id` before saving. `login()` uses one **identical, generic** error
message whether the email doesn't exist OR the password is wrong — this
prevents **user enumeration** (an attacker probing which emails are
registered by comparing different error messages). `Hash::check()`
compares a plain password against the stored bcrypt hash — we never store
or compare plain-text passwords.

`createToken('api-token')->plainTextToken` — Sanctum generates a random
API token, stores a hashed version in the `personal_access_tokens` table,
and returns the plain-text version *once* (the client must save it; it
can never be retrieved again — same principle as a password).

```php
// app/Models/User.php (relevant parts)
#[Fillable(['name', 'email', 'password', 'tenant_id'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // Laravel hashes automatically on save
            'role' => UserRole::class,
        ];
    }
}
```
`'password' => 'hashed'` means anywhere you assign `$user->password =
'plaintext'`, Laravel bcrypt-hashes it automatically before saving — one
less place to remember to hash manually. `HasApiTokens` (from Sanctum)
adds the `createToken()` method and the relationship to issued tokens.

```php
// routes/api.php
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
});
```
Register/login are public (no token needed yet — you don't have one).
Every other route requires `auth:sanctum` first (proves who you are),
*then* `tenant` (resolves which company you belong to) — order matters,
since `ResolveTenant` reads `$request->user()`, which only exists after
`auth:sanctum` has already run.

**Interview soundbite:** "Login always returns the same generic error for
both 'unknown email' and 'wrong password' — that one design choice closes
a real user-enumeration vulnerability."

---

## Phase 7 — Authorization (Gates, Policies, Roles)

**The big idea:** authentication proves *who* you are; authorization
decides *what you're allowed to do* — and that decision should live in one
place per model, not scattered across controllers.

### Gates — simple yes/no checks, not tied to one model

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Gate::define('view-all-tenants', function (User $user) {
        return str_ends_with($user->email, '@irenexa.com');
    });
}
```
A Gate is good for rules that aren't about one specific record — "can this
user see the internal admin dashboard at all?" Checked with
`Gate::allows('view-all-tenants')` or `$user->can('view-all-tenants')`.

### Policies — organized, per-record rules

```php
// app/Policies/LeadPolicy.php
class LeadPolicy
{
    public function viewAny(User $user): bool { return true; }

    public function view(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function create(User $user): bool { return true; }

    public function update(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        // Deleting needs BOTH: same company AND admin role.
        return $user->tenant_id === $lead->tenant_id
            && $user->role === UserRole::Admin;
    }
}
```
Laravel auto-discovers this class by naming convention (`Lead` model →
`LeadPolicy`). Each method answers one specific question about one
specific action. `delete()` is intentionally stricter than `view()`/
`update()` — deleting is more dangerous, so it requires an extra
condition (`role === Admin`), not just tenant match.

### Roles — the missing piece Policies needed

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
```
```php
// migration: add_role_to_users_table
$table->string('role')->default('member')->after('tenant_id');
```
Default is `'member'` — the LEAST powerful option. A brand-new user must
never accidentally get admin power; if unsure, default low ("least
privilege"). `role` is deliberately **left out** of `User`'s
`#[Fillable(...)]` list — the same pattern as `tenant_id` in Phase 5 — so
a client sending `{"role": "admin"}` in a request has it silently
ignored. The only way to become admin is code we control on purpose.

### Wiring Policies into Form Requests

```php
// app/Http/Requests/StoreLeadRequest.php
public function authorize(): bool
{
    return $this->user()->can('create', Lead::class);
}
```
Before this lesson, `authorize()` hardcoded `return true`. Now it asks the
Policy the same question, so tightening `LeadPolicy::create()` later
automatically updates this endpoint with zero other changes — one rule,
one home.

### Testing authorization

```php
// tests/Feature/LeadAuthorizationTest.php
public function test_an_admin_cannot_delete_a_lead_in_another_tenant(): void
{
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminInTenantA = User::factory()->create([
        'tenant_id' => $tenantA->id,
        'role' => UserRole::Admin,
    ]);
    $leadInTenantB = Lead::factory()->create(['tenant_id' => $tenantB->id]);

    $this->assertFalse($adminInTenantA->can('delete', $leadInTenantB));
}
```
This is the most important test in the suite: it proves the `&&` in
`LeadPolicy::delete()` really requires *both* conditions — being an admin
of the wrong company is still not allowed. Authorization bugs are almost
always "should have been blocked but wasn't," so tests for the *blocked*
case matter more than tests for the *allowed* case.

We also caught a real bug this phase: `TenantIsolationTest` (from Phase 5)
had gone stale — it still tested tenant isolation using an `X-Tenant`
header, but Phase 6 rewrote `ResolveTenant` to read the tenant from the
logged-in user instead. The test would have silently failed the next time
anyone ran it. Fixed by logging a real user in via `Sanctum::actingAs()`.
This is exactly what automated tests are for: catching drift between code
and its own tests.

**Interview soundbite:** "Authorization checks always call into a Policy
or Gate — never an inline `if ($user->role !== 'admin')` in a controller
— because a rule that lives in ten places is a rule that will eventually
be wrong in at least one of them."

---

## Full architecture, top to bottom (Phases 4–7 combined)

```
HTTP Request
   │
   ▼
Middleware: auth:sanctum   (Phase 6 — who are you?)
   │
   ▼
Middleware: tenant (ResolveTenant)   (Phase 5 — which company?)
   │
   ▼
Form Request: authorize()   (Phase 7 — are you allowed to even try this?)
Form Request: rules()       (Phase 4 — is your data valid?)
   │
   ▼
Controller   (Phase 4 — thin, translates HTTP ↔ Service)
   │
   ▼
Service   (Phase 4 — business rules live here)
   │
   ▼
Repository interface → Eloquent implementation   (Phase 4 — only door to the DB)
   │
   ▼
Model with BelongsToTenant   (Phase 5 — every query auto-filtered + auto-stamped by tenant)
   │
   ▼
MySQL
```

Every phase added exactly one more guarantee to this pipeline, without
ever touching the layers built in earlier phases. That's the payoff of
Clean Architecture: each concern has exactly one home.
