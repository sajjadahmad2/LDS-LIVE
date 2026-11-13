# Installation and Configuration

This is a cautious local setup outline. Confirm environment variables, database schema, queue backend, and integration credentials from the authorized deployment configuration.

## Requirements

Based on composer.json:
- PHP 8.1 or later, with extensions required by Laravel 10 and its dependencies.
- Composer.
- A database supported by the configured Laravel database driver.
- A queue backend appropriate to the configured queue connection.
- Node.js/npm may be required if frontend asset manifests are present.

## Install

```bash
git clone https://github.com/sajjadahmad2/LDS-LIVE.git
cd LDS-LIVE
composer install
```

The current .gitignore excludes composer.lock. Dependency resolution may select versions allowed by Composer constraints rather than a previously locked set. For reproducible deployments, generate and review a lock file in a controlled development environment and commit it.

## Environment

There is no tracked .env.example in the reviewed repository. Create a local .env from a trusted template and configure application, database, session, cache, queue, mail, and integration settings.

Never use production credentials on a developer workstation or commit an .env file.

## Application key and database

```bash
php artisan key:generate
php artisan migrate
```

Run migrations only against a local/disposable development database until the migration set and its effects have been reviewed. Do not run destructive schema commands against production during initial setup.

## Storage and runtime

```bash
php artisan storage:link
php artisan serve
```

If the configured queue connection is asynchronous, start a worker:

```bash
php artisan queue:work
```

Production workers should be supervised and restarted during deployments. Monitor failed jobs and application logs.

## Frontend assets

If package.json and a corresponding lock file are present, install the matching Node dependencies and use scripts declared in package.json. Confirm the manifest before assuming a build command.

## Integrations

The application contains code paths for GoHighLevel handoff and an external Medicare agent directory. Obtain credentials and endpoint settings from the authorized system owner. Store them in environment configuration or a secrets manager; do not commit tokens, private URLs, or customer data.

## Production readiness

- Set APP_ENV=production and APP_DEBUG=false.
- Use HTTPS and secure cookies.
- Confirm database backups and restore procedures.
- Protect inbound webhooks with the provider's expected verification mechanism.
- Verify role and ownership checks on administrative routes.
- Review routes exposing logs, backups, cleanup operations, or contact data.
- Configure queue workers, retries, failed-job monitoring, and process supervision.
- Commit a reviewed composer.lock and run automated tests.
