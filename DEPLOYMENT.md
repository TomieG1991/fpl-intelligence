# FPL Intelligence — Production Deployment

This document defines the production deployment requirements for FPL Intelligence.

It is intentionally hosting-provider neutral. Exact server paths, database names,
scheduler configuration, domain settings and SSL configuration should be chosen
when the production hosting environment is known.

This document prepares the application for deployment. It does not mean that the
current development installation has been deployed to a live server.

---

## 1. Production Architecture

FPL Intelligence consists of:

- PHP 8.2+ application code
- MySQL 5.7+ database
- browser-facing files under `public/`
- non-public application code under `classes/`
- environment-driven configuration under `config/`
- production command-line jobs under `cron/`
- database deployment files under `sql/`
- automated tests under `tests/`

The production web server document root must point to:

```text
/path/to/fpl-intelligence/public
```

It must not point to the project root:

```text
/path/to/fpl-intelligence
```

This keeps configuration, cron jobs, SQL files, tests and internal application
classes outside the browser-accessible web tree.

---

## 2. Runtime Requirements

The production environment requires:

- PHP 8.2 or newer
- MySQL 5.7 or newer
- PDO
- PDO MySQL
- JSON support
- PHP HTTP stream support
- `allow_url_fopen` enabled
- PHP CLI
- `proc_open` available for coordinated update jobs
- outbound HTTPS access to the Fantasy Premier League API

The runtime requirements can be checked using:

```text
tests/ProductionRuntimeRequirementsTest.php
```

The tests directory is not part of the production public web root.

---

## 3. Application Environment

Production must explicitly set:

```text
FPL_APP_ENV=production
```

When the application is not explicitly configured as production, the runtime
configuration defaults to:

```text
development
```

The development default exists for the local WAMP installation and must not be
relied upon for the live deployment.

---

## 4. Database Configuration

Production database credentials must be supplied through environment variables:

```text
FPL_DB_HOST
FPL_DB_NAME
FPL_DB_USERNAME
FPL_DB_PASSWORD
```

When:

```text
FPL_APP_ENV=production
```

all four database environment variables are required.

The application fails closed if any required production database value is
missing or empty.

Do not place production database credentials in committed source files.

The tracked:

```text
config/config.php
```

contains the environment-driven runtime configuration contract and does not
contain production secrets.

The example configuration is available at:

```text
config/config.example.php
```

Optional machine-specific configuration files such as:

```text
config/config.local.php
```

remain excluded from version control.

Environment files are also excluded from version control.

---

## 5. FPL API Configuration

By default, FPL Intelligence uses:

```text
https://fantasy.premierleague.com/api/
```

The API base URL can be overridden using:

```text
FPL_API_BASE_URL
```

The production server must allow outbound HTTPS requests to the configured API
endpoint.

No FPL API credentials are currently required.

---

## 6. Database Deployment

The deployment schema is:

```text
sql/schema.sql
```

The schema is intentionally database-name neutral.

It does not:

- create a database
- select a hard-coded database
- assume the database is named `fpl_intelligence`

Create the production database using the facilities provided by the hosting
environment, then import `sql/schema.sql` into that selected database.

The configured:

```text
FPL_DB_NAME
```

must identify the same database.

The schema uses:

- InnoDB
- utf8mb4
- utf8mb4_unicode_ci

The schema uses idempotent table creation so an existing table is not recreated
merely because the schema is imported again.

Schema deployment safety is protected by:

```text
tests/DatabaseDeploymentSchemaTest.php
```

Do not import development data into production unless that is an intentional
deployment decision.

Historical evidence should not be manufactured or rewritten merely to reproduce
the state of the development database.

---

## 7. Database User Permissions

Use a dedicated production database user.

The application database user should receive only the permissions required by
the application.

The normal application does not require permission to create databases.

The database itself should be created separately through the production hosting
environment before the schema is imported.

Avoid using a MySQL root or administrative account as the production
application user.

---

## 8. Web Server Document Root

The browser-facing application is contained in:

```text
public/
```

Configure the production domain or virtual host so its document root points
directly to that directory.

For example:

```text
/path/to/fpl-intelligence/public
```

Do not expose the repository root as the website document root.

The following directories must remain outside the public web tree:

```text
classes/
config/
cron/
sql/
tests/
```

Repository documentation and development files should likewise not become
browser-accessible application routes.

The application uses relative internal navigation and does not depend on the
local WAMP URL or the development project directory.

---

## 9. HTTPS

The live application should be served over HTTPS.

Configure the production domain and TLS certificate through the hosting
environment.

HTTP requests should be redirected to HTTPS where supported by the production
web server.

Exact certificate, domain and redirect configuration is deliberately deferred
until the hosting environment is known.

---

## 10. PHP Error Handling

Production PHP should be configured with:

```text
display_errors = Off
log_errors = On
```

Technical errors should be written to the server's PHP error log rather than
displayed to visitors.

Browser-facing application error handling uses the application environment to
avoid exposing raw exception details in production.

Development mode may retain technical exception detail for local diagnosis.

CLI jobs deliberately retain useful diagnostic output because their output is
intended for operational logs and scheduler monitoring rather than public web
pages.

---

## 11. File Permissions

Use the minimum filesystem permissions required by the web server and PHP
runtime.

Application source code should not be generally writable by the web process.

The current application does not require broad write access to the project
directory.

Do not grant blanket writable permissions to:

```text
classes/
config/
cron/
public/
sql/
tests/
```

Any hosting-specific writable locations should be introduced only when a real
runtime requirement exists.

---

## 12. Live Data Update Pipeline

The production live-data update entry point is:

```text
cron/runDataUpdates.php
```

It coordinates:

1. Bootstrap player and team data
2. Fixtures
3. Player Fixture History

