# FPL Intelligence

FPL Intelligence is a data-driven Fantasy Premier League decision-support
application built in PHP and MySQL.

The application combines live FPL data, historical evidence, projections,
fixture analysis, squad analysis and decision models to support the main
decisions an FPL manager faces.

## Current Development Status

Current stable release:

**v1.2.0 — Production Readiness & Deployment Preparation**

v1.2.0 is complete and has passed its full release-validation regression.

The release prepares the stable FPL Intelligence application for a future
live-server deployment by establishing tested production contracts for:

- environment-specific configuration
- production database credentials
- secret handling
- public web-root isolation
- production-safe browser error handling
- database deployment
- PHP CLI portability
- scheduled-job failure signalling
- production runtime requirements
- deployment and operational validation

Production environments explicitly use `FPL_APP_ENV=production` and require
database connection values through environment variables.

The production web-server document root must point to `public/`, keeping
application configuration, classes, cron jobs, SQL files and tests outside the
browser-accessible web tree.

The deployment schema is database-name neutral, and production command-line
orchestration no longer depends on the local WAMP PHP installation path.

`DEPLOYMENT.md` provides the provider-neutral deployment guide for the future
live-server deployment.

Actual production hosting configuration, database credentials, DNS, TLS,
scheduler setup and application upload remain intentionally deferred until the
target hosting environment is used.

v1.2.0 does not change production intelligence-model weights, Projection
Confidence thresholds, optimizer objectives, optimizer search widths,
candidate-pool semantics or deterministic tie-break behaviour.

The final v1.2.0 regression suite passes with all 374 test files and all 10,758
assertions passing.

The authoritative committed baseline is the `main` branch after the completed
release is committed and pushed.

See `ROADMAP.md` for the full development history, evidence baseline and release
architecture.

## Main Application Areas

FPL Intelligence currently provides:

- Dashboard and application health
- Fixture analysis
- Team Intelligence
- Player Intelligence
- Player profiles
- Player Form Intelligence
- Expected Points
- multi-gameweek projections
- Market Intelligence
- squad analysis
- Starting XI recommendations
- captain and vice-captain recommendations
- transfer analysis
- transfer planning
- transfer optimisation
- Wildcard Intelligence
- Free Hit Intelligence
- Bench Boost Intelligence
- Triple Captain Intelligence
- recommendation-history infrastructure
- historical backtesting
- model calibration
- data-update health monitoring

The application is designed to remain explainable. Intelligence outputs expose
the evidence and confidence concepts used by the underlying models rather than
presenting recommendations as unexplained rankings.

## Requirements

The project is currently developed locally using:

- Windows
- WAMP
- Apache
- PHP 8.2
- MySQL 5.7
- phpMyAdmin
- Git

The application runtime requires:

- PHP 8.2 or newer
- MySQL 5.7 or newer
- PDO
- PDO MySQL
- JSON support
- PHP HTTP stream support
- `allow_url_fopen`
- PHP CLI
- `proc_open` for coordinated update jobs
- outbound HTTPS access to the FPL API

The current development environment uses the project directory:

`C:\wamp64\www\fpl-intelligence`

Production requirements and deployment validation are documented in
`DEPLOYMENT.md`.

## Installation

Clone or copy the project into the WAMP web root:

`C:\wamp64\www\fpl-intelligence`

Ensure Apache and MySQL are running.

Application configuration is stored under:

`config/`

Database connection settings must match the local MySQL environment.

Do not commit local credentials or other environment-specific secrets.

The public application is served from:

`public/`

For the current local development configuration, the application is available
under:

`http://localhost:8008/fpl-intelligence/public/`

## Database Setup

Database schema files are stored under:

`sql/`

The database contains the live and historical data required by the application,
including:

- teams
- players
- fixtures
- gameweeks
- player fixture history
- player gameweek snapshots
- recommendation candidates
- recommendation snapshots
- update-run history

Create or select the target database first, then import `sql/schema.sql` into
that database.

The deployment schema deliberately does not create or select a hard-coded
database, allowing the database name to remain environment-specific.

