# LDS-LIVE — Lead Distribution System

LDS-LIVE is a Laravel-based lead management and distribution application. It receives lead/contact events, evaluates campaign and agent eligibility, records processing activity, and supports forwarding assigned contact data to GoHighLevel (GHL).

> This guide describes the implementation visible in the repository. It does not imply that every integration or deployment has been tested end to end.

## Core capabilities

- Campaign management and campaign-to-agent configuration.
- Agent profiles, lead types, state/carrier eligibility, delivery limits, and destination settings.
- Inbound webhook handling for contact creation and survey submissions.
- Agent eligibility and assignment using lead type, state, daily/monthly/total limits, priority, and weightage-related counters.
- Contact processing, reserve handling, and operational logging.
- GoHighLevel contact handoff.
- Administrative dashboard, reporting, and user management.
- External Medicare agent-directory lookup.

## Technology

The repository's composer.json declares PHP ^8.1, Laravel ^10.48, Laravel Sanctum ^3.3, Laravel UI ^4.5, Guzzle ^7.2, Predis ^2.3, and Yajra Laravel DataTables ^10.11. The application timezone is configured as America/Chicago.

## Application areas

| Area | Responsibility |
|---|---|
| Agents | Profiles, lead types, limits, states, carriers, and destinations |
| Campaigns | Campaign definitions and campaign-agent configuration |
| Contacts | Contact records and assigned lead history |
| Processing / reserve | Intermediate processing and contacts without an eligible assignment |
| Webhooks and jobs | External event intake and asynchronous processing |
| Dashboard | Operational statistics and administrative views |

Further documentation:
- [Architecture](docs/architecture.md)
- [Installation](docs/installation.md)
- [Security checklist](docs/security-checklist.md)

## Local setup

The repository currently does not include a tracked .env.example or composer.lock. Use a secure local environment template and confirm required variables from the authorized deployment configuration.

1. Install PHP 8.1+, required Laravel PHP extensions, Composer, and a supported database.
2. Clone the repository and install dependencies:

   ```bash
   git clone https://github.com/sajjadahmad2/LDS-LIVE.git
   cd LDS-LIVE
   composer install
   ```

3. Create a local .env file from a trusted template. Configure application, database, session, cache, queue, mail, and integration settings.
4. Generate an application key:

   ```bash
   php artisan key:generate
   ```

5. After confirming the configured database is a development database, run migrations:

   ```bash
   php artisan migrate
   ```

6. If required, create the storage link and start the development server:

   ```bash
   php artisan storage:link
   php artisan serve
   ```

7. If the queue connection is asynchronous, run a worker:

   ```bash
   php artisan queue:work
   ```

Do not run migrations against production as part of initial setup. Confirm frontend build requirements from the repository's package manifest before building assets.

## Operational notes

- Configure and monitor Laravel queue workers because webhook processing uses queued jobs.
- Keep GHL and external agent-portal credentials in environment configuration or a secrets manager.
- Review access controls for log downloads, database backup downloads, cleanup actions, and contact-data lookup routes before public exposure.
- The current .gitignore excludes composer.lock. A reviewed lock file should normally be committed for reproducible application deployments.
- Never commit .env files, API tokens, database passwords, customer records, production logs, or database dumps.

## Testing

The repository declares PHPUnit development dependencies. Run the available suite with:

```bash
php artisan test
```

Test coverage and end-to-end integration behavior have not been independently verified as part of this documentation update.

## License

A project-specific LICENSE file was not identified during this review. Confirm licensing terms before reuse or redistribution.
