# CLAUDE.md

## Project Overview

Laravel 13 application using the React starter kit with Inertia.js, TypeScript, Tailwind CSS v4, and Radix UI components. Authentication is handled by Laravel Fortify with passkey support.

## Tech Stack

- **Backend:** PHP 8.3+, Laravel 13, Inertia.js (server-side)
- **Frontend:** React 19, TypeScript, Tailwind CSS v4, Radix UI, Vite 8
- **Auth:** Laravel Fortify + passkeys (`@laravel/passkeys`)
- **Database:** MySQL 8.0 (local via Docker, production via Laravel Cloud)
- **Type routing:** Laravel Wayfinder (generates typed route helpers from PHP routes)
- **Code quality:** Pint (PHP), ESLint + Prettier (JS/TS), PHPStan/Larastan (static analysis)

## Database
- posts: id, category_id, slug, image_path (nullable), status (draft/published), timestamps
- post_translations: id, post_id, language (en/bn), title, body, timestamps (unique on post_id+language)
- categories: id, name, slug, timestamps

Post images are stored on the `public` disk under `storage/app/public/posts/` (served via the `public/storage` symlink — run `php artisan storage:link` after a fresh clone). Use `$post->imageUrl()` to get the public URL.

## Rules
- Always use Repository pattern
- Validate all inputs with Form Requests
- Keep controllers thin
- Write migrations, not raw SQL

## Development Environment

### Prerequisites
- PHP 8.5 (`C:\Users\sneha\.config\herd-lite\bin` must be on PATH)
- Composer (same PATH as above)
- Node.js 24 LTS + npm
- Docker Desktop (for MySQL)

### Start the database
```bash
docker compose up -d
```

### Start the dev server (runs PHP, queue worker, and Vite concurrently)
```bash
composer run dev
```

App runs at **http://localhost:8000**

### Stop the database
```bash
docker compose down
```

## Database

**Local and production both use MySQL 8.0.** Do not use SQLite-specific syntax.

Local credentials (see `.env`):
- Host: `127.0.0.1:3306`
- Database: `laravel`
- User: `laravel` / Password: `secret`

### Raw queries
Raw queries must use MySQL syntax only — SQLite is not used anywhere. Avoid SQLite-specific functions (`strftime`, `||` string concat, etc.). Prefer Query Builder or Eloquent; use raw queries only when necessary.

### Migrations
```bash
php artisan migrate
php artisan migrate:rollback
```

## Common Commands

| Task | Command |
|------|---------|
| Dev server | `composer run dev` |
| Run tests | `composer run test` |
| PHP lint (fix) | `composer run lint` |
| PHP lint (check) | `composer run lint:check` |
| Static analysis | `composer run types:check` |
| JS/TS format | `npm run format` |
| JS/TS lint | `npm run lint` |
| TS type check | `npm run types:check` |
| Full CI check | `composer run ci:check` |
| Build assets | `npm run build` |
| Artisan tinker | `php artisan tinker` |

## Project Structure

```
app/
  Actions/Fortify/     # Auth action classes
  Concerns/            # Shared traits (password/profile validation rules)
  Http/Controllers/    # Route controllers
  Http/Requests/       # Form requests
  Models/              # Eloquent models
  Providers/           # AppServiceProvider, FortifyServiceProvider
resources/js/
  actions/             # Inertia form action helpers
  components/          # Shared React components (UI primitives)
  hooks/               # Custom React hooks
  layouts/             # Page layout components
  pages/               # Inertia page components (map to routes)
  routes/              # Wayfinder-generated typed route helpers
  types/               # TypeScript type definitions
routes/
  web.php              # Main web routes
  settings.php         # Settings-related routes
database/
  migrations/          # Database migrations
  factories/           # Model factories for testing
  seeders/             # Database seeders
```

## Conventions

- **Page components** live in `resources/js/pages/` and are referenced by Inertia from controllers via `Inertia::render('PageName', $props)`.
- **Route linking** uses Wayfinder helpers (auto-generated in `resources/js/routes/`) — do not hardcode URL strings.
- **Forms** use Inertia's `useForm` hook or action helpers in `resources/js/actions/`.
- **UI components** are Radix UI primitives wrapped in `resources/js/components/ui/` — reuse these before creating new ones.
- **PHP style** follows Laravel Pint (PSR-12 based) — run `composer run lint` before committing.
- **No SQLite syntax** anywhere — all queries must be MySQL-compatible.

## Deployment

Deploy to [Laravel Cloud](https://cloud.laravel.com). The Laravel Cloud CLI (`cloud`) is installed globally via Composer.