The application deliberately separates refreshable live player data from
historical evidence that must remain stable for backtesting and calibration.

Historical tables should not be manually rewritten merely to match current live
FPL state.

## Data Updates

Controlled live-data updates are coordinated through:

`cron/runDataUpdates.php`

The controlled update pipeline currently covers:

- Bootstrap player and team data
- fixtures
- Player Fixture History

Update executions are recorded so the application can distinguish Healthy,
Stale, Partial, Running, Failed and Unavailable data states.

The main dashboard exposes Application Health information derived from this
persisted update history.

For update execution, current local scheduling, manual execution and
troubleshooting instructions, see:

`DATA_UPDATES.md`

Future live-server scheduling and production operational requirements are
documented separately in:

`DEPLOYMENT.md`

## Architecture

The application is organised around several main areas.

### `classes/`

Contains repositories, intelligence services, decision services, models,
optimisers, historical evidence services, backtesting services and supporting
application infrastructure.

### `config/`

Contains application configuration.

### `cron/`

Contains controlled data-update and historical processing entry points.

### `public/`

Contains the browser-facing application pages, shared presentation includes,
CSS and JavaScript.

### `sql/`

Contains database schema and migration SQL.

### `tests/`

Contains browser-friendly regression, integration, real-data, contract and
performance tests.

## Intelligence Architecture

FPL Intelligence separates different forms of evidence rather than treating
every metric as the same concept.

### Player Intelligence

Player Intelligence combines evidence including:

- Player Strength
- Player Value
- Player Availability
- Fixture Intelligence
- Position-Aware Fixture Intelligence

### Player Form Intelligence

Player Form uses persisted per-fixture historical evidence and includes:

- recent fixture windows
- recent appearance windows
- recency weighting
- participation evidence
- performance trends
- minutes trends

Form remains an explainable evidence layer and is not silently substituted for
the core Player Intelligence model.

### Expected Points

Expected Points models projected FPL returns using evidence including:

- projected minutes
- attacking expectation
- clean-sheet expectation
- goalkeeper saves
- defensive contributions
- expected bonus
- fixture and opponent context

The same underlying projection model is reused for multi-gameweek planning with
fixture-specific context.

### Decision Systems

Higher-level decision systems consume the appropriate intelligence evidence for:

- Starting XI selection
- captaincy
- transfers
- Wildcard
- Free Hit
- Bench Boost
- Triple Captain

Decision systems retain deterministic tie-breaking and explicit legality
constraints.

## Confidence Terminology

Several confidence concepts exist and must remain distinct.

### Sample Confidence

Describes the amount of supporting performance sample available.

### Effective / Decision Confidence

Describes confidence after the relevant decision context and supporting
evidence have been considered.

### Projection Confidence

Describes the evidence supporting an Expected Points projection.

### Reliability Confidence

Used where an optimisation or recommendation needs to communicate the
reliability of the evidence supporting that result.

These labels should not be merged merely because they all use the word
"confidence".

## Historical Evidence and Backtesting

Historical evidence is preserved separately from live FPL state.

The application uses historical data for:

- recommendation snapshots
- realised player outcomes
- projection backtesting
- ranking backtesting
- Starting XI backtesting
- captaincy backtesting
- transfer backtesting
- model calibration

Backtesting must compare recommendations against evidence that genuinely
existed at recommendation time.

Current live values must not be substituted retrospectively for unavailable
historical evidence.

## Testing

Tests are stored under:

`tests/`

Individual tests are browser-friendly and can be run directly through the local
web server.

Example:

`http://localhost:8008/fpl-intelligence/tests/PlayerFixtureHistoryRecentRetrievalTest.php`

The complete regression suite is run through:

`http://localhost:8008/fpl-intelligence/tests/runAllTests.php`

`runAllTests.php` automatically discovers every `*Test.php` file and executes
each test in its own PHP CLI process.

A release is not considered validated while any test file fails, produces an
execution error, or reports failed assertions.

The final v1.2.0 release validation completed with:

- 374 test files
- 374 test files passed
- 0 test files failed
- 0 test files with errors
- 10,758 assertions passed
- 0 assertions failed
- 307.062 seconds total runtime

