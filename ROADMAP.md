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

Current stable release:

**v0.33.0 — Blank & Double Gameweek Intelligence**

GitHub `main` is the authoritative code baseline after every completed commit.

---

Current development milestone:

**v0.34.0 — Chip Intelligence — NEXT**

v0.33.0 is complete and establishes explicit Blank, Normal and Double Gameweek
semantics across fixture modelling, multi-gameweek Expected Points, Squad
Horizon planning, Starting XI selection, captaincy, fixture clashes and transfer
evaluation.

The completed v0.33.0 milestone also strengthens historical snapshot integrity by
using completed-gameweek fixture history as the authoritative source for
historical market prices and by automatically capturing completed-gameweek
snapshots after a successful full fixture-history import.

The next development milestone is v0.34.0, which will build Chip Intelligence on
top of the completed multi-gameweek, Blank/Double Gameweek and Squad Horizon
architecture.
---


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
- immutable player gameweek snapshots
- historical player price
- historical raw selected-manager count
- historical ownership percentage where trustworthy
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

Completed-gameweek player state is preserved as immutable historical evidence.

Snapshots retain appropriate player state such as:

- player identity
- team
- position
- historical price
- raw selected-manager count where historical evidence exists
- ownership percentage where trustworthy
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

Historical snapshots are intentionally separate from the refreshable live
`players` table.

Once a completed-gameweek snapshot has been captured, normal snapshot capture
uses an immutable `insertIfAbsent()` contract and does not overwrite the
historical row when live FPL state later changes.

Raw historical selected-manager count is sourced from official completed
player fixture history rather than inferred from rounded ownership percentage.

Players without genuine historical evidence are not assigned invented market
values.

GW1 historical recovery validated:

- 614 snapshot rows existed
- 610 players had genuine GW1 fixture-history evidence
- 610 historical selected-manager counts were restored
- 610 historical prices were validated
- 10 incorrect snapshot prices were corrected
- four players without GW1 historical evidence remain unsupported rather than
  receiving manufactured historical values

Historical GW1 ownership percentage remains deliberately excluded from market
trend calculations because an exact historical total-manager denominator is not
available.

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

### Expected Points Intelligence

Includes:

- next-gameweek Expected Points
- projected minutes
- Projection Confidence
- expected goals
- expected assists
- clean-sheet probability
- goalkeeper saves
- defensive contributions
- expected bonus
- goals-conceded deductions
- position-aware FPL scoring
- fixture and opponent context
- early-season sample regression
- component-level explainability
- individual Player Profile presentation

Expected Points now also supports multi-gameweek planning through:

- fixture-specific future Expected Points projections
- real upcoming Premier League fixture context
- opponent team identity
- home/away venue context
- position-aware Fixture Opportunity
- opponent Attack Rating
- opponent Defence Rating
- Next 3 projected points
- Next 5 projected points
- Next 6 projected points
- per-fixture Projected Minutes
- per-fixture Projection Confidence
- per-fixture Expected Points component explainability

The immediate next-fixture projection remains the primary single-gameweek
Expected Points contract.

Multi-gameweek projections reuse that model with fixture-specific context rather
than maintaining a separate scoring model.

### Market Intelligence

Market Intelligence is complete as part of v0.31.0.

The current system includes:

- historical price movement
- historical raw ownership-count movement
- transfer momentum
- Rising / Stable / Falling component classifications
- combined Market Signal
- Strong Rising
- Rising
- Stable
- Falling
- Strong Falling
- Mixed
- Insufficient Evidence
- Value Trend Intelligence
- Improving Value
- Stable Value
- Deteriorating Value
- Mixed Value Signal
- insufficient-history protection
- duplicate-gameweek protection
- null/invalid evidence protection
- early-season protection
- compact public Market Intelligence summaries
- Player Intelligence integration
- Player Profile presentation

Price movement uses immutable historical gameweek snapshots.

Ownership movement uses exact historical raw selected-manager counts rather
than relying on rounded ownership percentages.

Transfer momentum uses persisted official `player_fixture_history` evidence.

The combined Market Signal requires at least two trustworthy component signals
before producing a directional classification.

Value Trend combines the existing Player Value model with market direction
without allowing market popularity to redefine underlying player quality.

With only GW1 historical evidence currently available, real players correctly
return:

- Price Movement: `Insufficient Historical Data`
- Ownership Movement: `Insufficient Historical Data`
- Transfer Momentum: `Insufficient Historical Data`
- Combined Market Signal: `Insufficient Evidence`
- Value Trend: `Insufficient Evidence`

Market Intelligence remains a supporting intelligence layer.

It does not directly alter:

- Player Strength
- Player Intelligence Score
- Expected Points
- Transfer recommendations
- Captain Intelligence
- Wildcard Intelligence
- Gameweek Intelligence

Popularity, transfer activity and price movement must not be treated as proof
of player quality.

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

### Status

**COMPLETE**

### Dependency

Requires v0.27.0 and strongly benefits from v0.28.0 historical Form Intelligence.

### Goal

Translate the existing intelligence and historical evidence models into an
explainable next-gameweek FPL points projection.

