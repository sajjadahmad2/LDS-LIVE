# Security and Release Checklist

This checklist is based on a source-level review. It is not a penetration test or a complete security audit.

## Review before public production use

- **Operational routes:** Review routes that clear caches, delete records/log files, or perform maintenance. Restrict them to authorized administrators and use appropriate HTTP methods and CSRF protections.
- **Log and backup downloads:** Ensure log archives and SQL backups are not downloadable without strong authorization. Prefer private storage and short-lived authenticated download links.
- **Contact-data lookup:** Routes returning stored contact payloads should require authentication and enforce role/tenant ownership. An email address alone should not act as authorization.
- **Webhook verification:** Confirm signature/shared-secret validation, replay protection, request validation, and rate limiting.
- **Agent reassignment:** Verify the acting user may access both the contact and destination agent before changing assignments.
- **External integrations:** Keep credentials out of source control and redact tokens and personal data from logs.
- **Database cleanup:** Verify retention rules and backups before deleting processing, log, or reserve-contact records.
- **Debugging:** Disable detailed exception output in production.

## Repository hygiene

- Keep .env files, private keys, credentials, production logs, SQL dumps, customer exports, and uploaded personal data out of Git.
- Review repository history for secrets, not only the latest tree. Rotate any credential that has ever been committed.
- The current .gitignore excludes composer.lock. For an application deployment, commit a reviewed lock file to make dependency installation reproducible.
- Add a project-specific license only after the owner confirms the intended terms.

## Release gate

Do not mark this checklist complete until each item has been verified against the deployed environment and full route/controller/middleware configuration.
