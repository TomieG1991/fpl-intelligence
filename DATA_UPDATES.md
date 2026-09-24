# FPL Intelligence — Data Updates

This document describes how the FPL Intelligence live-data update pipeline is
run and monitored.

The current Windows Task Scheduler configuration belongs to the local WAMP
development environment.

Future live-server scheduling must use the production server's available
scheduler and environment. See `DEPLOYMENT.md` for the production deployment
contract.

## Data Update Runner

The controlled live-data update pipeline is executed by:

```text
cron/runDataUpdates.php
```

The runner coordinates the required updates rather than requiring each updater
to be run manually.

The controlled pipeline currently includes:

1. Bootstrap data
2. Fixtures
3. Player Fixture History

Each update records its execution through the update-run persistence and health
infrastructure.

The runner uses the active PHP CLI executable rather than depending on a
hard-coded WAMP PHP installation.

Successful complete execution returns a successful process exit status.

Partial or failed execution returns a non-zero process exit status so a
scheduler can detect that the controlled update did not complete successfully.

---

## Current Local Development Schedule

The current development installation uses Windows Task Scheduler.

Task name:

```text
FPL Intelligence Data Update
```

Current local schedule:

```text
Daily at 06:00
```

The local WAMP task runs:

```text
Program/script:
C:\wamp64\bin\php\php8.2.3\php.exe

Arguments:
C:\wamp64\www\fpl-intelligence\cron\runDataUpdates.php

Start in:
C:\wamp64\www\fpl-intelligence
```

### Local Task Scheduler Configuration

General:

- Run only when the user is logged on
- Run with highest privileges

Conditions:

- Do not require the computer to be idle
- Do not require AC power
- Wake the computer to run the task
- Require an available network connection

Settings:

- Allow the task to be run on demand
- Run the task as soon as possible after a scheduled start is missed
- Do not automatically restart the task on failure
- Do not impose an execution time limit
- Do not start a new instance if the task is already running

The local task does not shut down, sleep or hibernate the computer when the
update finishes.

This Windows configuration is not the production deployment contract. When the
application is eventually deployed, the equivalent job should be configured
using the live server's scheduler, PHP CLI executable, project path,
permissions and production environment variables.

The exact production schedule is intentionally deferred until the live hosting
environment is known.

---

## Application Health

The FPL Intelligence dashboard displays the current health of the controlled production updates.

The Application Health section monitors:

- Bootstrap
- Fixtures
- Player Fixture History

Possible health states include:

- Healthy
- Stale
- Partial
- Running
- Failed
- Unavailable

The dashboard also exposes useful execution information including:

- Last successful update
- Records received
- Records updated
- Records skipped
- Records failed
- Duration
- Error information when applicable

The application currently uses a 24-hour freshness threshold for the dashboard health evaluation.

---

## Manual Update

If an update needs to be run outside the normal schedule, it can be started either through Task Scheduler or directly through PHP CLI.

### Task Scheduler

Open Windows Task Scheduler and locate:

```text
Task Scheduler Library
→ FPL Intelligence Data Update
```

Right-click the task and select:

```text
Run
```

Do not start another instance while an update is already running.

After completion, refresh Task Scheduler if necessary.

A successful execution should return:

```text
Status:
Ready

Last Run Result:
0x0
```

The Task Scheduler interface may temporarily continue displaying `Running` after the PHP process has completed. Refresh the Task Scheduler view before treating this as a stuck process.

### PHP CLI

The update can also be executed manually with:

```text
C:\wamp64\bin\php\php8.2.3\php.exe C:\wamp64\www\fpl-intelligence\cron\runDataUpdates.php
```

---

## Verifying an Update

Do not rely only on the Task Scheduler result.

After an update, check the Application Health section of the FPL Intelligence dashboard.

A successful complete update should show all three controlled datasets as:

```text
Healthy
```

with recent Last Success timestamps.

Task Scheduler should also report:

```text
Last Run Result:
0x0
```

Together these confirm that Windows successfully launched the process and that the FPL Intelligence update pipeline successfully recorded its work.

---

## Failure Investigation

If the scheduled task fails:

1. Do not immediately rerun the task repeatedly.
2. Check the Application Health section first.
3. Determine which controlled update failed or remained Running.
4. Check the recorded error information where available.
5. Run the production runner manually only when useful for diagnosis.
6. Investigate the underlying failure before changing production code.

A Task Scheduler failure and an FPL Intelligence update failure are not necessarily the same problem.

For example, the application health records may show that the update completed successfully even if the Task Scheduler interface has not yet refreshed its displayed status.

---

## Operational Notes