### Delivered So Far

Added next-gameweek Projected Points modelling using:

- projected minutes
- attacking performance
- expected goals
- expected assists
- clean-sheet probability
- fixture opportunity
- opponent attack/defence context
- position-aware FPL scoring
- player availability
- recent historical evidence

Added:

- Projected Points
- Projected Minutes
- Projection Confidence
- projection confidence labels
- explainable scoring-component breakdown
- projection evidence and sample diagnostics

Added position-aware FPL Expected Points components for:

- appearance points
- goals
- assists
- clean sheets
- goalkeeper saves
- defensive contributions
- bonus points

### Projected Minutes

Added projected-minutes modelling using current availability and historical
participation evidence.

Projected Minutes are bounded between 0 and 90 and provide the playing-time
foundation for the Expected Points model.

### Attacking Returns

Added expected attacking-return modelling using historical:

- expected goals per 90
- expected assists per 90

Historical attacking evidence is combined with projected playing time and
fixture opportunity before being translated into position-specific FPL points.

### Clean-Sheet Projection

Added clean-sheet probability modelling using:

- recent clean-sheet evidence
- opponent attacking strength
- fixture context
- projected minutes
- early-season sample confidence

Clean-sheet Expected Points use official position-specific FPL scoring.

### Goalkeeper Saves

Added goalkeeper save projections using:

- historical saves per 90
- recency-weighted save evidence
- projected minutes
- fixture save opportunity

Save Expected Points use official FPL goalkeeper save scoring.

Outfield players explicitly expose save modelling as `Not Applicable`.

### Defensive Contributions

Added 2026/27 FPL defensive-contribution Expected Points modelling.

The model uses the appropriate defensive-action evidence by position and
supports the official position-specific defensive-contribution thresholds.

Added position baselines derived from completed real fixture-history evidence.

Early-season player rates are regressed toward their position baseline using
appearance sample confidence so one match cannot dominate the projection.

Defensive-contribution modelling exposes:

- raw defensive actions per 90
- position baseline
- appearance sample size
- sample confidence
- regressed action rate
- fixture opportunity multiplier
- projected defensive actions
- threshold probability
- expected defensive-contribution points

### Bonus Points

Added Expected Bonus modelling using historical BPS evidence.

The model uses:

- recency-weighted BPS per 90
- position-specific BPS baselines
- projected minutes
- appearance sample confidence
- early-season regression
- projected BPS

Added a smooth probabilistic BPS-to-bonus curve calibrated from complete GW1
2026/27 player-fixture evidence.

This avoids treating projected BPS as a deterministic realised match BPS score
and prevents small exact-BPS samples from producing unstable projection jumps.

Expected Bonus remains bounded between zero and three FPL points.

### Early-Season Protection

Expected Points components using limited historical evidence apply explicit
sample regression.

With one appearance of evidence, individual rates remain strongly regressed
toward appropriate position-level baselines.

As historical evidence grows, player-specific performance is allowed to carry
progressively greater weight.

This protects the projection engine from overreacting to isolated early-season
performances.

### Explainability

Expected Points exposes component-level outputs covering:

- appearance
- goals
- assists
- clean sheets
- saves
- bonus
- defensive contributions

Supporting evidence is retained so the application can explain why each
component contributes to the final projection.

### Current Real-Data Validation

Complete GW1 historical evidence is available for projection modelling.

Real-data diagnostics confirm:

- goalkeeper save projections are active
- defensive-contribution projections are active
- bonus projections are active
- early-season sample regression is active
- specialist components reach the final Projected Points total

For example, high-BPS players receive positive Expected Bonus while one-match
evidence remains conservatively regressed toward position baselines.

### Testing

Added synthetic, regression and real-data coverage for:

- projected minutes
- projection confidence
- Expected Points inputs
- position-aware FPL scoring
- attacking Expected Points
- clean-sheet probability
- goalkeeper saves
- defensive contributions
- defensive-contribution baselines
- defensive-contribution sample regression
- bonus modelling
- BPS position baselines
- BPS sample regression
- probabilistic bonus behaviour
- component totals
- Player Intelligence integration
- complete real-player projection coverage

The current complete regression suite passes with:

- 108 test files
- 108 test files passed
- 0 test files failed
- 0 test files with errors
- 3,845 assertions passed
- 0 assertions failed

### Deferred Scoring Events

The following rare-event FPL scoring components are intentionally deferred
from v0.29.0 because the available early-season evidence is insufficient for
stable player-level modelling:

- goalkeeper penalty saves
- yellow cards
- red cards
- own goals
- penalties missed

These events are persisted in historical fixture data and can be introduced
later through a dedicated calibration/backtesting milestone once a meaningful
season sample is available.

### Completion Notes

v0.29.0 now delivers an explainable next-gameweek FPL Expected Points model
covering the primary recurring scoring routes and deductions.

The final model includes:

- projected minutes
- projection confidence
- expected goals
- expected assists
- clean-sheet probability
- appearance points
- goal points
- assist points
- clean-sheet points
- goalkeeper save points
- goals-conceded deductions
- defensive-contribution points
- expected bonus points
- fixture and opponent context
- early-season sample regression
- component-level explainability
- player-profile presentation

