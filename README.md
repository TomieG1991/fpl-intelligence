# FPL Intelligence

FPL Intelligence is a data-driven Fantasy Premier League decision-support
application built in PHP and MySQL.

The application combines live FPL data, historical evidence, projections,
fixture analysis, squad analysis and decision models to support the main
decisions an FPL manager faces.

## Current Development Status

Current stable release:

**v0.40.0 — v1.0 Release Candidate & End-to-End Acceptance**

v0.40.0 is complete and has been committed and pushed to `main`.

Current release preparation:

**v1.0.0 — FPL Intelligence**

The v1.0 roadmap criteria have been satisfied. The v0.40.0 release-candidate
milestone completed the architectural contract audit, end-to-end acceptance
review and complete regression validation required before the first stable
v1.0 release.

The validated release-candidate regression suite passes with all 357 test files
and all 10,523 assertions passing.

No production defect or missing v1.0 capability was identified during the
acceptance audit. The identified Wildcard real-data acceptance-coverage gap was
closed before final v1.0 release preparation.

The authoritative committed baseline is the `main` branch. v1.0.0 is currently
in final release preparation and will become the stable release once the final
release changes have been validated, committed and pushed.

See `ROADMAP.md` for the full development history and v1.0 release criteria.

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

The project is developed locally using:

- Windows
- WAMP
- Apache
- PHP 8.2
- MySQL 5.7
- phpMyAdmin
- Git

The current development environment uses the project directory:

`C:\wamp64\www\fpl-intelligence`

The application expects PHP PDO MySQL support to be available.

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

Use the SQL files in `sql/` to create the required schema for a new local
database.

The application deliberately separates refreshable live player data from
historical evidence that must remain stable for backtesting and calibration.

Historical tables should not be manually rewritten merely to match current live
FPL state.

## Data Updates

Production data updates are coordinated through:

`cron/runDataUpdates.php`

The controlled update pipeline currently covers:

- Bootstrap player and team data
- fixtures
- Player Fixture History

Update executions are recorded so the application can distinguish Healthy,
Stale, Partial, Running, Failed and Unavailable data states.

The main dashboard exposes Application Health information derived from this
persisted update history.

For full setup, scheduling, manual execution and troubleshooting instructions,
see:

`DATA_UPDATES.md`

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

The final v0.39 release validation completed with:

- 355 test files
- 355 test files passed
- 0 test files failed
- 0 test files with errors
- 10,393 assertions passed
- 0 assertions failed
- 270.747 seconds total runtime

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

- `ROADMAP.md` — development history, architecture progress and path to v1.0
- `CHANGELOG.md` — release-by-release changes
- `DATA_UPDATES.md` — production update pipeline, scheduling and troubleshooting

## v1.0 Goal

v1.0 represents a stable end-to-end FPL decision-support application covering:

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
- model backtesting
- explainable recommendations
- reliable data updates

The complete regression suite must pass before the v1.0 release.