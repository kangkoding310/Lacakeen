# Lacakeen

Lacakeen is a full-stack project management workspace built with Laravel 13, Vue 3, Inertia.js, PostgreSQL, and Tailwind CSS. It includes dynamic Kanban workflows, task collaboration, calendar planning, chat, reporting, members, notifications, workflow automation, integrations, and workspace settings.

## Requirements

- PHP 8.4+
- Composer 2
- Node.js 22+
- PostgreSQL 14+

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database named `lacakeen`, update the `DB_*` values in `.env`, then run:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
composer run dev
```

Demo account:

```text
Email: david@lacakeen.test
Password: password
```

## Production processes

Run the queue worker for invitations, task-assignment emails, and due-date reminders:

```bash
php artisan queue:work --tries=3
```

Run Laravel's scheduler every minute. The application dispatches due-date reminders daily at 08:00:

```cron
* * * * * cd /path/to/lacakeen && php artisan schedule:run >> /dev/null 2>&1
```

OAuth integrations and realtime broadcasting are intentionally adapter-ready placeholders; add provider credentials or Reverb when deploying those services.

## Quality checks

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

The test suite covers authentication, page rendering, task code generation, authorization, and Kanban movement. Laravel 13 currently ships with `laravel/pao`; Pest's Laravel plugin does not yet declare Laravel 13 compatibility, so the runnable suite uses Laravel's PHPUnit-compatible runner.
