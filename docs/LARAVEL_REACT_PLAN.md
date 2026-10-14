# Luminest: Laravel + React Conversion Plan

> **Who this is for:** the team and Claude Code. Read this before writing any code for the rebuild.
> **Companion document:** [`SYSTEM_DOCUMENTATION.md`](SYSTEM_DOCUMENTATION.md) is the source of truth for **business rules** (workflows, statuses, validation, known defects). This plan covers **how** to build it with the required stack. Where the two disagree, this plan wins on architecture (it supersedes §8.1 and §8.8 of the system documentation, which assumed Blade/Livewire).

---

## 1. What the course requires (ITELEC 4100 Final Project: Web API)

| # | Requirement | How this plan meets it |
|---|---|---|
| 1 | Convert the native PHP app into **Laravel + React JS** | Repo root = Laravel REST API, `frontend/` = React SPA (Vite) |
| 2 | May enhance features based on comments on the previous project | §9 Enhancements. **The team must fill in the instructor's comments.** |
| 3 | Optional Google or Facebook login (bonus points) | Phase 7: Google login via Laravel Socialite |
| 4 | 15–20 min video: backend, frontend, code structure; every member presents | §11 Presentation plan |
| — | Backend **Laravel**, frontend **React JS**, DB **MySQL** | Fixed. No other frameworks for these three layers. |

**Rubric, and what it means for the code**

| Category | Points | What graders will look for → what we do |
|---|---|---|
| Functionality | 35 | Every module (§1.1) works, no errors, **MVC pattern** → full feature parity (§6), feature tests, thin controllers + models + API Resources |
| Design | 15 | Looks good, user-friendly → consistent layout, loading/empty/error states, toasts, confirm dialogs, responsive |
| Coding standard | 25 | Indentation, naming, easy to follow → Laravel Pint + ESLint/Prettier enforced, conventions in §8 |
| Presentation | 25 | Explain code confidently, everyone has a part → module owners in §1.1, script in §11 |

### 1.1 Modules

The system is split into six modules. Use these IDs and names in phases, PR titles, commit scopes (`feat(M3): ...`), test folders (`tests/Feature/M3_Reservation/`) and the presentation. Code folders stay grouped by role as in §3 (one module can span several roles), and services and models are named after the module's domain (`ReservationService`, `MaintenanceService`). "Each individual module working" in the rubric means each row below works end-to-end.

