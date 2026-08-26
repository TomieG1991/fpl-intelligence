# FPL Intelligence v1.0 Roadmap

## Project Goal

FPL Intelligence is a data-driven Fantasy Premier League decision-support
application.

The goal of v1.0 is not simply to rank FPL players.

The completed system should help answer the main decisions an FPL manager
faces:

- Who are the strongest players?
- Who should I start this gameweek?
- Who should I bench?
- Who should I captain and vice-captain?
- Should I make a transfer?
- Which transfer improves my squad?
- Should I hold a transfer?
- Which players are good over several upcoming gameweeks?
- When should I use a chip?
- What squad structure is strongest?
- Which teams and fixtures create the best opportunities?
- How reliable is the available evidence?
- Why is the application making each recommendation?

The system should remain explainable, testable and robust throughout:
- preseason
- early season
- normal gameweeks
- partially completed gameweeks
- completed gameweeks
- blank gameweeks
- double gameweeks


---

# Current Stable Baseline

## Version

Current stable release:

**v0.28.0 — Player Form & Trend Intelligence**

GitHub `main` is the authoritative code baseline after every completed commit.


## Current Data Foundation

The application currently stores and updates:

- Premier League teams
- FPL players
- player price
- ownership
- minutes
- goals
- assists
- clean sheets
- bonus
- BPS
- ICT
- expected goals
- expected assists
- expected goal involvements
- availability/status/news
- Premier League fixtures
- fixture difficulty
- fixture scores
- finished status
- provisionally finished status
- official FPL gameweeks
- gameweek deadlines
- previous/current/next gameweek state
- player gameweek snapshots
- historical player price
- historical ownership
- historical availability state
- per-fixture player history
- per-fixture minutes
- per-fixture starts
- per-fixture FPL points
- per-fixture goals
- per-fixture assists
- per-fixture clean sheets
- per-fixture BPS
- per-fixture expected goals
- per-fixture expected assists
- per-fixture expected goal involvements
- per-fixture expected goals conceded
- historical fixture team/opponent context
- historical transfer and selection data

## Historical Data Foundation

Historical storage is now available independently of the live `players` table.

The historical architecture includes:

- `gameweeks`
- `player_gameweek_snapshots`
- `player_fixture_history`

### Gameweek History

All 38 official FPL gameweeks are persisted with:

- official FPL gameweek ID
- name
- deadline
- finished state
- data-checked state
- previous/current/next state

Gameweek importing is idempotent.

### Player Gameweek Snapshots

The current FPL bootstrap state is preserved by gameweek.

Snapshots retain appropriate player state such as:

- player identity
- team
- position
- price
- ownership
- availability
- minutes
- goals
- assists
- clean sheets
- bonus
- BPS
- expected goals
- expected assists
- expected goal involvements

Snapshot importing is idempotent.

### Player Fixture History

Official per-fixture player history is persisted from the FPL player-summary API.

Fixture-history records preserve:

- player
- gameweek
- fixture
- historical team
- historical opponent
- home/away state
- total points
- minutes
- starts
- goals
- assists
- expected goals
- expected assists
- expected goal involvements
- clean sheets
- goals conceded
- expected goals conceded
- saves
- defensive contribution metrics
- bonus
- BPS
- influence
- creativity
- threat
- ICT Index
- price
- selected count
- transfer activity

Historical fixture uniqueness is based on:

`player_id + fixture_id`

This is intentional so the application can safely support:

- normal gameweeks
- blank gameweeks
- double gameweeks

Historical fixture importing is:

- idempotent
- resumable
- batchable
- API-throttled

Historical evidence now feeds the standalone Player Form Intelligence layer.

Player Form Intelligence currently remains diagnostic and explainable rather
than directly altering the core Player Intelligence Score or downstream
decision systems.

Historical Form evidence is exposed through Player Intelligence summaries and
player profiles, while its eventual influence on projections and decision
models will be introduced conservatively in later milestones.


## Current Intelligence Systems

### Player Intelligence

Includes:

