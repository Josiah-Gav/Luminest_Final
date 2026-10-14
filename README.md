# Luminest

Real-estate management system for one subdivision: house reservations and purchases, maintenance requests, and user administration. Built as a **Laravel 12 REST API** (repo root) and a **React SPA** (`frontend/`) on **MySQL**, for the ITELEC 4100 final project.

- How it is built: [docs/LARAVEL_REACT_PLAN.md](docs/LARAVEL_REACT_PLAN.md)
- Business rules of the original app: [docs/SYSTEM_DOCUMENTATION.md](docs/SYSTEM_DOCUMENTATION.md)

## Prerequisites

| Tool | Version |
|---|---|
| PHP | 8.2+ with `pdo_mysql` and `pdo_sqlite` (tests run on in-memory SQLite) |
| Composer | 2.x |
| Node.js / npm | Node 20.19+ or 22.12+ (Vite 8) |
| MySQL | XAMPP's MySQL (MariaDB 10.4) on port **3309**, with an empty database named `luminest_v2` |

Create the database once, for example in phpMyAdmin or MySQL Workbench:

```sql
CREATE DATABASE luminest_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Setup

**Backend** (repo root):

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Then edit `.env` and set `DB_PORT`, `DB_USERNAME` and `DB_PASSWORD` for your MySQL server, and create and fill the tables:

```bash
php artisan migrate:fresh --seed                # base data
php artisan db:seed --class=DemoSeeder          # optional demo data
```

- **Base data** (`migrate:fresh --seed`): the 4 house models, the 850 units (all available) and one login per role. Reservations, payments and maintenance requests start empty.
- **Demo data** (`DemoSeeder`, optional, run after the base data): a paid purchase by `tenant@` (the house becomes sold), a cancelled and an active reservation by `prospect@` with payments, and maintenance requests in every status. Use it before a demo or recording. Running it twice does nothing; start again with `migrate:fresh --seed`.

Logins (password `password`): `admin@`, `manager@`, `staff.plumbing@`, `staff.electrical@`, `tenant@` and `prospect@luminest.test`.

**Frontend:**

```bash
cd frontend
cp .env.example .env      # VITE_API_URL=http://localhost:8000
npm install
```

## Run

Use two terminals:

```bash
php artisan serve                 # API on http://localhost:8000
cd frontend && npm run dev        # app on http://localhost:5173
```

Open http://localhost:5173. Use `localhost` for both apps, never `127.0.0.1` for one of them: Sanctum's login cookies only work when both run on the same host.

## Quality checks

```bash
./vendor/bin/pint && php artisan test     # repo root: code style + Pest tests
cd frontend && npm run lint               # ESLint
cd frontend && npm run format             # Prettier (also sorts Tailwind classes)
```
