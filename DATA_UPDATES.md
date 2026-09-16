# FPL Intelligence — Production Data Updates

This document describes how the FPL Intelligence production data-update pipeline is run and monitored.

## Production Update Runner

The controlled production update pipeline is executed by:

```text
cron/runDataUpdates.php
```

On the current WAMP installation, it can be run manually from Windows using:

```text
C:\wamp64\bin\php\php8.2.3\php.exe C:\wamp64\www\fpl-intelligence\cron\runDataUpdates.php
```

The working directory should be:

```text
C:\wamp64\www\fpl-intelligence
```

The runner coordinates the production updates rather than requiring each updater to be run manually.

The controlled pipeline currently includes:

1. Bootstrap data
2. Fixtures
3. Player Fixture History

Each update records its execution through the update-run persistence and health infrastructure.

---

## Automatic Schedule

Production data updates are scheduled through Windows Task Scheduler.

Task name:

```text
FPL Intelligence Data Update
```

Schedule:

```text
Daily at 06:00
```

The task runs:

```text
Program/script:
C:\wamp64\bin\php\php8.2.3\php.exe

Arguments:
C:\wamp64\www\fpl-intelligence\cron\runDataUpdates.php

Start in:
C:\wamp64\www\fpl-intelligence
```

### Task Scheduler Configuration

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

The task does not shut down, sleep or hibernate the computer when the update finishes.

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

Recommendation capture, historical snapshot capture and promotion are separate processes from the controlled production data-update pipeline and should not be added to this scheduled task without a deliberate design change.