- Player Performance
- per-90 performance
- Sample Confidence
- Effective Confidence
- Player Strength
- Player Value
- Player Availability
- Fixture Intelligence
- Position-Aware Fixture Intelligence
- Overall Player Intelligence
- Player Assessment
- Player Comparison


### Player Form Intelligence

Includes:

- official per-fixture historical performance evidence
- configurable recent fixture windows
- configurable recent appearance windows
- recency-weighted historical performance
- position-aware Form modelling
- Form Rating
- Performance Rating
- participation rate
- zero-minute fixture evidence
- Performance Trend
- Participation Trend
- Minutes Trend
- Improving / Stable / Declining classifications
- insufficient-data protection
- historical sample diagnostics
- request-level historical Form caching

Player Form Intelligence currently remains diagnostic.

It is exposed through Player Intelligence summaries and individual player
profiles but does not yet directly alter the core Player Intelligence Score.

Form Intelligence remains separate from:

- Sample Confidence
- Effective Confidence
- underlying Player Strength


### Fixture Intelligence

Includes:

- fixture difficulty modelling
- near-term fixture opportunity
- team-strength context
- opponent Attack Intelligence
- opponent Defence Intelligence
- position-aware opportunity

Goalkeepers and defenders evaluate opponent attacking strength.

Midfielders and forwards evaluate opponent defensive strength.


### Team Intelligence

Includes:

- Team Strength
- Team Performance
- Attack Rating
- Defence Rating
- upcoming fixture opportunity
- Team Intelligence Score
- league-wide rankings
- individual team profiles


### Transfer Intelligence

Includes:

- transfer evaluation
- transfer combinations
- Transfer Planner
- Transfer Optimizer
- squad-aware single transfers
- squad-aware double transfers
- affordability
- squad improvement scoring


### Squad Intelligence

Includes:

- FPL squad import
- squad analysis
- player recommendations
- transfer recommendations
- development preview support
- player navigation


### Wildcard Intelligence

Includes:

- complete 15-player Wildcard optimisation
- FPL budget constraints
- FPL position constraints
- maximum three players per club
- duplicate protection
- Starting XI optimisation
- formation analysis
- goalkeeper reliability
- bench reliability
- squad structure scoring
- Wildcard Score
- FPL-style bench presentation


### Captain Intelligence

Includes:

- Captain Score
- attacking threat
- fixture opportunity
- availability
- confidence/reliability
- Effective Confidence
- confidence-adjusted attacking inputs
- captain recommendation
- vice-captain recommendation
- ranked captain alternatives


### Gameweek Intelligence

Includes:

- Gameweek Score
- Starting XI optimisation
- all legal FPL formations
- bench ordering
- Effective Confidence risk
- Captain Intelligence
- Transfer Intelligence
- manager-level Gameweek Decision Intelligence
- Hold / Consider Transfer / Make Transfer / Urgent Action decisions
- Squad Risks
- Key Insights


## Confidence Architecture

These concepts MUST remain separate.

### Sample Confidence

Purpose:

Measure maturity of the player's statistical performance sample.

Used for:

- regression of raw performance ratings
- Player Strength inputs

Conceptual flow:

Sample Confidence
→ adjusted performance ratings
→ Player Strength


### Effective Confidence

Purpose:

Measure how reliable the player currently is for an FPL decision.

Uses:

- 40% Sample Confidence
- 60% share of team-available Premier League minutes played

Used for:

- Gameweek decision risk
- Starting XI reliability
- Captain reliability
- Wildcard goalkeeper reliability
- Wildcard bench reliability

Conceptual flow:

Sample maturity + current participation
→ Effective Confidence
→ decision reliability

Effective Confidence MUST NOT directly replace Sample Confidence when
regressing performance ratings.


## Mid-Gameweek Behaviour

A fixture is considered completed evidence when either:

- `finished = 1`
- `finished_provisional = 1`

An upcoming fixture requires:

- `finished = 0`
- `finished_provisional = 0`

This allows partially completed gameweeks to contribute real participation
evidence without waiting for the entire gameweek to finish.