| ID | Module | Features | Roles | API (§5) | Pages (§6) | Built in (§7) | Owner |
|---|---|---|---|---|---|---|---|
| **M1** | **Authentication & Profile** | Register, login, logout, role-based redirect, edit profile, change password, block inactive accounts, Google login (bonus) | All | §5.1 | Login, Register, Profile, OAuthCallback | Phases 2, 7 | _TBD_ |
| **M2** | **House Catalog** | Public landing page, house model details, available block/lot lookup | Public, Prospect | §5.2 | Landing, HouseTypeDetail | Phase 3 | _TBD_ |
| **M3** | **Reservation & Purchase** Reserve a unit by paying a ≥ ₱5,000 reservation fee, pay the balance in instalments, automatic completion at ₱0 (prospect becomes tenant), cancel (fee non-refundable), PDF receipt per payment, reservation history; PM view-only (§1.2) | Prospect, Property Manager | §5.3, §5.6 (reservations) | prospect/*, manager/Reservations | Phase 3 | _TBD_ |
| **M4** | **Maintenance Requests** | Tenant submits and confirms resolved; PM assigns staff by expertise; staff updates progress and notes; open queue and history | Tenant, Property Manager, Maintenance Staff | §5.4, §5.5, §5.6 (maintenance) | tenant/*, staff/*, manager/Maintenance, manager/MaintenanceHistory | Phase 4 | _TBD_ |
| **M5** | **Property Management** | PM dashboard stats, house listings (read-only status, see §1.2 R7), tenant list, staff list and demote | Property Manager | §5.6 (dashboard, houses, tenants, staff) | manager/Dashboard, Houses, Tenants, Staff | Phase 5 | _TBD_ |
| **M6** | **User Administration** | Admin dashboard, create/edit/view/delete users, activate/deactivate, staff expertise, no self-delete | Admin | §5.7 | admin/* | Phase 5 | _TBD_ |

**Shared foundations (not modules, used by all of them):**
- Database schema and seeders (Phase 1).
- Roles and authorization: `role` middleware, policies, React `ProtectedRoute`/`RoleRoute`.
- Shared UI: `AppLayout`, sidebar, `components/ui/*`, toasts.
- Optional enhancements from §9c. Each one you build counts as a small extra module (M7, M8, …) with its own owner.

With fewer than six members, pair modules: M1 alone; M2 + M3; M4 alone; M5 + M6.

### 1.2 Changed business rules: reservation & payment (M3)

The rebuild **replaces** the legacy reservation flow (SYSTEM_DOCUMENTATION §4.2: pending → PM accept/reject → pay). Where SYSTEM_DOCUMENTATION disagrees with this section, **this section wins**.

**Summary:** the prospect reserves a unit by paying a reservation fee, pays the rest in instalments, and the purchase completes automatically when the balance reaches ₱0. The Property Manager no longer approves or rejects anything. All payments are **simulated** (no gateway), and every payment produces a **PDF receipt**.

**Reservation statuses** (`ReservationStatus` enum): `reserved`, `cancelled`, `paid`. There is no `pending`, `accepted` or `rejected`.

```
                 reserve + fee ≥ ₱5,000
  (house available) ──────────────────────▶ reserved ──── balance reaches ₱0 ───▶ paid
                                               │          (automatic)
                                               └── prospect cancels ──▶ cancelled
```

**Rules**

| # | Rule |
|---|---|
| R1 | **Reserve:** the prospect picks an available unit and pays a **reservation fee of at least ₱5,000** in the same action. Reservation, first payment and house status `available → reserved` happen in **one DB transaction**, with the house row locked so two people can't reserve the same unit. |
| R2 | **One active reservation per user:** a user can't start a new reservation while they have one in `reserved` status. (They can buy another house after the first is `paid`.) |
| R3 | **Price snapshot:** the reservation stores `total_price` copied from the house type at reservation time, so later price changes don't affect existing buyers. **Balance = total_price − sum of payments.** |
| R4 | **Instalments:** while `reserved`, the owner can pay any number of times. Each payment must be **at least ₱5,000**, except when the remaining balance is below ₱5,000, in which case the payment must be **exactly** the balance. A payment can **never exceed** the balance (no overpayment). |
| R5 | **Automatic completion:** the payment that brings the balance to ₱0 sets, in the same transaction: reservation → `paid` (+ `paid_at`), house → `sold` with `owner_id` and `purchased_at`, and the user's role `prospect → tenant`. **No Property Manager input.** If the reservation fee already equals the full price, R1 and R5 happen together. |
| R6 | **Cancel:** only the owner, only while `reserved`. Reservation → `cancelled` (+ `cancelled_at`), house → `available`. Payments made so far are **non-refundable**: they stay on record and their receipts remain downloadable. |
| R7 | **Property Manager is view-only** for reservations: list, filter, see payments/balances and download receipts. No accept, reject, cancel or edit. House status is driven only by reservations (R1, R5, R6), so the PM can't change it by hand either. |
| R8 | **Money is whole pesos:** prices and payment amounts are stored as unsigned integers (no centavos, no floats). Display them as `₱2,495,000`. |
| R9 | **Receipt for every payment:** a PDF generated server-side (see below), downloadable by the payer, the Property Manager and the Admin. |

**PDF receipt contents** (one receipt per payment; generated on demand from the `payments` row, so it can be re-downloaded any time):
- Receipt number (e.g. `LUM-2026-000123`) and payment date/time.
- Buyer name and email.
- What was paid for: house model, block and lot (e.g. *Angelique Duplex, Block 1 Lot 5*).
- Payment type: reservation fee, instalment or final payment.
- **Amount paid now**, total price, total paid to date, **remaining balance**.
- A note: *"Simulated payment. No real transaction took place."*

The one-line summary from the brief must be readable at a glance, for example: **Angelique Duplex — Paid ₱5,000 | Balance ₱2,495,000 | 2026-10-14**.

Implementation: `barryvdh/laravel-dompdf` rendering a Blade template at `resources/views/pdf/receipt.blade.php`. This is the **only** Blade view in the project: it's a PDF layout, not a web page, so it doesn't conflict with "no Blade UI".

---

## 2. Architecture decisions (settled, do not revisit without reason)

| Decision | Choice | Why |
|---|---|---|
| App shape | **Separate React SPA + Laravel JSON API** | The project is titled "Web API". Inertia would render pages from Laravel and expose no real API. |
| Repo layout | This repo (`Luminest_Final`): Laravel at the root, React in `frontend/`. The legacy app stays in the separate [`Josiah-Gav/Luminest`](https://github.com/Josiah-Gav/Luminest) repo. | One repo to submit; the old app stays available for reference without cluttering this one |
| Laravel | 12.x, PHP 8.2+ | Current LTS-track version |
| API auth | **Laravel Sanctum, SPA cookie auth** (stateful, CSRF-protected) | Safer than storing tokens in localStorage; works with Socialite redirects |
| React tooling | **Vite 8 + React 19 + JavaScript (JSX)** | The brief says "React JS". TypeScript is optional; only adopt it if the whole team is comfortable explaining it. |
| Routing (FE) | React Router 7 (`react-router-dom`) | Standard |
| Server state (FE) | **TanStack Query** + **axios** | Caching, loading/error states, refetch-on-interval (replaces the legacy 10-second polling) |
| Forms (FE) | react-hook-form | Less boilerplate. Laravel 422 errors map onto fields. |
| Styling / UI | **Tailwind CSS v4** (`@tailwindcss/vite`) + **Headless UI** (modals, dropdowns, menus, transitions) + **Heroicons** + `clsx` | The team already knows Tailwind. Full design control for the Design score. Headless UI supplies accessible interactive pieces without imposing a look. |
| Notifications (FE) | react-hot-toast | Feedback on every action, styled with Tailwind classes |
| PDF receipts | `barryvdh/laravel-dompdf` (Blade template rendered to PDF) | Simple, well known, no external service. See §1.2. |
| DB | XAMPP's MySQL (MariaDB 10.4, port 3309), database `luminest_v2` (do not reuse the legacy DB) | Clean schema, seeded from scratch |
| Tests | Pest 3 (backend feature tests; Pest 4 needs PHP 8.3) | Protects the business rules during refactors |
| Lint/format | Laravel Pint (PSR-12) · ESLint + Prettier | Rubric: coding standard |

### Request flow (the MVC story for the presentation)

```
React page ──axios──▶ routes/api.php ──▶ middleware (auth:sanctum, role)
                                          │
                                          ▼
                       FormRequest (validate) ──▶ Controller (thin)
                                                    │
                                                    ▼
                                    Policy (may I?) ─▶ Service (business rules, DB transaction)
                                                    │
                                                    ▼
                                         Eloquent Model ◀──▶ MySQL
                                                    │
                                                    ▼
                                   API Resource (JSON shape) ──▶ React renders
```

- **M** = Eloquent models + enums.
- **C** = API controllers.
- **V** = API Resources (the JSON "view") on the server, plus React components on the client.

Say this explicitly in the video.

---

## 3. Target folder structure

```
/
├── CLAUDE.md                  # Context for Claude Code (short; points here)
├── docs/                      # This plan + system documentation
├── app/                       # Laravel 12 lives at the repo root (plus the standard bootstrap/, config/, public/, storage/)
│   ├── Enums/             # Role, HouseStatus, ReservationStatus, MaintenanceStatus, MaintenanceCategory, Priority
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Auth/              # AuthController, SocialAuthController, ProfileController
│   │   │   ├── HouseTypeController.php
│   │   │   ├── ReservationController.php          # prospect/tenant self-service
│   │   │   ├── Tenant/MaintenanceRequestController.php
│   │   │   ├── Staff/MaintenanceRequestController.php
│   │   │   ├── PropertyManager/{Dashboard,House,Reservation,Tenant,MaintenanceRequest,Staff}Controller.php
│   │   │   └── Admin/{Dashboard,User}Controller.php
│   │   ├── Middleware/EnsureUserHasRole.php
│   │   ├── Requests/      # one FormRequest per write endpoint
│   │   └── Resources/     # UserResource, HouseResource, ReservationResource, ...
│   ├── Models/            # User, HouseType, House, Reservation, Payment, MaintenanceRequest
│   ├── Policies/          # ReservationPolicy, MaintenanceRequestPolicy, UserPolicy
│   └── Services/          # ReservationService, MaintenanceService
├── database/{migrations,factories,seeders}/
├── routes/{api.php,web.php}
├── tests/Feature/
└── frontend/                  # React + Vite
    └── src/
        ├── api/               # client.js (axios instance) + one module per resource
        ├── auth/              # AuthProvider, useAuth, ProtectedRoute, RoleRoute
        ├── components/
        │   ├── layout/        # AppLayout, Sidebar (role-based menu), Topbar, PublicLayout
        │   └── ui/            # Button, Input, Card, Badge, StatusBadge, DataTable, SearchFilterBar, ConfirmDialog (Headless UI), EmptyState, Pagination
        ├── pages/
        │   ├── public/        # Landing, HouseTypeDetail, Login, Register, OAuthCallback, NotFound
        │   ├── prospect/      # Dashboard, ReserveUnit, MyReservations
        │   ├── tenant/        # Dashboard, MaintenanceList, MaintenanceCreate, MaintenanceDetail
        │   ├── staff/         # Dashboard, AssignedRequests, RequestDetail, History
        │   ├── manager/       # Dashboard, Houses, Reservations, Tenants, Maintenance, MaintenanceHistory, Staff
        │   ├── admin/         # Dashboard, Users, UserForm, UserDetail
        │   └── shared/        # Profile
        ├── hooks/             # useDebounce, etc.
        ├── utils/             # formatters (currency ₱, dates), status labels
        ├── index.css          # Tailwind import + @theme brand tokens
        ├── routes.jsx
        └── main.jsx
```

---

## 4. Database (Laravel migrations)

Use the schema in **SYSTEM_DOCUMENTATION.md §8.2** as-is: `users`, `house_types`, `houses`, `reservations`, `payments`, `maintenance_requests`. Additional rules:

- Status/category/priority columns are `string` + **PHP backed enums** with Eloquent casts. Do not use MySQL `ENUM`.
- `houses` has `unique(block, lot)`. `reservations.house_id` is a real FK.
- **Reservations and payments follow §1.2**, which overrides SYSTEM_DOCUMENTATION §8.2 for these tables:
  - `house_types.price`: unsigned integer (whole pesos).
  - `reservations`: `user_id`, `house_id`, `status` (`reserved`/`cancelled`/`paid`), `total_price` (unsigned integer snapshot), `cancelled_at`, `paid_at`, timestamps. No `decided_at`/`decided_by`.
  - `payments`: `reservation_id`, `receipt_number` (unique), `type` (`reservation_fee`/`instalment`/`final`), `amount`, `balance_after` (snapshot for the receipt), `method` (default `simulated`), `paid_at`, timestamps.
  - Balance is calculated (`total_price − sum(payments.amount)`), never stored on the reservation, so it can't drift. `balance_after` on each payment is only a historical snapshot for its receipt.
- `reservations.user_id` and `payments.reservation_id` **restrict** on delete (not cascade), so payment records and house statuses can't be wiped by deleting a user (§5.7).
- No `house_types.code` (only needed for the skipped legacy import) and no `users.email_verified_at` (no email verification).
- No `users.owned_house`. Ownership = `houses.owner_id`.
- Add `users.google_id` (nullable, unique) and `users.avatar_url` (nullable) for Phase 7. Make `users.password` nullable for Google-only accounts.
- Store everything raw. **Never** `htmlspecialchars` before saving; React escapes output.

**Seeders.** `php artisan migrate:fresh --seed` gives the **base data** only: from the legacy app we keep just the house listing, so reservations, payments and maintenance requests start empty.

- `HouseTypeSeeder`: the 4 models from SYSTEM_DOCUMENTATION §5.4 (with prices).
- `HouseSeeder`: the 850 units, using the block layout in §5.3.
- `UserSeeder`: one account per role, password `password`: `admin@`, `manager@`, `staff.plumbing@`, `staff.electrical@`, `tenant@`, `prospect@luminest.test`.

**Demo data (on demand).** `php artisan db:seed --class=DemoSeeder`, run after the base seed (e.g. before recording the video). It is not part of `DatabaseSeeder`, and it skips itself when reservations already exist.

- `DemoSeeder`: reservations in every status (`reserved` with a few instalments, `cancelled` with a forfeited fee, `paid` by `tenant@`, whose house becomes sold), with consistent payments, and maintenance requests in every status, so every screen has data during the video.

Legacy data import is **not** required for the course. Skip SYSTEM_DOCUMENTATION §9 unless real data must be kept.

---

## 5. API contract

Subsections map to modules: §5.1 = M1, §5.2 = M2, §5.3 = M3, §5.4–5.5 = M4, §5.6 = M3/M4/M5 (manager side), §5.7 = M6.

Base URL `/api`. JSON only. All routes except "public" require `auth:sanctum`. Errors use Laravel defaults: `401` unauthenticated, `403` forbidden (policy/role), `404`, `422` `{message, errors: {field: [..]}}`. Lists are paginated (`?page=`, Laravel `meta`/`links`).

### 5.1 Auth & profile

| Method | Path | Who | Notes |
|---|---|---|---|
| GET | `/sanctum/csrf-cookie` | public | Call once before login/register |
| POST | `/api/register` | public | name, email, phone_number, password, password_confirmation → role `prospect` |
| POST | `/api/login` | public | email, password. Reject `is_active = false`. |
| POST | `/api/logout` | auth | |
| GET | `/api/user` | auth | Current user (UserResource incl. role, owned houses) |
| PUT | `/api/profile` | auth | name, email, phone_number |
| PUT | `/api/profile/password` | auth | current_password, password, password_confirmation |
| GET | `/auth/google/redirect` | public (web route) | Phase 7 |
| GET | `/auth/google/callback` | public (web route) | Log in or create a prospect, then redirect to `FRONTEND_URL/oauth/callback` |

### 5.2 Public catalog

| Method | Path | Notes |
|---|---|---|
| GET | `/api/house-types` | 4 models with price and image |
| GET | `/api/house-types/{slug}` | Detail |
| GET | `/api/house-types/{slug}/available-units` | `{ "5": [1,2,7], "6": [3] }` = block → available lots |

### 5.3 Prospect / Tenant: reservations (role: prospect, tenant)

| Method | Path | Rule (see §1.2) |
|---|---|---|
| GET | `/api/reservations` | Own reservations, with total price, total paid and balance |
| GET | `/api/reservations/{id}` | Owner. Includes the payment list. |
| POST | `/api/reservations` | `{house_id, amount}`. R1–R3: unit available (row lock), no other `reserved` reservation, `amount` ≥ 5,000 and ≤ price. Creates reservation + first payment. Returns the payment, so the UI can offer its receipt. |
| POST | `/api/reservations/{id}/payments` | `{amount}`. Owner, `reserved` only. R4 amount rules; R5 automatic completion when the balance reaches 0. |
| POST | `/api/reservations/{id}/cancel` | Owner, `reserved` only. R6: unit becomes available; payments stay (non-refundable). |
| GET | `/api/payments/{id}/receipt` | PDF download (`application/pdf`). The payer, Property Manager or Admin only (policy). |

### 5.4 Tenant: maintenance (role: tenant)

| Method | Path | Rule |
|---|---|---|
| GET | `/api/tenant/maintenance-requests` | Own requests |
| POST | `/api/tenant/maintenance-requests` | title, description, category, priority, house_id (must be a house the tenant owns) |
| GET | `/api/tenant/maintenance-requests/{id}` | Owner |
| POST | `/api/tenant/maintenance-requests/{id}/resolve` | Owner. Completed only. |

### 5.5 Maintenance staff (role: maintenance_staff)

| Method | Path | Rule |
|---|---|---|
| GET | `/api/staff/maintenance-requests?scope=active\|history` | **Only requests assigned to me** (fixes the legacy access hole) |
| GET | `/api/staff/maintenance-requests/{id}` | Assigned to me |
| PATCH | `/api/staff/maintenance-requests/{id}` | `{status: in_progress\|completed, resolution_notes}`. Allowed transitions only. |

### 5.6 Property manager (role: property_manager)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/manager/dashboard` | House counts by status, maintenance counts by status, recent requests, active reservations count, total collected and total outstanding balance |
| GET | `/api/manager/houses?search=&status=&house_type=` | Paginated |
| GET | `/api/manager/reservations?status=&search=` | Read-only (R7): buyer, unit, status, total, paid, balance |
| GET | `/api/manager/reservations/{id}` | Read-only, with the payment list. Receipts via `GET /api/payments/{id}/receipt`. |
| GET | `/api/manager/tenants?search=` | Tenants with their houses |
| GET | `/api/manager/maintenance-requests?scope=open\|closed&search=&status=&priority=` | |
| PATCH | `/api/manager/maintenance-requests/{id}/assignment` | `{staff_id\|null}`. Expertise must match category or be `general`. |
| GET | `/api/manager/staff?search=&expertise=` | With active/completed counts. Also feeds the assignment dropdown. |
| DELETE | `/api/manager/staff/{id}` | Demote to prospect. Unassign their open requests (decision: set those back to pending). |

### 5.7 Admin (role: admin)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/admin/dashboard` | User counts by role and status |
| GET/POST/GET/PUT/DELETE | `/api/admin/users[/{id}]` | `apiResource`. Expertise is required only for staff. Admin cannot delete or deactivate themselves. Deleting a user with reservation history returns `422` (the FK restricts it so payment records survive); the admin deactivates them instead. |

---

## 6. Frontend page map (feature parity checklist)

Each row is done when it works end-to-end against the API, with loading, empty and error states. See §1.1 for which module each page belongs to.

| Role | Page (route) | Legacy equivalent | API used |
|---|---|---|---|
| Public | Landing `/` | `welcome.php` (use **real** house types, not placeholders) | `GET house-types` |
| Public | House detail `/houses/:slug` | `view/house_types/*` | `GET house-types/{slug}` |
| Public | Login `/login`, Register `/register` | `view/auth/*` | auth |
| All | Profile `/profile` | `*/profile.php` | profile |
| Prospect | Dashboard `/prospect` | `Prospect/dashboard.php` | house-types, reservations |
| Prospect | Reserve `/prospect/reserve/:slug` (block → lot pickers, reservation fee input with ₱5,000 minimum, confirm dialog, receipt download after success) | `Prospect/reservation.php` | available-units, POST reservations |
| Prospect | My reservations `/prospect/reservations` (per reservation: total / paid / balance with a progress bar, "Pay" modal enforcing R4, payment history with a receipt download per payment, cancel with a non-refundable warning) | `Prospect/history.php` | reservations, payments, receipt |
| Tenant | Dashboard `/tenant` (my house, open requests) | `Tenant/dashboard.php` | user, maintenance |
| Tenant | Requests list / new / detail (+ "Mark resolved") | `Tenant/maintenance_*` | tenant maintenance |
| Staff | Dashboard, Assigned list, Detail (status + notes form), History | `Maintenance_Staff/*` | staff maintenance |
| Manager | Dashboard (stat cards, recent, auto-refresh 30 s) | `Property_Manager/dashboard.php` | manager/dashboard |
| Manager | Houses (search, filter, status change) | `listings.php` | manager/houses |
| Manager | Reservations (read-only list + detail with payments and receipt downloads) | `reservation_list.php` | manager/reservations, receipt |
| Manager | Tenants | `tenants.php` | manager/tenants |
| Manager | Maintenance queue (assign staff dropdown filtered by expertise) | `maintenance.php` | manager/maintenance-requests, manager/staff |
| Manager | Maintenance history | `maintenance_history.php` | `scope=closed` |
| Manager | Staff (search, demote with confirm) | `maintenance_staff.php` | manager/staff |
| Admin | Dashboard, Users table, Create/Edit form, View | `Admin/*` | admin/* |

**Role-based routing:** after login, redirect by `user.role` to that role's dashboard. `RoleRoute` blocks other roles client-side; the API is still the real guard. The sidebar menu is generated from the role.

**Design system:** keep the legacy brand, defined once as Tailwind theme tokens in `frontend/src/index.css`:

```css
@import "tailwindcss";

@theme {
  --color-brand-red: #c1121f;
  --color-brand-blue: #1d4ed8;
  --color-ink: #111827;
  --font-display: "Playfair Display", serif;
  --font-sans: "Source Sans 3", sans-serif;
}
```

This gives classes like `bg-brand-blue`, `text-ink` and `font-display`. Load both fonts from Google Fonts in `index.html`. Use Playfair Display for headings, Source Sans 3 for body text, and the house photos from the legacy repo's `assets/` folder (copy them into `frontend/src/assets/`). Use one `AppLayout` with a sidebar for every logged-in role (the legacy app had two inconsistent navbars).

---

## 7. Build phases

Work top to bottom. Each phase ends with a PR, green tests and lint. **For Claude Code:** do one phase (or one sub-bullet) per session. Read the acceptance criteria before starting, and do not start the next phase uninstructed.

Phases build modules in dependency order: Phases 0–1 are shared foundations, Phase 2 = M1, Phase 3 = M2 + M3, Phase 4 = M4, Phase 5 = M5 + M6, Phase 6 = optional extras, Phase 7 = M1 (Google login).

| Phase | Scope | Acceptance criteria |
|---|---|---|
| **0. Scaffold** | Laravel 12 is already installed at the root. Add Sanctum (`php artisan install:api`), CORS + `SANCTUM_STATEFUL_DOMAINS`, Pint, Pest; remove the default Tailwind/Blade front-end assets (`resources/js`, `resources/css`, root `package.json`/`vite.config.js`), since React has its own Vite setup (Tailwind moves into `frontend/`). `frontend/`: Vite React, Tailwind v4 (`@tailwindcss/vite`) with brand tokens in `src/index.css`, Headless UI, Heroicons, clsx, react-hot-toast, React Router, TanStack Query, axios (`withCredentials`, `withXSRFToken`), ESLint + Prettier with `prettier-plugin-tailwindcss`. `.env.example` for both. README with setup commands. | `php artisan serve` + `npm run dev` both run. Frontend calls `GET /api/user` and gets 401. Lint passes. |
| **1. Database** | Enums, migrations, models + relationships + casts + scopes, factories, all seeders. Keep Laravel's default `users`/`cache`/`jobs` migrations (they also create `sessions`, `cache` and `jobs`): `CACHE_STORE` and `QUEUE_CONNECTION` are still `database`, and Phase 2 login throttling uses the cache. | `migrate:fresh --seed` succeeds. 850 houses. One user per role. Tinker relationships work. |
| **2. Auth & shell** | Register/login/logout/user/profile/password endpoints + tests. `role` middleware. React AuthProvider, Login, Register, Profile, AppLayout with role sidebar, ProtectedRoute/RoleRoute, role redirect, 404 page. | Each seeded user logs in and lands on their dashboard. A wrong role gets 403 (API) or a redirect (UI). Inactive users cannot log in. |
| **3. Catalog & reservations** | House-type endpoints, ReservationService (reserve/pay/cancel per §1.2) + Policy + tests, PDF receipt (dompdf). Landing, house detail, reserve flow, my reservations with pay modal and receipts, PM read-only reservations page. | Full §1.2 flow works in the UI. Tests cover: double booking blocked, fee < ₱5,000 rejected, instalment < ₱5,000 rejected unless it settles the balance, overpayment rejected, final payment sets `paid` + house `sold` + role `tenant`, cancel frees the unit and keeps payments, PM cannot modify reservations, receipt downloads as PDF only for allowed users. |
| **4. Maintenance** | MaintenanceService (submit/assign/progress/resolve) + Policy + tests. Tenant pages, staff pages, PM queue + history. | Full §4.3 flow works. Staff cannot see or update unassigned requests (test). Expertise rule enforced (test). |
| **5. Manager & admin** | PM dashboard, houses, tenants, staff pages. Admin dashboard + user CRUD. When an admin deactivates a user, also delete their rows from the `sessions` table so they're logged out immediately, with a test. | Every legacy PM/Admin screen has a working equivalent. Self-delete is blocked. A deactivated user is logged out immediately. |
| **6. Enhancements** | Items chosen from §9 | Each item has its own test or demo steps |
| **7. Google login (bonus)** | Socialite: `/auth/google/redirect` + callback, link by email or create a prospect, "Continue with Google" button | Works with a test Google account. Docs explain how to set the client ID/secret. |
| **8. Polish** | Responsive pass, loading skeletons, empty states, toasts, confirm dialogs, form error mapping, favicon/title, final DemoSeeder | No console errors. Every page is usable at phone width. Demo script runs without errors. |
| **9. Presentation** | §11 | Video recorded |

---

## 8. Coding conventions (rubric: Coding standard)

**Backend**

- Controllers stay thin: validate (FormRequest), authorize (Policy), call a Service, return a Resource. No SQL in controllers.
- Business rules and transactions live in `Services`. Status transitions live in the enums (`canTransitionTo()`).
- Naming: singular models (`House`); plural tables; `camelCase` methods; `snake_case` DB columns and JSON keys; enums `PascalCase` cases with `snake_case` values.
- Always return API Resources, never raw models.
- `composer pint` before every commit. Every endpoint has a feature test (happy path + forbidden role + one validation error).

**Frontend**

- Components `PascalCase.jsx`, hooks `useThing.js`, one component per file.
- All HTTP goes through `src/api/*`. Pages never call axios directly.
- Server data is fetched with TanStack Query (`useQuery`/`useMutation`); invalidate queries after a mutation. No duplicated fetch logic.
- Status → label/colour mapping lives in one place (`utils/status.js`). SYSTEM_DOCUMENTATION §6.4 uses Bootstrap colour names; treat them as meanings and map them to Tailwind: success → `green`, primary → `brand-blue`, info → `sky`, warning → `amber`, danger → `red`, secondary → `gray`.
- Tailwind: use the theme tokens, never raw hex values in class names. When the same class list repeats, extract a component (`Button`, `Card`, `Badge`, `Input`) instead of using `@apply`. Use `clsx` for conditional classes. Let `prettier-plugin-tailwindcss` sort class names.
- Interactive widgets (modal, dropdown, menu, tabs) use Headless UI, not hand-rolled show/hide logic.
- `npm run lint` clean, Prettier formatted.

**Git**

- Branch per phase/feature. Conventional commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`). PR into `main`. Never commit `.env` or `vendor/`/`node_modules/`.

**Do not carry over from legacy**

- Runtime schema checks (`SHOW TABLES`/`SHOW COLUMNS`).
- SQL inside views/pages.
- `htmlspecialchars` before saving.
- Hard-coded `/luminest/` paths.
- Duplicated helper functions.
- Role checks of the form `isset(role) && role !== X`.
- See the full list in SYSTEM_DOCUMENTATION §7.

---

## 9. Enhancements

### 9a. Instructor comments on the previous project (team: fill this in)

> _List every comment from the previous grading here, each with the phase that addresses it. This is requirement #2 and should be shown in the video._

| # | Comment | How we address it | Phase |
|---|---|---|---|
| 1 | _..._ | | |

### 9b. Fixes we get by rebuilding (show these in the video)

These all come from SYSTEM_DOCUMENTATION §7:

- Proper role protection on every endpoint.
- Staff limited to their own assignments.
- CSRF protection.
- Reservations can no longer lock a unit for free: a reservation fee is required, and every payment is recorded with a receipt.
- One payment path with automatic completion; the PM can't push a sale through by hand.
- Passwords are no longer altered before hashing.
- Inactive accounts are blocked at login.
- Validation on every input.

### 9c. Optional new features (pick by value/effort; do not do all of them)

| Feature | Value for demo | Effort |
|---|---|---|
| In-app notifications (Laravel database notifications + bell icon): payment received / reservation fully paid, request assigned/completed | High | Medium |
| Photo upload on maintenance requests (`storage/` + validation) | Medium | Medium |
| Dashboard charts (Chart.js / Recharts: houses by status, requests per month) | High (design score) | Low |
| CSV export on manager tables | Low | Low |

---

## 10. Local setup (goes in the README after Phase 0)

```bash
# backend (repo root)
cp .env.example .env && composer install && php artisan key:generate
# set DB_DATABASE=luminest_v2, FRONTEND_URL=http://localhost:5173,
# SANCTUM_STATEFUL_DOMAINS=localhost:5173, SESSION_DOMAIN=localhost
php artisan migrate:fresh --seed && php artisan serve          # http://localhost:8000

# frontend
cd frontend && cp .env.example .env && npm install            # VITE_API_URL=http://localhost:8000
npm run dev                                                    # http://localhost:5173
```

Use `localhost` for both apps, not `127.0.0.1` for one and `localhost` for the other. Sanctum cookie auth breaks across different hosts.

---

## 11. Presentation plan (15–20 min, every member speaks)

Each member presents the module(s) they own (§1.1) and explains **their module end-to-end**: React page → API route → controller → service/policy → model → resource. That covers backend, frontend and code structure, as the brief asks.

| Segment | Minutes | Content |
|---|---|---|
| Intro & architecture | 2–3 | Problem, roles, stack, request-flow diagram (§2), folder structure |
| M1 Authentication & Profile (+ Google login) | 3 | Sanctum flow, role middleware, policies |
| M2–M3 Catalog, Reservation & Purchase | 3–4 | Live demo: reserve with ₱5,000 fee → download receipt → pay an instalment → pay the exact balance → status becomes paid and the user becomes a tenant automatically. Show the ReservationService transaction and the R4 validation. |
| M4 Maintenance Requests | 3–4 | Live demo: submit → assign (expertise rule) → progress → resolve. Show policy limiting staff to their own requests. |
| M5–M6 Property Management & User Administration | 2–3 | Dashboards, search/filter, user CRUD |
| Quality & enhancements | 2 | Tests running, lint, instructor comments addressed (§9a), legacy defects fixed (§9b) |

Record against a fresh database (`php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder`) so the demo is predictable.

---

## 12. Open decisions (team must answer; Claude Code should ask if still blank)

1. Team size and module owners? (fill in the Owner column in §1.1)
2. Instructor comments from the previous project? (§9a)
3. Which optional enhancements? (§9c)
4. JavaScript or TypeScript for the frontend? Default: JavaScript.
5. Can one user own several houses? Default: yes (schema allows it). If a tenant owns more than one, they pick the house when filing a request.
6. Product questions in SYSTEM_DOCUMENTATION §8.9. Defaults used in this plan:
   - Reservation workflow: decided, see §1.2 (fee ≥ ₱5,000, instalments ≥ ₱5,000, automatic completion, non-refundable cancel, PM view-only).
   - Unused maintenance statuses (`cancelled`, `rejected`, `on_hold`) are dropped.