## Development Workflow

Development follows a test-driven workflow:

1. identify one concrete behaviour or problem
2. add or update focused regression coverage
3. confirm the intended controlled RED state
4. make the minimum production change
5. rerun the focused test
6. confirm GREEN
7. run relevant downstream regression coverage
8. visually verify browser-facing changes where appropriate
9. run the complete regression suite at meaningful checkpoints

Unexpected failures are investigated before further production changes are
made.

Tests are not changed simply because they fail. The failure must first be
classified as either:

- incorrect production behaviour
- a stale or brittle test expectation

## Development Principles

The project follows several rules intended to protect model behaviour and
historical integrity:

- avoid speculative production changes
- keep changes small and testable
- preserve deterministic behaviour
- do not weaken optimiser search purely for performance
- do not change model weights as a performance optimisation
- avoid brittle assumptions about changing live FPL data
- preserve genuine historical evidence
- keep confidence concepts semantically distinct
- remove temporary diagnostics before release
- prefer shared evidence and request-scoped caching only where the correctness
  boundary is understood

## Release Process

Before a release:

1. complete focused development and regression testing
2. run the complete test suite
3. review local changes and untracked files
4. remove temporary diagnostics
5. update project documentation
6. update `CHANGELOG.md`
7. update displayed application version where applicable
8. perform final regression validation
9. commit locally
10. push the completed release to `main`

## Additional Documentation

- `ROADMAP.md` — development history, architecture progress and release milestones
- `CHANGELOG.md` — release-by-release changes
- `DATA_UPDATES.md` — data-update pipeline, local scheduling and troubleshooting
- `DEPLOYMENT.md` — production deployment requirements, security and validation

## v1.0 Release

v1.0.0 established the first stable end-to-end FPL decision-support release,
covering:

- player and team evaluation
- fixtures
- recent form
- Expected Points
- squad analysis
- Starting XI
- captaincy
- transfers
- multi-gameweek planning
- Wildcard
- Free Hit
- Bench Boost
- Triple Captain
- blank and double gameweeks
- Market Intelligence
- recommendation history
- model backtesting and calibration
- explainable recommendations
- reliable data updates and application-health monitoring

The v1.0.0 release completed its architectural acceptance audit and passed the
complete regression suite before publication.

## v1.1 Release

v1.1.0 establishes the first post-v1.0 intelligence-quality and outcome
evaluation baseline.

The release validates direct historical evaluation of preserved Expected
Points, Projection Confidence, Player Intelligence rankings, Starting XI,
Captain Intelligence, transfer recommendations and manager-facing Transfer
Decision Intelligence.

It also adds the production historical-evidence lifecycle required to accumulate
future immutable recommendation and player-state evidence.

The current baseline contains one authoritative Ready gameweek. The evidence
therefore supports continued observation rather than production model changes.

Future development should build on the stable v1.1.0 baseline and allow the
historical sample to grow before intelligence weights or confidence thresholds
are reconsidered. Any future model change should be supported by evidence
across a materially broader historical sample and protected by regression
tests.


## v1.2 Release

v1.2.0 establishes the production-readiness and deployment-preparation baseline
for FPL Intelligence.

The release introduces tested production contracts for environment
configuration, secret handling, web-root isolation, browser-safe error
presentation, database deployment, PHP CLI portability, scheduler failure
signalling and runtime acceptance.

The deployment schema is environment-neutral, and the live-data and historical
evidence runners no longer depend on hard-coded WAMP PHP paths.

`DEPLOYMENT.md` defines the provider-neutral process that will be followed when
the application is eventually moved to the live server.

The release deliberately does not configure a real production server,
production database credentials, DNS, TLS or production scheduler. Those steps
remain deferred until the target hosting environment is known.

No production intelligence-model behaviour or historical evidence was changed
as part of the deployment-preparation work.

The final v1.2.0 complete regression suite passed:

- 374 test files
- 374 test files passed
- 0 test files failed
- 0 test files with errors
- 10,758 assertions passed
- 0 assertions failed
- 307.062 seconds total runtime