Players from teams that have not yet completed a match may have:

- zero team available minutes
- null Effective Confidence

This is different from a player whose team has played but who recorded
zero minutes.


## Bench Convention

The application follows the FPL visual bench convention:

1. Backup goalkeeper
2. First outfield substitute
3. Second outfield substitute
4. Third outfield substitute

Outfield substitution priority remains preserved independently of the
goalkeeper position.


---

# Development Roadmap


## v0.27.0 — Historical Gameweek & Fixture Intelligence

### Status

**COMPLETE**

### Goal

Stop relying exclusively on the latest cumulative FPL player state and build a
permanent historical data foundation.

### Delivered

Added persistent gameweek storage using official FPL gameweek identity.

Added:

- `gameweeks`
- `GameweekRepository`
- complete 38-gameweek importing
- previous/current/next gameweek state
- idempotent gameweek persistence

Added player gameweek snapshots using:

- `player_gameweek_snapshots`
- `PlayerGameweekSnapshotRepository`
- current-gameweek snapshot importing
- player/team/position identity preservation
- historical price and ownership state
- idempotent snapshot persistence

Added official per-fixture player history using:

- `player_fixture_history`
- `PlayerFixtureHistoryRepository`
- live FPL `element-summary` history
- historical team resolution
- historical opponent resolution
- player + fixture uniqueness
- Double Gameweek-safe storage

Added dedicated historical fixture importing through:

- `updatePlayerFixtureHistory.php`
- batch mode
- offset/resume support
- full-pool mode
- request throttling
- idempotent upserts

### Historical Import Validation

Completed GW1 historical import with:

- 610 player fixture-history rows
- 610 unique players
- all 10 Premier League GW1 fixtures represented
- zero duplicate player/fixture rows

Player gameweek snapshot import currently preserves the complete current player
pool for the active FPL gameweek.

### Architecture Decision

Official per-fixture FPL history is preferred for match-level performance
analysis where available.

This avoids relying solely on differences between cumulative bootstrap
snapshots.

Gameweek snapshots still remain valuable for preserving:

- price
- ownership
- availability
- player state
- market state

### Important Behaviour

Historical storage is currently a data foundation only.

It does not yet directly alter:

- Player Strength
- Sample Confidence
- Effective Confidence
- Player Intelligence
- Transfer Intelligence
- Wildcard Intelligence
- Captain Intelligence
- Gameweek Intelligence

Historical evidence will begin feeding new intelligence models from v0.28.0.

### Testing

Added regression and integration coverage for:

- historical schema
- gameweek importing
- snapshot importing
- duplicate protection
- player-summary API structure
- fixture-history repository behaviour
- real single-player fixture-history import
- complete fixture-history integration
- gameweek identity
- player identity
- fixture identity
- historical team context
- historical opponent context
- blank/double-gameweek-safe uniqueness
- zero-minute history preservation
- import idempotency
- import performance

The complete project regression suite passes with v0.27.0 integrated.

---

## v0.28.0 — Player Form & Trend Intelligence

### Status

**COMPLETE**

### Dependency

Requires v0.27.0.

### Goal

Add recent-form intelligence using persisted official per-fixture history rather
than relying primarily on cumulative season totals.

Add recent-form intelligence rather than relying primarily on season totals.

### Delivered

Added `PlayerForm` for recent historical Player Form modelling using persisted
official per-fixture FPL history.

Added configurable historical retrieval covering:

- recent fixtures
- recent appearances
- zero-minute official fixture history
- participation evidence

Added recency-weighted Form modelling so newer fixtures carry greater influence
without allowing one match to dominate the historical sample.

Added position-aware Form modelling using appropriate evidence for:

- goalkeepers
- defenders
- midfielders
- forwards

Added historical performance metrics including:

- points per appearance
- average appearance minutes
- expected goals per 90
- expected assists per 90
- expected goal involvements per 90
- BPS per 90
- clean-sheet rate
- expected goals conceded per 90

Added:

- Form Rating
- Performance Rating
- participation rate
- fixture sample size
- appearance sample size
- zero-minute fixture count

Separated holistic recent Form from on-pitch Performance so playing-time
security does not incorrectly determine performance quality.

Added `PlayerFormTrend` with independent:

- Performance Trend
- Participation Trend
- Minutes Trend

Trend classifications support:

- Improving
- Stable
- Declining
- Insufficient Data

Added minimum historical sample protection so early-season evidence cannot
produce misleading trend classifications.

Added Player Form Intelligence to:

- bulk Player Intelligence summaries
- individual Player Intelligence profiles

Added a Historical Intelligence / Recent Form section to player profiles showing:

- Form Rating
- Performance Rating
- Participation
- Performance Trend
- Participation Trend
- Minutes Trend
- historical fixture sample
- historical appearance sample
- zero-minute evidence where applicable

Added request-level historical Form caching to prevent repeated database queries
during large Player Intelligence operations.

The caching optimisation reduced the complete regression-suite runtime from
approximately 255 seconds to approximately 134 seconds.

### Architecture Decision

Player Form Intelligence remains diagnostic at this stage.

Form does not yet directly alter:

- core Player Intelligence Score
- Transfer Intelligence
- Starting XI decisions
- Captain Intelligence
- Wildcard Intelligence
- Gameweek Intelligence

This allows the historical Form model to accumulate and be validated against
real gameweek evidence before it receives decision-making weight.

### Confidence Separation

The existing confidence architecture remains unchanged.

Sample Confidence
→ statistical sample maturity

Effective Confidence
→ current decision reliability

Form Intelligence
→ recent performance and participation direction

These concepts remain independently explainable and must not be collapsed into
one metric.

### Current Real-Data State

GW1 currently provides:

- 610 persisted player fixture-history records
- 610 unique players
- all 10 Premier League fixtures represented

Real player profiles now expose historical Form Intelligence.

With only GW1 historical evidence available, trend classifications correctly
return `Insufficient Data` until sufficient history accumulates.

### Testing

Added regression coverage for:

- Player Form model structure
- position-aware Form weighting
- recency weighting
- Performance Rating
- zero-minute history
- participation modelling
- historical sample sizes
- Form Rating bounds
- Performance Rating bounds
- Performance Trend
- Participation Trend
- Minutes Trend
- trend thresholds
- insufficient historical evidence
- early-season behaviour
- Player Intelligence Service integration
- individual Player Profile integration
- request-level historical caching
- Player Form profile UI
- PHP error protection
- performance regression

The complete project regression suite passes with:

- 96 test files
- 96 test files passed
- 0 test files failed
- 0 test files with errors
- 3,417 assertions passed
- 0 assertions failed

### Important Rule

Form must complement underlying Player Strength.

It must not replace the existing Sample Confidence architecture.

### Confidence Separation

Form Intelligence must not replace either confidence model.

The concepts remain:

Sample Confidence
→ statistical sample maturity

Effective Confidence
→ current decision reliability

Form Intelligence
→ recent performance direction

These must remain independently explainable.


---

## v0.29.0 — FPL Expected Points Intelligence

### Goal

Translate the existing intelligence models into an understandable FPL points
projection.

### Planned Work

Create an Expected Points / Projected Points model using:

- expected minutes
- attacking performance
- clean-sheet opportunity
- fixture intelligence
- position
- availability
- Effective Confidence
- recent form
- team attack/defence context

Generate:

- next-gameweek projected points
- projected minutes
- projection confidence
- projection breakdown

Keep projections explainable.

Example:

Projected Points: 6.4

Components:
- Expected Minutes
- Attacking Return Potential
- Clean Sheet Potential
- Fixture Opportunity
- Reliability Adjustment

### Testing

Use synthetic scenarios to ensure:

- stronger fixtures improve projection
- lower expected minutes reduce projection
- unavailable players are penalised
- position scoring behaves correctly
- projections remain bounded and sensible


---

## v0.30.0 — Multi-Gameweek Planning Intelligence

### Dependency