The runner uses the active PHP CLI executable rather than a hard-coded WAMP
installation path.

A production invocation will take the general form:

```text
/path/to/php /path/to/fpl-intelligence/cron/runDataUpdates.php
```

Exact paths must be determined on the production server.

The job returns a successful process exit status only when the controlled update
completes successfully.

Failed or incomplete update execution returns a non-zero process status so the
production scheduler can detect the problem.

Do not configure the real production schedule until the live server environment
is known.

Further operational information is maintained in:

```text
DATA_UPDATES.md
```

---

## 13. Historical Evidence Lifecycle

Historical evidence uses a separate production entry point:

```text
cron/runHistoricalEvidenceLifecycle.php
```

Its order is:

1. Promote eligible recommendation candidates
2. Promote eligible player gameweek snapshot candidates
3. Capture the latest player gameweek snapshot candidates

The lifecycle remains separate from the live-data update coordinator.

A production invocation will take the general form:

```text
/path/to/php /path/to/fpl-intelligence/cron/runHistoricalEvidenceLifecycle.php
```

The lifecycle returns a non-zero process status when it fails.

A suitable production cadence can be configured after deployment. The current
recommended cadence is hourly, subject to the facilities available on the live
server.

Do not configure the real scheduler during deployment preparation.

Recommendation candidate capture remains dependent on genuine manager-specific
production recommendation generation and is not fabricated by this unattended
lifecycle.

---

## 14. Production Scheduling

The current Windows Task Scheduler configuration belongs to the local WAMP
development environment.

The live server should use its own scheduling mechanism.

Depending on the hosting environment this may be:

- cron
- a hosting control-panel scheduler
- another server-supported scheduled-task facility

Production scheduling must preserve the separation between:

```text
cron/runDataUpdates.php
```

and:

```text
cron/runHistoricalEvidenceLifecycle.php
```

Do not assume Windows paths or the local WAMP PHP installation on production.

---

## 15. Data Health

The application uses persisted update-run evidence to report application data
health.

The current freshness threshold is:

```text
86400 seconds
```

or 24 hours.

Health states include:

- Healthy
- Stale
- Partial
- Running
- Failed
- Unavailable

After production scheduling is configured, the dashboard Application Health
section should be used alongside scheduler results to verify successful data
updates.

A scheduler exit code alone does not replace application-level data-health
verification.

---

## 16. Production Security Checklist

Before the live application is made publicly accessible, verify:

- `FPL_APP_ENV` is `production`
- production database credentials are supplied through environment variables
- no production credentials are committed to Git
- the web document root is `public/`
- `classes/` is not browser accessible
- `config/` is not browser accessible
- `cron/` is not browser accessible
- `sql/` is not browser accessible
- `tests/` is not browser accessible
- directory listing is disabled
- HTTPS is enabled
- PHP `display_errors` is disabled
- PHP error logging is enabled
- the database uses a dedicated non-root user
- filesystem permissions follow least privilege
- outbound HTTPS access to the FPL API works
- PHP CLI is available
- `proc_open` is available
- production cron/scheduler jobs do not overlap unnecessarily

---

## 17. Pre-Deployment Validation

Before uploading to the live server, run the complete automated test suite
locally.

The focused production-readiness tests include:

```text
tests/ProductionWebRootContractTest.php
tests/ProductionConfigurationContractTest.php
tests/DatabaseDeploymentSchemaTest.php
tests/ProductionRuntimeRequirementsTest.php
tests/DataUpdateCronPortabilityTest.php
tests/HistoricalEvidenceLifecycleCronTest.php
tests/PhpCliExecutableLocatorTest.php
```

All focused tests and the complete regression suite should be green before the
release is treated as deployment-ready.

---

## 18. Post-Upload Validation

After the files and database have eventually been deployed, but before the
application is considered live, verify the production environment itself.

At minimum:

1. Confirm the domain serves the application from `public/`.
2. Confirm HTTPS is active.
3. Confirm non-public project directories cannot be requested through the web.
4. Confirm production environment variables are available to PHP.
5. Confirm the application connects to the intended production database.
6. Confirm the dashboard loads without exposing PHP errors.
7. Confirm FPL API access succeeds.
8. Run the live-data update runner manually once.
9. Verify its process exit status.
10. Verify Application Health records the successful updates.
11. Run the historical-evidence lifecycle manually once.
12. Verify its process exit status.
13. Verify no unexpected historical evidence is created or overwritten.
14. Configure production scheduling only after the manual runs succeed.
15. Recheck Application Health after the first scheduled execution.

---

## 19. Deployment Rollback Preparation

Before replacing an existing production release:

- back up the production database
- preserve the currently deployed application release
- record the active environment configuration
- avoid deleting historical evidence
- avoid destructive schema operations unless explicitly required by a future
  migration

If deployment validation fails, restore the previous application release and,
where necessary, the corresponding database backup before investigating further.

---

## 20. Deferred Live-Server Decisions

The following are intentionally not decided by v1.2.0 deployment preparation:

- production server filesystem paths
- hosting control-panel implementation
- production database name
- production database username
- production database password
- domain/DNS configuration
- TLS certificate provider
- exact scheduler implementation
- exact live-data update cadence
- production upload mechanism

These decisions depend on the live hosting environment and should be made when
the actual deployment begins.

---

## 21. Deployment Readiness Boundary

v1.2.0 prepares FPL Intelligence for a future production deployment.

Completion of this release means the application has documented and tested
deployment contracts for:

- environment configuration
- production secrets
- public web-root isolation
- production-safe browser error handling
- database deployment
- PHP/runtime requirements
- CLI portability
- scheduled-job failure signalling
- historical-evidence lifecycle execution
- deployment validation

It does not mean the application has already been uploaded, scheduled or made
publicly accessible on a live server.