The Player Fixture History update is substantially slower than the Bootstrap and Fixtures updates.

A production verification run on 16 September 2026 recorded approximately:

```text
Bootstrap:
772 ms

Fixtures:
489 ms

Player Fixture History:
134,078 ms
```

Therefore a complete production update taking several minutes can be normal.

Repeated or overlapping update executions should be avoided.

---

## Historical Evidence Lifecycle

Historical evidence is maintained by a separate production lifecycle from the
controlled live-data update pipeline.

The lifecycle is executed by:

```text
cron/runHistoricalEvidenceLifecycle.php
```

On the current WAMP development installation, it can be run manually using:

```text
C:\wamp64\bin\php\php8.2.3\php.exe C:\wamp64\www\fpl-intelligence\cron\runHistoricalEvidenceLifecycle.php
```

The working directory should be:

```text
C:\wamp64\www\fpl-intelligence
```

### Lifecycle Order

The historical-evidence lifecycle runs these operations in order:

1. Promote eligible recommendation candidates.
2. Promote eligible player gameweek snapshot candidates.
3. Capture the latest player gameweek snapshot candidates for the next
   deadline.

Promotion deliberately occurs before capture.

This ensures that evidence belonging to a deadline that has passed is promoted
to its immutable historical form before mutable candidate evidence is captured
for the next actionable gameweek.

### Historical Integrity

The lifecycle preserves the distinction between mutable pre-deadline candidate
evidence and immutable historical evidence.

Repeated execution is safe because the lifecycle uses the existing idempotent
promotion and candidate-storage boundaries.

The lifecycle does not:

- reconstruct missing historical recommendations
- manufacture recommendation evidence
- recalculate historical recommendations using current data
- overwrite immutable evidence merely because live FPL state has changed

Recommendation candidate capture is deliberately not performed by the
unattended historical-evidence lifecycle.

Recommendation candidates contain manager-specific decision evidence and must
originate from genuine production recommendation generation. The current
production Chips flow captures this evidence after the manager's imported squad
and the four chip decisions have been produced.

Public FPL data cannot be used to reconstruct private manager-specific
pre-deadline recommendation evidence reliably.

Therefore, if genuine recommendation evidence was not captured before the
relevant deadline, the historical gameweek remains incomplete rather than
receiving manufactured evidence.

### Production Scheduling

The historical-evidence lifecycle is intended to run automatically on the
production server.

A recommended production cadence is once per hour:

```cron
0 * * * * /path/to/php /path/to/fpl-intelligence/cron/runHistoricalEvidenceLifecycle.php
```

Replace `/path/to/php` and `/path/to/fpl-intelligence` with the paths used by
the production hosting environment.

The hourly lifecycle is safe because its promotion and candidate-storage
boundaries are idempotent.

Before a deadline, repeated executions can refresh the mutable player snapshot
candidate for the upcoming gameweek.

After a deadline, eligible recommendation and player snapshot candidates can
be promoted to immutable historical evidence.

The lifecycle schedule does not itself guarantee that the underlying live FPL
data is fresh.

Player snapshot candidate capture reads the application's locally stored data.
The production live-data update schedule must therefore also be frequent enough
for the required pre-deadline evidence quality.

The historical-evidence lifecycle and live-data update pipeline should remain
separate even when both are automated on the production server.

### Relationship to Live Data Updates

`cron/runHistoricalEvidenceLifecycle.php` is intentionally separate from:

```text
cron/runDataUpdates.php
```

The historical lifecycle is not added to `DataUpdateCoordinator` or
`DataUpdateProcessRunner`.

Historical-evidence execution is also not recorded as one of the normal
Application Health `update_runs`.

The normal controlled live-data pipeline remains responsible for:

1. Bootstrap data
2. Fixtures
3. Player Fixture History

The historical lifecycle remains responsible for preserving eligible
pre-deadline evidence for later backtesting and calibration.

This separation prevents historical-evidence preservation from changing the
established live-data update contract.

### Failure Behaviour

The historical-evidence lifecycle fails closed.

The next lifecycle step is not executed when a required earlier step fails.

A successful run reports success for:

```text
recommendation_promotion
player_snapshot_promotion
player_snapshot_capture
```

A failure should be investigated before changing production code or attempting
to manufacture the missing historical evidence.

### Deployment Note

The Windows Task Scheduler instructions earlier in this document describe the
current WAMP development environment.

When FPL Intelligence is deployed to the live web server, scheduling should use
the production server's available scheduler, such as cron, with the appropriate
production PHP executable, project paths and permissions.

The live deployment should automate both the required live-data updates and the
historical-evidence lifecycle at suitable cadences while preserving their
separate architectural responsibilities.