Strongly benefits from v0.28 and v0.29.

### Goal

Move beyond next-gameweek decisions.

### Planned Work

Support planning horizons such as:

- next 3 gameweeks
- next 5 gameweeks

Add:

- multi-GW fixture score
- projected points across horizon
- fixture swings
- transfer horizon value
- Hold vs Buy vs Sell analysis

Upgrade Transfer Intelligence so it can distinguish between:

- strong one-week punt
- strong medium-term transfer
- player worth holding despite one poor fixture
- player with deteriorating future schedule

Expose horizon selection in relevant pages.


---

## v0.31.0 — Market & Price Intelligence

### Goal

Use FPL market information as a supporting decision signal.

### Planned Work

Preserve historical:

- player price
- ownership percentage
- transfers in
- transfers out where available

Add:

- price movement history
- ownership movement
- transfer momentum
- value trend
- market trend classification

Potential outputs:

- Rising
- Stable
- Falling
- Heavily Bought
- Heavily Sold

### Important Rule

Popularity must not be treated as proof of player quality.

Market data should support decisions, not dominate Player Intelligence.


---

## v0.32.0 — Squad Horizon & Rotation Intelligence

### Goal

Evaluate the squad as a multi-week unit.

### Planned Work

Analyse:

- defensive rotation
- goalkeeper rotation
- bench coverage
- fixture clashes
- weak fixture clusters
- position depth
- expensive bench problems
- coverage during difficult fixtures

Add squad-level future strength over 3-5 gameweeks.

Identify:

- rotation strengths
- structural weaknesses
- players repeatedly likely to be benched
- weak bench coverage
- fixture bottlenecks


---

## v0.33.0 — Blank & Double Gameweek Intelligence

### Goal

Make fixture modelling explicitly aware of unusual FPL schedules.

### Planned Work

Detect:

- blank gameweeks
- double gameweeks
- multiple fixtures for one team
- teams with no fixture

Adapt:

- fixture opportunity
- projected points
- captain recommendations
- Starting XI decisions
- transfer recommendations

Avoid assuming every team has exactly one fixture per gameweek.

### Testing

Include synthetic:

- normal GW
- blank GW
- double GW
- mixed BGW/DGW


---

## v0.34.0 — Chip Intelligence

### Dependencies

Requires strong multi-gameweek and blank/double-gameweek support.

### Goal

Provide intelligent recommendations for FPL chips.

### Planned Systems

#### Wildcard Timing Intelligence

Extend the existing Wildcard squad generator so the app can answer:

- Is a Wildcard worth using now?
- How much improvement does the Wildcard create?
- Is waiting likely to be better?


#### Free Hit Intelligence

Generate the optimal one-gameweek Free Hit squad.


#### Bench Boost Intelligence

Measure:

- projected bench points
- bench reliability
- fixture quality
- full-squad availability


#### Triple Captain Intelligence

Use Captain Intelligence and projected points to identify exceptional captain
opportunities.


### Output

Each chip should support:

- Use
- Consider
- Hold

with explanation and confidence.


---

## v0.35.0 — Recommendation History & Backtesting

### Goal

Begin measuring whether FPL Intelligence recommendations actually work.

### Planned Work

Before each deadline preserve:

- player rankings
- Captain recommendation
- Starting XI recommendation
- transfer recommendations
- projected points
- Gameweek Decision
- important model components

After matches complete, compare recommendations with actual outcomes.

Add model evaluation metrics.

Potential questions:

- Did the recommended captain outperform alternatives?
- Did transfer targets outperform sold players?
- How accurate were expected minutes?
- How accurate were projected points?
- Did higher Intelligence Scores correlate with stronger returns?


### Importance

No model should be tuned purely because today's output "looks right".

Backtesting should provide evidence for future calibration.


---

## v0.36.0 — Model Calibration & Intelligence Quality

### Dependency

Requires historical/backtesting data.

### Goal

Tune model weights using evidence rather than intuition alone.

### Planned Work

Evaluate:

- Player Strength weighting
- Fixture weighting
- Position-Aware Fixture weighting
- Effective Confidence weighting
- Captain weighting
- Gameweek weighting
- transfer weighting
- projection accuracy

Changes must be supported by historical results and regression tests.


---

## v0.37.0 — Data Update Reliability & Application Health

### Goal

Make the data pipeline safe and observable.

### Planned Work

Add update health information:

- last successful FPL player update
- last successful fixture update
- number of players updated
- number of fixtures updated
- stale-data warning
- API failure handling
- update duration

Potentially introduce one controlled updater that coordinates:

- FPL player/team update
- fixture update
- history snapshot update

Never silently replace valid data with an empty API response.


---

## v0.38.0 — Performance & Caching

### Goal

Reduce repeated expensive Player Intelligence calculations.

### Current Motivation

Real-data services and tests now perform substantial repeated calculations.

### Planned Work

Profile:

- PlayerIntelligenceService
- Wildcard Optimizer
- Transfer Optimizer
- Gameweek pages
- Squad pages

Introduce caching only where correctness can be preserved.

Cache invalidation should occur after relevant FPL data updates.

Performance improvements must not change model output.


---

## v0.39.0 — v1.0 UX, Explainability & Release Hardening

### Goal

Prepare the application as a coherent complete product.

### Planned Work

Review every public page for:

- consistent terminology
- consistent scores
- confidence labels
- navigation
- mobile behaviour
- empty states
- API failure states
- explanatory text
- stale-data warnings
- profile links
- accessibility

Review code for:

- duplicate calculations
- temporary diagnostics
- stale comments
- unused methods
- inconsistent naming

Review tests for:

- brittle live-data assumptions
- obsolete regression expectations
- unnecessary diagnostics

Add project documentation covering:

- installation
- database setup
- update scripts
- architecture
- model definitions
- testing
- development workflow


---

# v1.0.0 — FPL Intelligence

v1.0 is reached when FPL Intelligence provides a stable end-to-end decision
system covering:

- player evaluation
- team evaluation
- fixtures
- recent form
- expected points
- squad analysis
- Starting XI
- captaincy
- transfers
- multi-gameweek planning
- Wildcard
- Free Hit
- Bench Boost
- Triple Captain
- blank gameweeks
- double gameweeks
- market/price intelligence
- recommendation history
- model backtesting
- explainable recommendations
- reliable data updates

The full regression suite must pass before v1.0 release.


---

# Development Rules

## 1. GitHub Baseline

After a commit is pushed:

`main` becomes the authoritative baseline.

Between commits, local changes must be assumed to be newer than GitHub.


## 2. Protect Local Work

If files have uncommitted changes, do not replace them using the GitHub
version without first comparing the changes.


## 3. Small Controlled Changes

Prefer:

- exact file
- exact section
- exact replacement
- targeted tests

Avoid unnecessary whole-file replacement.


## 4. Tests Protect Behaviour

A failing test must not automatically be changed just to make it green.

First determine whether:

- production behaviour is wrong
- the test expectation is stale


## 5. Diagnostics Are Temporary Unless Deliberately Retained

Temporary investigation scripts and diagnostic output should be removed before
a release unless they provide lasting development value.


## 6. Confidence Semantics Must Remain Clear

Sample Confidence and Effective Confidence have different responsibilities.

Do not merge their meanings.


## 7. Live Data Tests Must Avoid Brittle Assumptions

Tests should avoid requiring:

- a particular player
- a particular opponent
- a particular partial-gameweek state
- an exact live score

unless the test deliberately uses controlled synthetic data.


## 8. Every Release

Before each release:

1. run targeted tests during development
2. run `RunAllTests.php`
3. review `git diff`
4. review untracked files
5. remove temporary diagnostics
6. update `CHANGELOG.md`
7. update displayed application version
8. rerun final regression suite when appropriate
9. commit
10. push to `main`


---

# Current Next Action

**NEXT: v0.27.0 — Historical Gameweek Data Foundation**

The first task is to design the historical database schema before modifying
the existing update scripts.