# Luminest

Real-estate management system for one subdivision: house reservations and purchases, maintenance requests, and user administration. There are five roles: Admin, Property Manager, Maintenance Staff, Tenant, Prospect.

**Current task: convert the native-PHP app into a Laravel REST API + React SPA + MySQL** (ITELEC 4100 final project, "Web API").

## Read before working

1. `docs/LARAVEL_REACT_PLAN.md` covers the six modules (§1.1, M1–M6; use these IDs in tests, commits and PRs), architecture, folder structure, the API contract, the page map, build phases, conventions and open decisions. **It is authoritative for how to build.**
2. `docs/SYSTEM_DOCUMENTATION.md` covers the legacy app's workflows, status rules, DB schema and known defects (§7). **It is authoritative for business rules, except where plan §1.2 changes them** (the reservation & payment workflow). Do not reproduce the defects listed in §7.

## Ground rules

- Stack is fixed: Laravel 12 (API only, Sanctum SPA cookie auth) at the repo root, React (Vite, JavaScript, **Tailwind CSS v4** + Headless UI) in `frontend/`, MySQL. No Bootstrap. No Blade UI, no Inertia, no Livewire (the only Blade file is the PDF receipt template, plan §1.2).
- Work one build phase (plan §7) at a time. Meet its acceptance criteria and stop. Do not jump ahead.
- MVC is graded:
  - controllers stay thin (FormRequest → Policy → Service → API Resource);
  - business rules and transactions go in `app/Services`;
  - status transitions go in `app/Enums`.
- Every API endpoint gets a Pest feature test: happy path, forbidden role, and one validation error.
- Before finishing: `./vendor/bin/pint && php artisan test` (repo root), and `cd frontend && npm run lint`.
- The legacy native-PHP app is in the separate repo `Josiah-Gav/Luminest` (reference only; it is not in this repo). `docs/SYSTEM_DOCUMENTATION.md` describes it, so you rarely need the source. Never edit it.
- If an open decision in plan §12 blocks the task, ask instead of guessing.

## Status

Phase 0 (scaffold) is done: Sanctum SPA cookie auth, CORS, Pest 3, and the React app in `frontend/` (React 19, Vite 8, Tailwind v4, ESLint + Prettier). `DB_CONNECTION=mariadb` (XAMPP MariaDB 10.4, port 3309).

Phase 1 (database) is done: enums with status transitions in `app/Enums`, migrations, models (relationships, casts, scopes, calculated `total_paid`/`balance` on `Reservation`), factories, and seeders. `migrate:fresh --seed` gives base data only (4 house types, 850 available units, 6 logins); `db:seed --class=DemoSeeder` adds optional demo reservations, payments and maintenance requests in every status. Both are tested in `tests/Feature/Database/{BaseSeedTest,DemoSeedTest}.php`.

Phase 2 (M1 auth & shell) is done. API: register/login/logout/user/profile/password (`Api/Auth/{AuthController,ProfileController}`, FormRequests in `Requests/Auth`, `UserResource`), login throttle (5 per email+IP) and inactive-account check in `LoginRequest::authenticate()`, `role:` middleware (`EnsureUserHasRole`), phone normalization in `App\Support\PhoneNumber`. `SESSION_DRIVER=database`. Tests in `tests/Feature/M1_Auth/`; session-cookie tests use `freshRequest()` from `tests/Pest.php` (simulates a new PHP process). React: `src/routes.jsx`, `src/auth/*` (AuthProvider via TanStack Query, ProtectedRoute/RoleRoute/GuestRoute), AppLayout with role sidebar (`components/layout/menu.js`), Login/Register/Profile/NotFound, one `DashboardPlaceholder` for all five role dashboards. 

Phase 3 (M2 catalog + M3 reservations) is done. All §1.2 rules live in `app/Services/ReservationService.php` (reserve/pay/cancel, row locks, completion at ₱0 incl. a full-price fee); `ReservationPolicy` (pay/cancel = owner while reserved, receipt = payer/PM/admin); PDF receipts via dompdf (`resources/views/pdf/receipt.blade.php`). `GET /api/reservations` allows prospect + tenant; store/pay/cancel are prospect-only. React: landing with the 4 models (photos in `src/assets/houses/`), `/houses/:slug` with the reserve form, `/reservations` (prospect + tenant) with the Pay dialog, `/manager/reservations` (read-only). Tests in `tests/Feature/{M2_Catalog,M3_Reservations}/`. 

Phase 4 (M4 maintenance) is done. `app/Services/MaintenanceService.php` (submit/assign/updateProgress/resolve, all via `MaintenanceStatus::canTransitionTo()`), `MaintenanceRequestPolicy` (tenant owner may resolve). Tenant submits only for a house they own (`StoreMaintenanceRequest`); PM assigns only active `maintenance_staff` whose expertise matches the category or is `general`, and only while pending/accepted (`AssignStaffRequest` + service); staff queries start from `assignedRequests()`, so other people's requests are 404. Endpoints under `/api/tenant/*`, `/api/staff/*`, `/api/manager/*` (incl. minimal `GET /api/manager/staff`, extended in Phase 5). React: `/tenant/maintenance`, `/staff/requests` (active + history), `/manager/maintenance`, `/manager/maintenance/history`. Tests in `tests/Feature/M4_Maintenance/`. 

Phase 5 (M5 manager + M6 admin) is done. Admin: `Api/Admin/{DashboardController,UserController}` (role:admin middleware only, no policy), rules in `app/Services/UserService.php` (no self-delete/deactivate/role change; delete refused with 422 "deactivate instead" when the user has reservations; deactivating or deleting deletes the user's `sessions` rows; expertise required for staff, cleared for other roles; `UpdateUserRequest` is all-`sometimes` so the toggle sends only `is_active`). `app/Services/StaffService.php`: `releaseOpenRequests()` (accepted/in_progress → pending, unassigned) is called by demote, deactivate, role change away from staff and delete, in the same transaction. Manager: `PropertyManager/{Dashboard,House,Tenant,Staff}Controller` (houses are read-only, R7; collected = all payments, outstanding = balance of `reserved` reservations). React: `pages/admin/*`, `pages/manager/{Dashboard,Houses,Tenants,Staff}.jsx`, `pages/shared/Welcome.jsx` (tenant + prospect dashboards; `/staff` redirects to `/staff/requests`); `DashboardPlaceholder` is gone. Tests in `tests/Feature/{M5_Manager,M6_Admin}/`. Session tests must use `freshRequest()` from `tests/Pest.php`. The next step is Phase 6 (optional enhancements) or Phase 7 (Google login), plan §7.

## Commands (after Phase 0)

```bash
php artisan migrate:fresh --seed && php artisan serve   # repo root, :8000 (base data)
php artisan db:seed --class=DemoSeeder                  # optional demo data, after the base seed
cd frontend && npm run dev                              # :5173
php artisan test
```

Seeded logins: `admin@`, `manager@`, `staff.plumbing@`, `staff.electrical@`, `tenant@`, `prospect@luminest.test` / `password`.
