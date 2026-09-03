# School Management System

A school management system for admins, teachers, and parents — built on
[Laravel](https://laravel.com), [Livewire](https://livewire.laravel),
[Livewire Flux](https://fluxui.dev), and [Filament](https://filamentphp.com).

Repo: `shayanalibuilds/school-management-system`

## Surfaces

| Who | Where | How |
| --- | --- | --- |
| Visitors / parents / students | `/` and public check pages | Livewire Volt + Flux, no auth (parent/guardian CNIC + phone lookup) |
| Admin | `/dashboard` | Filament panel (guard `admin`, `admins` table) |
| Teacher / staff | `/staff` | Filament panel (guard `staff`, `staffs` table) |

Parents, guardians, and students never register or log in. Public pages resolve
their children via parent/guardian CNIC + phone number.

## Stack

- PHP 8.4, Laravel 13, SQLite
- Livewire 4, Volt single-file components (`⚡` prefix), Flux UI 2, Blaze
- Filament 5 (two panels: `admin`, `staff`)
- Pest 5, PHPStan/Larastan (max), Pint, Rector, Peck

## Getting started

```bash
composer setup          # install, .env, key, sqlite, migrate, seed, assets
composer dev            # server + queue + logs + vite
```

Seeded logins (local):

- Admin: `admin@school.test` / `password`
- Staff: `teacher@school.test` / `password`

## Quality gates

```bash
composer test           # typos + pest + lint + phpstan + rector
composer fix            # auto-fix everything
vendor/bin/pint --dirty # format changed PHP files
```