Sample Confidence, Effective Confidence, Projection Confidence and Form
Intelligence remain intentionally separate concepts.

Effective Confidence is retained as a downstream decision-reliability measure
rather than being multiplied directly into Projected Points.

This avoids double-penalising playing-time uncertainty while preserving
confidence information for later transfer, captain, wildcard and gameweek
decision models.

### Final Validation

The complete regression suite passes with:

- 110 test files
- 110 test files passed
- 0 test files failed
- 0 test files with errors
- 3,924 assertions passed
- 0 assertions failed

The Expected Points model is now integrated into Player Intelligence and
displayed on the player profile with:

- Projected Points
- Projected Minutes
- Projection Confidence
- expected outcome inputs
- explainable FPL scoring breakdown

v0.29.0 is complete.

### Important Rules

Expected Points must remain explainable.

Sample Confidence, Effective Confidence, Projection Confidence and Form
Intelligence must retain clearly defined and separate responsibilities.

Early-season evidence must not be allowed to create unrealistically confident
projections.

Projection components must use official 2026/27 FPL scoring rules where
applicable.

---

## v0.30.0 — Multi-Gameweek Expected Points Intelligence

### Status

**COMPLETE**

### Dependency

Builds on v0.29.0 Expected Points Intelligence.

### Goal

Extend the single-fixture Expected Points model into an explainable
multi-gameweek projection foundation.

Allow individual players to be evaluated across several upcoming fixtures
without introducing a separate or competing projection model.

### Delivered

Added multi-gameweek Expected Points projections using the existing
single-fixture Expected Points engine as the scoring source of truth.

Added fixture-specific future projection context including:

- gameweek
- kickoff time
- opponent team
- opponent name
- home/away venue
- base Fixture Opportunity
- position-aware Fixture Opportunity
- opponent Attack Rating
- opponent Defence Rating

Added per-fixture projection outputs including:

- Projected Points
- Projected Minutes
- Projection Confidence
- Projection Confidence label
- Expected Points components
- projection inputs
- supporting evidence

Added planning horizons for:

- Next 3 gameweeks
- Next 5 gameweeks
- Next 6 gameweeks

Planning-horizon totals are calculated directly from the underlying
fixture-level Expected Points projections.

### Defensive Fixture Sensitivity

Improved defensive Expected Points so materially different fixture contexts
remain distinguishable even when opponent Attack Ratings are equal.

Clean-sheet probability now retains broader fixture context alongside
opponent attacking strength.

Expected goals-conceded modelling now retains broader fixture context alongside
opponent attacking strength.

Goalkeeper save projections remain driven by opponent attacking strength rather
than general fixture opportunity.

This preserves the intended distinction between:

- likelihood of preventing goals
- likelihood of facing save opportunities

### Player Intelligence Integration

Added multi-gameweek Expected Points to individual Player Intelligence profiles.

The multi-gameweek model is deliberately calculated for individual players
rather than every player returned by `getAllPlayerSummaries()`.

This avoids multiplying expensive Expected Points calculations across the
complete player pool when multi-gameweek detail is not required.

### Player Profile

Added a dedicated Multi-Gameweek Planning section to the Player Profile.

The interface exposes:

- Next 3 projected points
- Next 5 projected points
- Next 6 projected points
- six upcoming fixture projections
- gameweek
- opponent
- home/away venue
- Fixture Opportunity
- Projected Minutes
- Projection Confidence
- Expected Points

The planning interface follows the existing Player Intelligence design system
and remains responsive within the Player Profile.

### Architecture Decision

Multi-gameweek Expected Points does not maintain an independent scoring model.

Each fixture projection reuses the existing Expected Points model with
fixture-specific context.

This ensures:

- single-gameweek and multi-gameweek projections remain consistent
- scoring changes only need to be implemented once
- component explainability remains identical
- regression coverage can protect one projection architecture

The first multi-gameweek fixture projection must remain aligned with the
immediate next-fixture Expected Points projection.

### Scope Decision

v0.30.0 establishes the player-level multi-gameweek projection foundation.

The following broader planning features remain future work:

- transfer horizon value
- Hold vs Buy vs Sell analysis
- transfer timing across several gameweeks
- squad-level planning horizons
- defensive rotation analysis
- goalkeeper rotation analysis
- fixture-cluster analysis

These should build on the completed multi-gameweek Expected Points foundation
rather than being forced into the initial projection milestone.

### Validation

Controlled fixture-sensitivity testing confirms that favourable and difficult
fixtures produce appropriately different defensive Expected Points behaviour.

Real-data validation confirms:

- future fixtures resolve correctly
- opponent names resolve correctly
- home/away context resolves correctly
- Fixture Opportunity reaches the projection contract
- Projected Minutes reaches each fixture projection
- Projection Confidence reaches each fixture projection
- individual fixture Expected Points remain numeric
- planning horizons equal their underlying fixture sums
- the immediate next-fixture projection remains aligned with the first
  multi-gameweek fixture projection
