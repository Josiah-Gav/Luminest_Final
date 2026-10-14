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

Phase 0 (scaffold) is done: Sanctum SPA cookie auth, CORS, Pest 3, and the React app in `frontend/` (React 19, Vite 8, Tailwind v4, ESLint + Prettier). There are no app migrations or models yet, and `SESSION_DRIVER=file` until the Phase 1 tables exist. The next step is Phase 1 (Database, plan §7).

## Commands (after Phase 0)

```bash
php artisan migrate:fresh --seed && php artisan serve   # repo root, :8000
cd frontend && npm run dev                              # :5173
php artisan test
```

Seeded logins: `admin@`, `manager@`, `staff.plumbing@`, `staff.electrical@`, `tenant@`, `prospect@luminest.test` / `password`.
