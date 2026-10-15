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

## Google login setup

"Continue with Google" is optional; without these keys the rest of the app works as usual.

1. In the [Google Cloud Console](https://console.cloud.google.com/) create a project (or pick one), then open **APIs & Services → OAuth consent screen** and set it up (External, add your Google account as a test user).
2. Open **Credentials → Create credentials → OAuth client ID**, application type **Web application**.
3. Under **Authorized redirect URIs** add `http://localhost:8000/auth/google/callback`.
4. Copy the client ID and secret into the repo-root `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

5. Restart `php artisan serve`. The redirect URI in `.env` must match the one in the Console exactly.

New Google users become prospects. A Google account whose email already exists is linked to that user, but only when Google reports the email as verified. Deactivated accounts cannot log in this way.

## Quality checks

```bash
./vendor/bin/pint && php artisan test     # repo root: code style + Pest tests
cd frontend && npm run lint               # ESLint
cd frontend && npm run format             # Prettier (also sorts Tailwind classes)
```