- the Player Profile renders all six future fixture projections
- temporary diagnostic output does not leak into the Player Profile

### Testing

The complete regression suite passes with:

- 117 test files
- 117 test files passed
- 0 test files failed
- 0 test files with errors
- 4,071 assertions passed
- 0 assertions failed

v0.30.0 is complete.


---

## v0.31.0 — Market Intelligence

### Status

**COMPLETE**

### Dependency

Builds on the v0.27.0 historical data foundation.

### Goal

Use FPL market information as an explainable supporting decision signal without
treating popularity as evidence of underlying player quality.

### Delivered

Added `MarketIntelligenceService`.

Added historical Price Movement Intelligence using immutable player gameweek
snapshots.

Price movement supports:

- Rising
- Stable
- Falling
- Insufficient Historical Data

Added historical Ownership Movement Intelligence using exact raw
selected-manager counts.

Ownership movement supports:

- Rising
- Stable
- Falling
- Insufficient Historical Data

Historical ownership movement deliberately does not depend on legacy GW1
`selected_by_percent` because that field cannot currently be reconstructed with
an exact historical denominator.

Added Transfer Momentum Intelligence using official persisted
`player_fixture_history` data.

Transfer Momentum preserves:

- transfers in
- transfers out
- transfer balance
- zero-transfer evidence
- distinct gameweek identity

Added Combined Market Signal modelling.

Combined classifications currently support:

- Strong Rising
- Rising
- Stable
- Falling
- Strong Falling
- Mixed
- Insufficient Evidence

At least two trustworthy component signals are required before the service
produces a directional combined classification.

### Value Trend Intelligence

Added Value Trend Intelligence combining the existing Player Value model with
the completed Market Intelligence classification.

Player Value remains the source of truth for underlying value quality.

Value groups are interpreted as:

- Strong Value:
  - Exceptional
  - Excellent
  - Good
- Neutral:
  - Average
- Weak:
  - Poor
  - Very Poor

Value Trend classifications support:

- Improving Value
- Stable Value
- Deteriorating Value
- Mixed Value Signal
- Insufficient Evidence

Strong Value combined with Rising or Strong Rising market behaviour produces
`Improving Value`.

Strong Value combined with Stable market behaviour produces `Stable Value`.

Weak Value combined with Falling or Strong Falling market behaviour produces
`Deteriorating Value`.

Conflicting value and market evidence produces `Mixed Value Signal`.

Insufficient market evidence produces `Insufficient Evidence`.

Market activity does not independently increase the underlying Player Value
rating.

### Historical Snapshot Architecture

The live `players` table remains refreshable current FPL state.

`player_gameweek_snapshots` now represents immutable historical gameweek state.

`player_fixture_history` remains the official completed-fixture history source
for:

- player performance
- historical selected-manager count
- transfer activity

Normal snapshot capture uses `insertIfAbsent()` and does not overwrite an
existing historical player/gameweek record.

### GW1 Market Recovery

The original GW1 snapshot had been refreshed with later live player state.

A controlled recovery process established:

- 614 GW1 snapshot rows
- 610 players with genuine official GW1 fixture-history evidence
- 4 players without genuine GW1 fixture history
- 10 historical price differences
- 610 missing historical selected-manager counts

Recovered:

- 610 trustworthy historical GW1 prices
- 610 exact historical selected-manager counts

Corrected the 10 historical price differences.

The four unsupported players were deliberately left without invented historical
selected counts.

Historical GW1 `selected_by_percent` remains excluded from ownership-trend
calculations because the exact historical total-player denominator is not
stored.

### Early-Season Behaviour

Market Intelligence must not manufacture trends from one gameweek.

With only GW1 historical evidence available, current real-data behaviour is:

- Price Movement: `Insufficient Historical Data`
- Ownership Movement: `Insufficient Historical Data`
- Transfer Momentum: `Insufficient Historical Data`
- Combined Market Signal: `Insufficient Evidence`

The same models should begin producing genuine directional intelligence
automatically as later immutable gameweek snapshots accumulate.

### Architecture Decision

Market behaviour is supporting intelligence.

Market Intelligence must remain separate from:

- Player Strength
- Expected Points
- Player Intelligence quality
- Sample Confidence
- Effective Confidence
- Projection Confidence

Popularity, transfers and price movement must not be treated as proof that a
player is intrinsically strong.

Market data may later inform decision timing and financial risk, but should not
override football-performance evidence.

### Public Integration

Added a stable public Market Intelligence summary contract.

The public summary exposes:

- combined Market Intelligence classification
- evidence count
- compact price direction
- compact ownership direction
- compact transfer direction
- compact Value Trend classification

Internal historical arrays and detailed implementation evidence remain outside
the compact public contract.

Market Intelligence is exposed through individual Player Intelligence profiles.

The Player Profile now includes a dedicated Market Intelligence section showing:

- Market Signal
- Evidence
- Value Trend
- Price Movement
- Ownership Movement
- Transfer Momentum

Early-season insufficient-history states are presented explicitly rather than
being disguised as stable market behaviour.

### Testing

Added dedicated regression and integration coverage for:

- Market Intelligence service structure
- real-player market-state retrieval
- historical price evidence
- controlled rising price movement
- controlled falling price movement
- stable price movement
- chronological price history
- duplicate-gameweek price protection
- invalid price evidence
- raw ownership-count movement
- conflicting percentage protection
- zero-manager ownership evidence
- transfer momentum
- transfer-balance integrity
- zero-transfer evidence
- distinct transfer gameweeks
- duplicate-gameweek transfer protection
- combined Market Signal classification
- mixed evidence
- partial evidence
- insufficient evidence
- public combined-signal integration
- repeatability
- invalid-player protection
- early-season real-data behaviour
- immutable snapshot lifecycle
- GW1 historical market recovery

Additional final milestone coverage includes:

- public Market Intelligence summary contract
- Player Intelligence Market Intelligence integration
- Player Profile Market Intelligence presentation
- Value Trend classification
- Value Trend integration
- compact Value Trend summary contract
- Player Intelligence Value Trend integration
- milestone-level acceptance coverage
- request-scoped Player Intelligence caching regression coverage

The final v0.31.0 milestone acceptance test passes:

- 75 milestone checks passed
- 0 milestone checks failed

The final complete regression suite passes with:

- 140 test files
- 140 test files passed
- 0 test files failed
- 0 test files with errors
- 4,581 assertions passed
- 0 assertions failed
- approximately 164 seconds total runtime

### Completion Notes

v0.31.0 completes the Market Intelligence milestone.

The completed system now provides:

- immutable historical market evidence
- historical price movement
- exact raw selected-manager ownership movement
- transfer momentum
- combined Market Signal classification
- Value Trend Intelligence
- controlled insufficient-history behaviour
- compact public Market Intelligence summaries
- Player Intelligence integration
- Player Profile presentation

Market Intelligence remains deliberately separate from underlying football
quality and projection models.

Future milestones may use Market Intelligence as supporting evidence for
transfer timing, squad planning and gameweek decisions, but such integration
must be justified conservatively rather than introduced simply because market
data is available.

### Important Rule

Popularity must not be treated as proof of player quality.

Market data should support decisions, not dominate Player Intelligence.

---

## v0.32.0 — Squad Horizon & Rotation Intelligence

### Status

**COMPLETE**

### Dependency

Builds on v0.30.0 Multi-Gameweek Expected Points Intelligence.

### Goal

Evaluate the complete FPL squad as a multi-week unit using the existing
player-level projection architecture.

Identify rotation opportunities, bench coverage, fixture clashes, position-depth
problems and structural weaknesses without introducing a separate competing
Expected Points model.

### Delivered

Added `SquadHorizonIntelligence` for analysing a complete 15-player squad across
consecutive upcoming FPL gameweeks.

Added `SquadHorizonIntelligenceService` as the production orchestration layer
between:

- imported FPL squads
- local player identity
- existing multi-gameweek Expected Points
- Squad Horizon Intelligence

Added a three-gameweek production horizon covering:

- complete squad projections
- legal Starting XI selection
- optimal formation
- Starting XI projected points
- bench composition
- bench projected points

### Bench Coverage

Added Bench Coverage Intelligence for every horizon gameweek.

The model exposes:

- bench player count
- total projected bench points
- strongest outfield substitute
- weakest outfield starter
- replacement coverage gap

This allows the squad to distinguish a numerically complete bench from one that
actually provides useful projected cover.

### Goalkeeper Rotation

Added Goalkeeper Rotation Intelligence.

The model evaluates:

- preferred goalkeeper by gameweek
- preference changes across the horizon
- rotating goalkeeper projected points
- best single goalkeeper projected points
- projected rotation gain

Tie handling and missing-projection behaviour are deterministic and protected by
dedicated regression coverage.

### Defensive Rotation

Added Defensive Rotation Intelligence.

Defender pairs are evaluated across the horizon and retained when the preferred
defender changes between gameweeks.

The output exposes:

- defender pair identity
- preferred defender by gameweek
- number of preference changes

This identifies genuine projected rotation opportunities rather than simply
listing all defender combinations.

### Fixture Clashes

Added Fixture Clash Intelligence.

A clash exists when two projected Starting XI players directly oppose one
another in the same Premier League fixture.

Clashes are identified using reciprocal:

`team_id → opponent_team_id`

relationships.

Clash counts represent player-pair clashes rather than distinct football
fixtures.

Missing or ambiguous opponent metadata does not manufacture a clash.

### Weak Fixture Clusters

Added Weak Fixture Cluster Intelligence.

A projected Starting XI player is treated as weak for this analysis when their
Projected Points are below 3.0.

A gameweek becomes a weak fixture cluster when at least three Starting XI
players meet that condition.

Missing projections remain unknown rather than being converted into artificial
zero-point weaknesses.

### Position Depth

Added Position Depth Intelligence across the complete 15-player squad.

Usable players require at least 3.0 Projected Points.

Minimum usable position requirements are:

- GK: 1
- DEF: 3
- MID: 2
- FWD: 1

The model exposes usable-player counts, depth counts and weak-depth positions.

