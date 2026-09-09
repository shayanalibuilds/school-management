# School Management System

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Filament](https://img.shields.io/badge/Filament-v5-2563EB)](https://filamentphp.com)
[![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)](https://livewire.laravel)
[![Tests](https://img.shields.io/badge/tests-255%20passing-2EA043)](#testing)
[![License: MIT](https://img.shields.io/badge/license-MIT-2563EB.svg)](LICENSE)

A complete school management system for **admins**, **teachers**, and
**parents** — two role-separated panels and a public portal that needs no
account at all. Built on [Laravel](https://laravel.com),
[Livewire](https://livewire.laravel), and
[Filament v5](https://filamentphp.com), with custom views composed from
Filament components so every page shares one design system.

Repo: `shayanalibuilds/school-management`

## Screenshots

<div align="center">

**Admin dashboard**

![Admin dashboard](docs/screenshots/admin-dashboard.png)

</div>

| Fill attendance — a whole class at once | Fill exam results — marks per class |
| --- | --- |
| ![Fill attendance](docs/screenshots/fill-attendance.png) | ![Fill exam results](docs/screenshots/fill-exam-results.png) |

| Students — GR #, class, parents | Classes — assign subjects inline |
| --- | --- |
| ![Students table](docs/screenshots/students-table.png) | ![Class subjects](docs/screenshots/class-subjects.png) |

| Parents — link children with one select | Import / Export — CSV in, CSV out |
| --- | --- |
| ![Parent children](docs/screenshots/parent-children.png) | ![Import export](docs/screenshots/import-export.png) |

| Teacher workspace, scoped to assignments | Public portal — no login needed |
| --- | --- |
| ![Staff dashboard](docs/screenshots/staff-dashboard.png) | ![Public home](docs/screenshots/public-home.png) |

| Public results lookup | Public fees with online payment |
| --- | --- |
| ![Public results](docs/screenshots/public-results.png) | ![Public fees](docs/screenshots/public-fees.png) |

## Surfaces

| Who | Where | What |
| --- | --- | --- |
| Admins | `/dashboard` | Full control: academics, people, finance, HR, settings |
| Teachers | `/staff` | A focused workspace scoped to their own assignments |
| Parents | `/` | Public lookups for results, attendance, and fees — no login |

Every create/edit form is a **step-by-step wizard** whose submit buttons
appear only on the final step. Attendance and exam results are entered as a
**whole class in one table**, and every dataset moves in and out through the
built-in **CSV import / export** page.

## Features

**Academics**
- Classes, subjects, and students identified by a single **GR #**
- Student profiles created through a wizard; parents and guardians linked as relations, never duplicated
- Class ↔ subject assignment from both directions, with inline create
- Staff assignments (subject × class) and a change-request workflow

**Attendance**
- Whole-class attendance entry on one screen (admin and teacher pages), with
  colour-coded status buttons — absent red, leave yellow, present blue
- Automated 8:20 AM deadline check — admins are notified when attendance is missing

**Exams**
- Exam results per year and exam type with automatic grades and class positions
- Five-year performance analytics on the dashboard

**Finance**
- Fee structures, per-student fees, and partial payments
- Online payments via **EasyPaisa** and **JazzCash** with sandbox/live environments,
  encrypted credentials, and official receipts
- Payroll and expense tracking with daily, weekly, monthly, yearly, or one-time
  recurrence (admin only)
- Optional **monthly school progress stats**: income from completed payments,
  estimated spending (paid salaries + recurring expenses) and the net result,
  charted over the last 12 months — shown on the dashboard only when the admin
  enables it in App settings

**People & tools**
- ID cards and staff cards, ready to print
- CSV import for students, staff, parents, and guardians; CSV export for ten datasets
- Gateway-agnostic payment settings guarded to admins
- App settings page: feature toggles including "queue everything" (writes run on
  the background queue for stability) and the monthly stats switch

**Public portal**
- Parents look up records by **parent/guardian CNIC** or **student GR #**,
  active students only
- Results (grades or class positions), 30-day attendance history, and fee dues
- Outstanding fees payable in-place through the configured gateway

**Scoped by role, by design**
- Teachers only ever see the classes and subjects assigned to them — the staff
  dashboard, attendance, and results entry are all filtered server-side (fail-closed)
- Finance operations and settings stay admin-only
- Nothing is ever hard-deleted: records are archived (students leave, other
  records turn inactive) and policies deny every delete ability

## Getting started

**Requirements:** PHP 8.3 with `intl`, Composer, Bun, Node.js 22+ (used by
the development server), SQLite. Dependencies are pinned against PHP
**8.3.4** — the PHP version Wasmer Edge ships for PHP apps — so the project
deploys unchanged to Wasmer-compatible runtimes.

```bash
git clone https://github.com/shayanalibuilds/school-management.git
cd school-management

composer setup   # dependencies, .env, app key, SQLite database, migration + seed, frontend assets
composer dev     # server + queue + vite, then opens the browser
```

That is the whole install. When `composer dev` opens the browser, sign in with
a demo account below.

**Demo accounts** (seeded):

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@school.test` | `password` |
| Teacher | `teacher@school.test` | `password` |

The seeder creates 10 classes, staff assignments, students, three years of
exam results, attendance history, and fee records — so every chart and lookup
has data on first run.

## Testing

The project follows test-first development and keeps the pipeline green:

```bash
composer test                # style, types, spelling, typos, refactor, tests
php artisan test             # 203 tests, 985+ assertions
vendor/bin/pint --dirty      # code style
vendor/bin/phpstan analyse   # static analysis
```

## License

Released under the [MIT License](LICENSE).
