# FPL Intelligence — Project Status

**Current milestone:** v1.3.0 — Gameweek Command Centre  
**Release state:** Local v1.3.0 release candidate; not yet committed, pushed or deployed  
**Last updated:** 14 October 2026

## Project environment

- Repository: `TomieG1991/fpl-intelligence` (`main`)
- Local project root: `C:\wamp64\www\fpl-intelligence`
- Browser base URL: `http://localhost:8008/fpl-intelligence/`
- Stack: PHP, MySQL, Windows/WAMP
- GitHub `main` is the authoritative **committed** baseline. Local changes for v1.3.0 may not yet be committed.
- Production deployment has not begun.

## Released baseline

- **v1.2.0:** commit `4c93bde1b327c6d7256b04c0e796f187bdc202ce`
- **Post-v1.2 Ko-fi sidebar integration:** commit `5198450c2d0f1ada296018409b2826cb516f606f`
- Last known post-Ko-fi full-suite baseline: 374 test files passed; 10,763 assertions passed.

## v1.3.0 scope

The Dashboard is being redesigned as a **Gameweek Command Centre**, answering: **“What should I do with my FPL team this gameweek?”** Specialist pages remain available for deeper analysis. Historical calibration was deferred because only one independent authoritative gameweek was available; missing evidence must not be fabricated.

### Implemented locally

- `CommandCentreService` provides a stable envelope for manager, gameweek, decision, starting XI, captaincy, transfers, squad horizon, chips, attention and data health.
- Manager selection is stored in a PHP session. The Dashboard form uses POST; normal navigation uses clean URLs without `entry_id` query strings. No hard-coded test manager is intended for production.
- Dashboard reuses authoritative gameweek decision intelligence rather than independently calculating model decisions.
- Heavy chip recommendation orchestration was removed from initial Dashboard loading; specialist chip analysis remains available elsewhere.
- Squad import mapping was optimised to use prepared player summaries once, eliminating repeated per-player profile construction. The user reported Dashboard load time of **under two seconds** after this change.
- Dashboard markup was reorganised into compact Manager/Gameweek context, Primary Decision + Captaincy, Starting XI + Bench, Transfers/Squad Outlook/Chips, Attention/Opportunities, Data Health, then secondary global data.
- Responsive presentation CSS was updated to match the reorganised markup.

### Latest verification — 14 October 2026

- Dashboard visual review approved by the user after the Attention/Opportunities, Data Health, and Additional Intelligence presentation refinements.
- Full browser regression completed: **405/405 test files passed; 11,343 assertions passed; 0 failures and 0 errors** (approximately 301 seconds).
- Seven focused tests were rerun GREEN after test-only corrections, including multi-gameweek expected points and Player Form history isolation. No model thresholds, scoring logic or decision semantics were altered to make these tests pass.
- `ROADMAP.md` and `CHANGELOG.md` were updated locally for v1.3.0 release preparation.
- `public/includes/sidebar.php` now displays **v1.3.0**, and its relevant browser test was reported GREEN. There is **no `version.txt`**; the displayed version is maintained in `sidebar.php`.
- `git diff --check` reports no whitespace errors. Git still prints a nonblocking LF/CRLF conversion warning for `public/assets/css/app.css`.
- Review of the small `GameweekDecisionEngine.php` diff found a grammatical correction only (singular/plural risk warning); no decision logic change.
- Five untracked diagnostics were identified. `git grep` returned no tracked references to their filenames. Keep them locally and **do not stage them for the release** pending any further dependency review: `tests/CommandCentreRuntimeDiagnostic.php`, `tests/CommandCentreStageTimingDiagnostic.php`, `tests/SquadImportCliDiagnostic.php`, `tests/SquadMappingPreparedSummaryEquivalenceDiagnostic.php`, `tests/SquadMappingStageTimingDiagnostic.php`.
- The local working tree also contains untracked application services, manager context, and regression tests that **must** be staged for the release after final review.

## Current work — resume here

1. Replace the local `PROJECT_STATUS.md` with this updated handover file. It is currently untracked.
2. Run the **final complete browser regression** after the documentation/sidebar version changes. The earlier 405/405 run predates those changes.
3. Review the intended Git staging set carefully, excluding the five optional diagnostics and any other unintended files; check staged diff/stat and status before commit.
4. Obtain the user's **explicit approval** before committing or pushing. GitHub `main` remains the authoritative committed baseline until then.

## Development guardrails

- TDD: **RED → minimal production change → GREEN → regression**.
- **One production or documentation file at a time.**
- Browser-run PHP tests must render line breaks with `<br>`, not `\n`.
- Investigate unexpected failures; never weaken tests to force GREEN.
- Preserve weights, search widths, candidate eligibility, objectives, tie-breaks, confidence semantics and historical evidence boundaries unless those are explicitly the subject of a separately authorised change.
- Avoid unnecessary commits; GitHub `main` remains the committed baseline.
- Only interrupt the user when they must replace a file, run a test, review a visual or approve an action.

## Maintaining this file

Update this document at **meaningful milestones**, after major architectural decisions, verified release checkpoints or important blockers—not after every small edit. Keep the repository and ChatGPT Project copies synchronised when updated. This status file is a handover aid; current source code, test output and `ROADMAP.md` take precedence if discrepancies arise.