### Repeated Benching

Added Repeated Benching Intelligence.

The model tracks for every squad player:

- starts
- bench appearances
- benched gameweeks
- average projected points while benched
- repeated-benching state
- meaningful repeated-benching state

A player is repeatedly benched when projected onto the bench at least twice
within the horizon.

Repeated benching is considered meaningful when average projected bench points
are at least 3.0.

### Structural Weakness

Added explainable Structural Weakness Intelligence combining four explicit
problems:

1. Weak Fixture Cluster
2. Position Depth Weakness
3. Uncovered Weak XI
4. Fixture Clash

Severity is determined from the number of active structural problems:

- 0 → None
- 1 → Low
- 2 → Moderate
- 3 → High
- 4 → Severe

The model exposes:

- structural problems by gameweek
- gameweeks containing problems
- worst gameweek
- maximum problem count
- maximum severity

No hidden weighted squad-health score is used.

### Production Integration

Integrated Squad Horizon into the real Squad Intelligence import path.

The production service:

1. validates the imported FPL squad
2. resolves all 15 imported FPL player IDs to local players
3. requests the existing multi-gameweek Expected Points projection for each player
4. adapts the authoritative projection data into the Squad Horizon contract
5. preserves fixture opponent metadata where it is unambiguous
6. passes the complete adapted squad into `SquadHorizonIntelligence`

An incomplete 15-player resolution does not produce a misleading partial
Squad Horizon.

### Squad Intelligence Interface

Added a dedicated Squad Horizon interface to the Squad page.

The interface now presents:

- maximum structural risk
- gameweek structural severity
- Starting XI projected points
- formation
- weak starters
- player clashes
- weak position depth
- Bench Coverage
- Goalkeeper Rotation
- Defensive Rotation
- Repeated Benching
- detailed Structural Weakness explanation

The interface uses responsive layouts and controlled empty states consistent
with the existing application design.

### Architecture Decision

Squad Horizon sits above the existing Expected Points architecture.

The intended flow is:

`Existing Player Projections`
`→ Squad Horizon`
`→ Rotation / Bench / Structural Analysis`

`MultiGameweekExpectedPoints` remains the player-projection source of truth.

Squad Horizon does not recalculate player Expected Points and does not maintain
an independent scoring model.

This preserves one projection architecture and prevents squad-planning logic
from drifting away from player-level Expected Points.

### Fixture Metadata Decision

The existing multi-gameweek service's fixture-level opponent metadata is used
for Squad Horizon fixture analysis.

When a player has exactly one fixture in a gameweek, authoritative opponent
metadata can be preserved.

When multiple fixtures exist in one gameweek, a single opponent is not
manufactured for the aggregated player/gameweek row.

This keeps the v0.32 model safe for the dedicated Blank & Double Gameweek work
planned for v0.33.0.

### Real-Data Validation

Real-data integration confirms:

- 15 of 15 imported players resolve locally
- 15 of 15 players have valid FPL positions
- 15 of 15 players currently expose multi-gameweek projections
- 90 projected fixtures are available across the real squad projection source
- the production horizon resolves gameweeks 2, 3 and 4
- 90 player/gameweek projection comparisons reconcile exactly
- zero projection mismatches exist against `MultiGameweekExpectedPoints`
- authoritative single-fixture opponent metadata is preserved
- fixture clashes are supported by reciprocal fixture metadata

The real-data integration also exposes explainable:

- Starting XI selections
- bench coverage
- weak fixture clusters
- position depth
- fixture clashes
- structural weakness
- goalkeeper rotation
- defensive rotation
- repeated benching

### Testing

Added dedicated synthetic, edge-case, service and real-data coverage for:

- Squad Horizon construction
- Starting XI selection
- legal formations
- Starting XI edge cases
- Bench Coverage
- Defensive Rotation
- Goalkeeper Rotation
- Goalkeeper Rotation edge cases
- Fixture Clashes
- Weak Fixture Clusters
- Position Depth
- Repeated Benching
- Repeated Benching edge cases
- Structural Weakness
- production service orchestration
- real-data production integration
- projection reconciliation
- fixture metadata reconciliation

The focused v0.32.0 model coverage passes:

- 194 synthetic/model assertions
- 65 production-service assertions
- 18 real-data integration assertions

The final complete regression suite passes with:

- 155 test files
- 155 test files passed
- 0 test files failed
- 0 test files with errors
- 4,840 assertions passed
- 0 assertions failed
- approximately 165 seconds total runtime

### Completion Notes

v0.32.0 completes the squad-level multi-gameweek planning foundation.

The application can now evaluate not only which individual players project well,
but how the complete 15-player squad fits together across upcoming gameweeks.

This provides the structural foundation required by later:

- Blank Gameweek planning
- Double Gameweek planning
- Wildcard timing
- Free Hit Intelligence
- Bench Boost Intelligence
- longer-term squad planning

The next milestone is v0.33.0 — Blank & Double Gameweek Intelligence.

---

## v0.33.0 — Blank & Double Gameweek Intelligence

### Status

**COMPLETE**

### Dependency

Builds on:

- v0.30.0 Multi-Gameweek Expected Points Intelligence
- v0.32.0 Squad Horizon & Rotation Intelligence

### Goal

Make fixture modelling explicitly aware of unusual FPL schedules without
introducing a separate Blank/Double Gameweek scoring system.

### Delivered

Added explicit schedule modelling for:

- Blank Gameweeks
- Normal Gameweeks
- Double Gameweeks
- teams with zero fixtures
- teams with one fixture
- teams with multiple fixtures

Added `GameweekScheduleIntelligence` as the canonical team/gameweek schedule
classification model.

Schedule semantics are:

- 0 fixtures → Blank
- 1 fixture → Normal
- 2+ fixtures → Double

Complete fixture rows are preserved for multi-fixture gameweeks.

Fixture ordering is deterministic by kickoff time and fixture identity.

### Multi-Gameweek Expected Points

Extended `MultiGameweekExpectedPoints` with explicit schedule semantics.

Player/gameweek projections now preserve:

- `fixture_count`
- `schedule_type`
- complete fixture arrays

Internal gameweek gaps between represented projections are preserved as explicit
Blank Gameweeks with:

- zero projected points
- zero fixtures
- empty fixture arrays

Double Gameweeks retain their individual fixture projections while exposing the
existing aggregated gameweek Expected Points total.

A single aggregate opponent is not manufactured when multiple fixtures exist.

The public projection method signature remains unchanged.

### Squad Horizon Integration

Extended `SquadHorizonIntelligenceService` to preserve authoritative schedule
metadata through the production adapter.

Extended `SquadHorizonIntelligence` player/gameweek rows with:

- fixture count
- schedule type
- complete fixture evidence

Unknown legacy schedule data remains unknown rather than being incorrectly
classified as Blank.

Squad Horizon continues to use the existing multi-gameweek Expected Points model
rather than recalculating player projections.

### Starting XI Intelligence

Validated Starting XI optimisation through:

- Normal Gameweeks
- Blank Gameweeks
- Double Gameweeks

Selection remains based on:

- legal FPL formations
- aggregated player projected points

No special rule forces a Double Gameweek player into the Starting XI.

No special rule automatically excludes a Blank Gameweek player outside the
normal projected-points optimisation.

### Captain Intelligence

Added explicit captain selection to Squad Horizon gameweeks.

Captain selection:

- is restricted to the selected Starting XI
- uses projected points
- uses deterministic lower-player-ID tie-breaking

Blank and Double Gameweek value therefore influences captaincy naturally through
the existing projection model.

No separate Double Gameweek captain bonus is used.

### Double Gameweek Fixture Clashes

Extended Fixture Clash Intelligence to understand multiple fixtures in one
gameweek.

Where usable fixture-level relationships exist, clashes are validated using:

- fixture identity
- reciprocal team/opponent relationships

Fixture-level evidence is authoritative when available.

The existing aggregate-opponent logic remains as a compatibility fallback when
fixture-level relationships are unavailable.

Each opposing player pair produces at most one clash record per gameweek.

### Transfer Evaluation

Added `evaluateTransfer()` to Squad Horizon Intelligence.

Transfer evaluation compares:

- the current squad horizon
- the replacement squad horizon
- before/after Starting XI projected points

Blank and Double Gameweek value therefore affects transfer decisions through the
existing Expected Points architecture.

A Double Gameweek does not automatically make a transfer an improvement.

Transfer outcomes can correctly remain:

- Improvement
- Neutral
- Regression

depending on projected Starting XI impact.

### Historical Snapshot Integrity

During final v0.33 regression testing, completed-gameweek historical snapshot
price handling was strengthened.

Completed-gameweek snapshot capture now prefers authoritative historical price
evidence from `player_fixture_history` rather than later live `players.price`.

Live price remains only a defensive fallback where historical price evidence is
unavailable.

The affected GW2 historical snapshots were repaired from persisted fixture
history.

Validation confirmed:

- 92 snapshot prices corrected
- 0 historical-price mismatches remained after repair
- 626 comparable players matched historical price evidence
- 626 comparable players matched historical selected-manager evidence
- 92 legitimate historical/live price differences now remain

These live differences are expected because completed snapshots are immutable
historical state.

### Historical Update Workflow

Integrated completed-gameweek snapshot capture into the full player
fixture-history update workflow.

The intended refresh dependency is now:

`Live FPL Update`
`→ Fixture Update`
`→ Full Player Fixture History Import`
`→ Completed-Gameweek Snapshot Capture`

Added `PlayerGameweekSnapshotCaptureGate`.

Automatic capture is allowed only when:

- the import is FULL
- at least one player is selected
- every selected player is processed
- zero players fail

Batch imports do not trigger snapshot capture.

Incomplete or failed full imports do not trigger snapshot capture.

The standalone snapshot capture runner remains available for deliberate manual
use and diagnostics.

### Architecture Decision

Blank and Double Gameweek Intelligence extends the existing architecture rather
than competing with it.

The intended flow is:

`Fixture Schedule`
`→ Multi-Gameweek Expected Points`
`→ Squad Horizon`
`→ Starting XI / Captaincy / Clash / Transfer Decisions`

