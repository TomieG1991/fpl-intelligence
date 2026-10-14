# FPL Intelligence — Project Status

**Current stable release:** v1.3.0 — Gameweek Command Centre
**Release state:** Committed and pushed to GitHub `main`; production deployment not started
**Next milestone:** Not yet agreed
**Last updated:** 14 October 2026

## Project environment

- Repository: `TomieG1991/fpl-intelligence` (`main`)
- Local project root: `C:\wamp64\www\fpl-intelligence`
- Browser base URL: `http://localhost:8008/fpl-intelligence/`
- Stack: PHP, MySQL, Windows/WAMP
- GitHub `main` is the authoritative committed baseline. Check for newer uncommitted local changes before replacing any local file.
- Production deployment has not begun.

## Released baseline

- **v1.3.0:** commit `afa7c196b07d2362f914348517cb2973fa4a368c` (14 October 2026); final browser regression: 405/405 test files and 11,343 assertions passed.
- **v1.2.0:** commit `4c93bde1b327c6d7256b04c0e796f187bdc202ce`
- **Post-v1.2 Ko-fi sidebar integration:** commit `5198450c2d0f1ada296018409b2826cb516f606f`
- Last known post-Ko-fi full-suite baseline: 374 test files passed; 10,763 assertions passed.

## v1.3.0 scope

The Dashboard was redesigned as a **Gameweek Command Centre**, answering: **“What should I do with my FPL team this gameweek?”** Specialist pages remain available for deeper analysis. Historical calibration was deferred because only one independent authoritative gameweek was available; missing evidence must not be fabricated.

### Delivered in v1.3.0

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
- `ROADMAP.md` and `CHANGELOG.md` were included in the v1.3.0 release commit. Some release-status wording in those files and `README.md` still needs reconciliation.
- `public/includes/sidebar.php` now displays **v1.3.0**, and its relevant browser test was reported GREEN. There is **no `version.txt`**; the displayed version is maintained in `sidebar.php`.
- `git diff --check` reports no whitespace errors. Git still prints a nonblocking LF/CRLF conversion warning for `public/assets/css/app.css`.
- Review of the small `GameweekDecisionEngine.php` diff found a grammatical correction only (singular/plural risk warning); no decision logic change.
- Five local development diagnostics were excluded from the v1.3.0 commit. `git grep` found no tracked references to their filenames. They are not part of the released baseline: `tests/CommandCentreRuntimeDiagnostic.php`, `tests/CommandCentreStageTimingDiagnostic.php`, `tests/SquadImportCliDiagnostic.php`, `tests/SquadMappingPreparedSummaryEquivalenceDiagnostic.php`, `tests/SquadMappingStageTimingDiagnostic.php`.
- The v1.3.0 release commit included 51 selected files; the user confirmed the push to GitHub `main`.

## Current work — resume here

1. v1.3.0 is released, committed and pushed to GitHub `main`.
2. The post-release documentation reconciliation has been completed across
   `PROJECT_STATUS.md`, `README.md`, `ROADMAP.md` and `CHANGELOG.md`.
3. Review and agree the next development milestone before beginning new
   production work.
4. For future versions, finalise version numbers and release documentation
   before the planned release commit. Prefer one commit per version upgrade
   unless additional commits are justified.
5. Never commit or push without explicit user approval.

## Development guardrails

- TDD: **RED → minimal production change → GREEN → regression**.
- **One production or documentation file at a time.**
- Browser-run PHP tests must render line breaks with `<br>`, not `\n`.
- Investigate unexpected failures; never weaken tests to force GREEN.
- Preserve weights, search widths, candidate eligibility, objectives, tie-breaks, confidence semantics and historical evidence boundaries unless those are explicitly the subject of a separately authorised change.
- Prefer one planned release commit per version upgrade; GitHub `main` remains the committed baseline. Never commit or push without explicit user approval.
- Only interrupt the user when they must replace a file, run a test, review a visual or approve an action.

## Maintaining this file

Update this document at **meaningful milestones**, after major architectural decisions, verified release checkpoints or important blockers—not after every small edit. Keep the repository and ChatGPT Project copies synchronised when updated. This status file is a handover aid; current source code, test output and `ROADMAP.md` take precedence if discrepancies arise.