There is:

- no independent BGW scoring model
- no independent DGW scoring model
- no artificial DGW bonus
- no artificial BGW penalty beyond absence of fixture points

This keeps one authoritative player-projection architecture.

### Testing

Added dedicated coverage for:

- explicit schedule classification
- schedule edge cases
- real schedule data
- multi-gameweek schedule semantics
- Squad Horizon schedule semantics
- schedule propagation
- Double Gameweek fixture clashes
- Double Gameweek clash edge cases
- Blank/Double Starting XI behaviour
- Blank/Double captaincy
- captaincy edge cases
- Blank/Double transfer evaluation
- transfer-evaluation edge cases
- mixed Normal/Blank/Double regression
- snapshot capture gating
- fixture-history/snapshot-capture workflow wiring

The focused v0.33 Blank/Double Gameweek suite passes:

- 521 unique focused assertions
- 0 failures

Snapshot workflow regression coverage confirms:

- snapshot capture gate: 11 / 11 passed
- fixture-history/snapshot integration: 28 / 28 passed
- snapshot capture service: passed
- snapshot capture runner: passed
- historical snapshot integration: passed

The final complete regression suite passes with:

- 171 test files
- 171 test files passed
- 0 test files failed
- 0 test files with errors
- 5,234 assertions passed
- 0 assertions failed
- 169.715 seconds total runtime

### Completion Notes

v0.33.0 completes the Blank & Double Gameweek Intelligence milestone.

The application now represents unusual FPL schedules explicitly from fixture
structure through player projections and squad-level decisions.

Blank Gameweeks no longer disappear or produce manufactured fixture projections.

Double Gameweeks retain individual fixture evidence while still using aggregated
gameweek Expected Points for optimisation.

Starting XI, captaincy, fixture clashes and transfer evaluation all remain driven
by the same schedule-aware Expected Points architecture.

Historical snapshot capture is now safer and part of the correct full historical
update workflow.

The completed BGW/DGW architecture provides the foundation required by
v0.34.0 — Chip Intelligence.


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

**START: v0.34.0 — Chip Intelligence**

v0.33.0 Blank & Double Gameweek Intelligence is complete.

The final v0.33.0 regression baseline is:

- 171 test files
- 171 test files passed
- 0 test files failed
- 0 test files with errors
- 5,234 assertions passed
- 0 assertions failed
- 169.715 seconds total runtime

The completed v0.33.0 milestone now provides explicit Blank, Normal and Double
Gameweek semantics throughout the existing fixture, projection and squad-planning
architecture.

The application can now safely represent:

- teams with zero fixtures in a gameweek
- teams with one fixture in a gameweek
- teams with multiple fixtures in a gameweek
- explicit Blank Gameweek player projections
- individual Double Gameweek fixture evidence
- schedule-aware Squad Horizon analysis
- Blank/Double-aware Starting XI optimisation
- schedule-aware captain selection
- Double Gameweek fixture clashes
- Blank/Double-aware transfer evaluation

The existing Expected Points architecture remains the source of player projection
value. No separate Blank or Double Gameweek scoring model was introduced.

Historical snapshot integrity was also strengthened during v0.33.0.

Completed-gameweek snapshot capture now uses authoritative historical fixture
history for historical price evidence, and successful full fixture-history
updates automatically trigger safe completed-gameweek snapshot capture.

The next milestone is:

**v0.34.0 — Chip Intelligence**

Initial v0.34.0 focus:

1. define a common explainable Chip Intelligence decision contract
2. add Wildcard Timing Intelligence using existing squad and multi-gameweek
   projections
3. add Free Hit Intelligence for one-gameweek squad optimisation
4. add Bench Boost Intelligence using complete squad and bench projections
5. add Triple Captain Intelligence using captain and multi-gameweek Expected
   Points
6. account explicitly for Blank and Double Gameweeks when evaluating chip value
7. distinguish immediate chip value from the value of holding the chip
8. provide Use / Consider / Hold recommendations with supporting evidence
9. add controlled synthetic and real-data regression coverage for chip decisions

v0.34.0 must build on the existing:

- Expected Points Intelligence
- Multi-Gameweek Expected Points Intelligence
- Squad Horizon Intelligence
- Blank & Double Gameweek Intelligence
- Captain Intelligence
- Wildcard Intelligence

Chip Intelligence should coordinate these existing systems rather than duplicate
their scoring models.

Wildcard Timing should evaluate the value of restructuring the squad rather than
replace the existing Wildcard squad optimiser.

Free Hit Intelligence should optimise specifically for the target gameweek while
respecting normal FPL squad constraints.

Bench Boost Intelligence should evaluate the projected value and reliability of
the complete 15-player squad, with particular attention to bench strength.

Triple Captain Intelligence should build on existing Captain Intelligence and
Expected Points rather than introduce an independent captain-scoring model.

Blank and Double Gameweek information should act through the schedule-aware
projection architecture completed in v0.33.0.

All existing non-chip decision behaviour must remain protected while Chip
Intelligence is introduced.