# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

## [0.31.0] - Market Intelligence

### Added

- Added Market Intelligence for evaluating historical FPL market movement alongside existing player-performance, fixture and value intelligence.
- Added `MarketIntelligenceService.php` as the dedicated market-analysis service.
- Added historical player price movement intelligence using persisted gameweek snapshots.
- Added price movement classifications:
  - Rising
  - Falling
  - Stable
  - Insufficient Historical Data
- Added historical ownership movement intelligence using exact raw selected-manager counts.
- Added ownership movement classifications:
  - Rising
  - Falling
  - Stable
  - Insufficient Historical Data
- Added historical transfer momentum intelligence using persisted per-gameweek transfer activity.
- Added transfer momentum classifications:
  - Rising
  - Falling
  - Stable
  - Insufficient Historical Data
- Added combined Market Intelligence signals using available price, ownership and transfer evidence.
- Added combined market classifications:
  - Strong Rising
  - Rising
  - Stable
  - Falling
  - Strong Falling
  - Mixed
  - Insufficient Evidence
- Added explicit evidence counting for combined market classifications.
- Added Value Trend Intelligence combining existing Player Value ratings with Market Intelligence.
- Added Value Trend classifications:
  - Improving Value
  - Stable Value
  - Deteriorating Value
  - Mixed Value Signal
  - Insufficient Evidence
- Added separation between player value quality and market direction so market popularity cannot independently redefine player quality.
- Added public Market Intelligence summaries for player-facing integrations.
- Added compact public Value Trend summaries.
- Added Market Intelligence to individual Player Intelligence profiles.
- Added a dedicated Market Intelligence section to the Player Profile.
- Added Market Signal, Evidence and Value Trend presentation.
- Added Price Movement, Ownership Movement and Transfer Momentum presentation.
- Added controlled early-season presentation when sufficient historical evidence is not yet available.
- Added immutable player-gameweek snapshot capture for completed historical gameweeks.
- Added `insertIfAbsent()` snapshot persistence so completed historical snapshots cannot be overwritten by later live data.
- Added completed-gameweek snapshot capture through `PlayerGameweekSnapshotCapture`.
- Added GW1 market snapshot recovery tooling using persisted player fixture history.
- Added recovery of exact historical selected-manager counts where official fixture-history evidence is available.
- Added recovery of historical player prices where persisted fixture-history evidence differs from the captured snapshot.
- Added dedicated Market Intelligence unit, integration, summary and Player Profile regression coverage.
- Added dedicated Value Trend unit, integration, summary and Player Intelligence integration coverage.
- Added a complete v0.31.0 Market Intelligence milestone acceptance test.
- Added request-scoped Player Intelligence summary caching.
- Added dedicated request-cache regression coverage.

### Changed

- Changed historical ownership intelligence to use exact raw selected-manager counts rather than reconstructing ownership from percentage values.
- Changed completed player-gameweek snapshots to behave as immutable historical records.
- Removed player-gameweek snapshot writing from the normal live FPL data update process so later bootstrap refreshes cannot overwrite completed historical state.
- Changed Market Intelligence to require sufficient historical evidence before claiming meaningful price, ownership or transfer direction.
- Changed combined Market Intelligence to require at least two available market components before producing a directional combined signal.
- Changed early-season Market Intelligence to explicitly distinguish insufficient evidence from genuine stable market movement.
- Changed Value Trend Intelligence to preserve the existing Player Value model as the source of player-value quality.
- Changed Value Trend so rising market activity alone cannot turn a weak-value player into a strong-value recommendation.
- Improved Player Intelligence performance by reusing complete player-summary calculations within the same request.
- Preserved the existing Player Intelligence, Expected Points, Form, Fixture, Captain, Transfer, Wildcard and Gameweek models while adding Market Intelligence as a supporting decision signal.

### Fixed

- Fixed live FPL data refreshes overwriting historical player-gameweek market snapshots.
- Fixed historical ownership snapshots lacking exact selected-manager counts where recoverable official fixture-history evidence exists.
- Fixed historical price snapshots where recoverable fixture-history evidence differed from the previously captured value.
- Fixed early-season Market Intelligence potentially presenting unavailable historical movement as meaningful market direction.
- Prevented zero selected-manager or zero transfer values from being incorrectly treated as unavailable data.
- Prevented duplicate gameweek transfer evidence from being counted more than once when transfer momentum is built from per-fixture player history.
- Prevented Market Intelligence from exposing internal raw historical arrays through its compact public summary contract.
- Prevented Value Trend public summaries from exposing unnecessary internal value-rating and market-classification fields.
- Fixed complete-suite runner compatibility for the Market Intelligence milestone acceptance result marker.

### Historical Market Data Validation

- Confirmed GW1 player fixture history contains exact raw selected-manager counts.
- Confirmed official FPL bootstrap data exposes ownership percentage but does not provide the equivalent exact historical raw selected-manager count.
- Confirmed persisted player fixture history provides the authoritative recoverable GW1 raw selection evidence.
- Recovered 610 matched GW1 player snapshot records from persisted historical fixture evidence.
- Confirmed four snapshot records without corresponding GW1 fixture history were safely left untouched.
- Confirmed snapshot recovery is controlled and does not fabricate missing historical evidence.
- Confirmed current early-season Market Intelligence correctly reports insufficient historical evidence while only one historical gameweek is available.
- Confirmed current Market Intelligence will automatically become directional as additional historical gameweek evidence is captured.

### Performance

- Added request-scoped caching to `PlayerIntelligenceService` for complete player-summary collections.
- Reduced repeated calculation of the same complete player dataset during complex Player Intelligence operations.
- Confirmed cached and uncached summary results remain identical.
- Reduced `PlayerIntelligenceServiceTest.php` runtime substantially while preserving existing behaviour.
- Preserved request-scoped cache lifetime so cached Player Intelligence data does not persist across independent application requests.

### Testing

- Added dedicated Market Intelligence movement tests.
- Added Market Intelligence integration coverage against persisted historical data.
- Added public Market Intelligence summary contract coverage.
- Added Player Intelligence Market Intelligence integration coverage.
- Added dedicated Value Trend classification tests.
- Added Value Trend integration and summary regression coverage.
- Added Player Intelligence Value Trend integration coverage.
- Added Player Intelligence request-cache regression coverage.
- Added `MarketIntelligenceMilestoneTest.php` as the complete v0.31.0 acceptance gate.
- Confirmed the milestone acceptance test passes all 75 checks.
- Re-ran the complete project regression suite after final Market Intelligence integration.
- Confirmed all 140 of 140 test files pass.
- Confirmed all 4,581 assertions pass.
- Confirmed zero test failures.
- Confirmed zero test execution errors.
- Complete regression suite runtime: approximately 164 seconds.

## [0.30.0] - Multi-Gameweek Expected Points Intelligence

### Added

- Added multi-gameweek Expected Points modelling across upcoming fixture horizons.
- Added `MultiGameweekExpectedPoints` support for projecting individual future fixtures while preserving the existing single-fixture Expected Points model as the scoring source of truth.
- Added Next 3, Next 5 and Next 6 Expected Points planning totals.
- Added fixture-specific multi-gameweek projection contexts using real upcoming Premier League fixtures.
- Added position-aware fixture opportunity to each future Expected Points projection.
- Added opponent Attack Rating and Defence Rating context to future fixture projections.
- Added opponent team names and home/away context to the multi-gameweek service contract.
- Added individual fixture projected points, projected minutes and projection confidence across the planning horizon.
- Added full Expected Points component and input explainability to each future fixture projection.
- Added multi-gameweek Expected Points to individual Player Intelligence profiles.
- Added a new Multi-Gameweek Planning section to the Player Profile UI.
- Added Planning Horizons presentation for Next 3, Next 5 and Next 6 Expected Points.
- Added Upcoming Projections presentation showing gameweek, opponent, venue, fixture opportunity, projected minutes, confidence and xP.
- Added responsive styling for the Multi-Gameweek Planning interface.
- Added dedicated multi-gameweek service, profile and page integration tests.
- Added controlled fixture-sensitivity diagnostics to validate that fixture context materially affects Expected Points.

### Changed

- Extended Player Intelligence to support medium-term planning without adding six future projections to every bulk player summary.
- Kept multi-gameweek projection generation scoped to individual players to avoid unnecessarily increasing the cost of `getAllPlayerSummaries()`.
- Reused the existing Team Strength, Team Performance and Fixture Intelligence models for future fixture context rather than introducing a separate fixture-strength system.
- Preserved the existing immediate next-fixture Expected Points projection and confirmed it matches the first fixture of the multi-gameweek projection horizon.
- Improved goalkeeper and defender defensive fixture sensitivity by blending opponent Attack Rating with broader fixture opportunity.
- Updated clean-sheet probability so materially different fixture contexts no longer collapse to identical defensive expectations when opponent Attack Ratings are equal.
- Updated expected goals-conceded modelling to retain broader fixture context alongside specialist opponent attacking strength.
- Kept goalkeeper save projections driven by opponent attacking strength rather than general fixture opportunity.
- Added shared defensive-threat context so clean-sheet probability and expected goals-conceded deductions move coherently.
- Added opponent-name lookup to the multi-gameweek service contract without introducing per-fixture database queries.
- Refined Player Profile spacing and readability around the new planning section.

### Fixed

- Fixed multi-gameweek goalkeeper projections where fixtures with substantially different Fixture Intelligence opportunity scores could produce identical Expected Points because defensive components only considered opponent Attack Rating.
- Fixed clean-sheet probability discarding broader fixture context.
- Fixed expected goals-conceded projections discarding broader fixture context.
- Fixed real multi-gameweek projections such as highly favourable and highly difficult fixtures collapsing to the same defensive Expected Points.
- Fixed opponent names initially resolving as blank in the multi-gameweek fixture contract.
- Fixed player-page multi-gameweek integration test behaviour by using real HTTP page requests rather than including `player.php` directly under CLI execution.
- Fixed home/away page-test assertions so rendered whitespace does not create false failures.

### Validation

- Controlled fixture-sensitivity testing confirms favourable fixtures produce higher clean-sheet probability and smaller expected goals-conceded deductions than difficult fixtures when opponent Attack Rating is held constant.
- Confirmed goalkeeper expected saves remain unchanged when only broader fixture opportunity changes.
- Confirmed real goalkeeper multi-gameweek projections now separate appropriately across materially different fixture contexts.
- Confirmed immediate next-fixture Expected Points remains aligned with the first multi-gameweek fixture projection.
- Confirmed Player Profile planning horizons equal the sum of their underlying fixture projections.
- Confirmed real opponent names, H/A venue context, fixture opportunity and projected points render correctly on the Player Profile.
- Confirmed no temporary diagnostic output is exposed on the live Player Profile.
- Complete regression suite passes with 117 of 117 test files.
- 4,071 of 4,071 assertions pass.
- Zero test failures and zero execution errors.

## [0.29.0] - Expected Points Intelligence

### Added

- Added next-gameweek FPL Expected Points modelling.
- Added projected-minutes modelling with availability, participation and recent-minutes evidence.
- Added projection confidence with bounded confidence labels and percentages.
- Added position-aware FPL Expected Points scoring for goalkeepers, defenders, midfielders and forwards.
- Added expected-goals and expected-assists projection inputs using recent per-90 performance evidence.
- Added clean-sheet probability modelling using recent defensive evidence and fixture context.
- Added goalkeeper save projections using recency-weighted saves per 90 and fixture opportunity.
- Added defensive-contribution projections for the 2026/27 FPL defensive-contribution scoring rules.
- Added position-specific defensive-action baselines and early-season sample regression.
- Added expected bonus-point modelling using recency-weighted BPS evidence, position baselines and projected minutes.
- Added a smooth probabilistic BPS-to-bonus curve calibrated from complete GW1 2026/27 data.
- Added Expected Points component evidence and specialist-component status reporting.
- Added real-data Expected Points, goalkeeper saves, defensive contributions and bonus diagnostics.
- Added baseline/calibration analysis tools for defensive contributions and bonus points.
- Expanded Expected Points regression coverage across Player Intelligence and downstream application services.
- Added expected goals-conceded deductions for goalkeepers and defenders using recency-weighted xGC evidence, early-season position regression, projected minutes and opponent Attack Rating.
- Added probabilistic goals-conceded scoring using a Poisson model to reflect the FPL rule of -1 point for every two goals conceded.
- Added real-data goals-conceded diagnostics covering low-risk and high-risk defensive projections.
- Added Projected Points presentation to the Player Profile, including Projected Minutes, Projection Confidence, expected goals, expected assists, clean-sheet probability and the full explainable FPL points breakdown.


### Changed

- Integrated Projected Points, Projected Minutes and Projection Confidence into Player Intelligence summaries.
- Replaced placeholder goalkeeper-save, defensive-contribution and bonus components with modelled expectations.
- Added early-season regression so limited fixture history is pulled toward position-level baselines rather than overfitting individual GW1 performances.
- Updated Expected Points component totals to include modelled saves, defensive contributions and bonus.
- Calibrated Expected Bonus as a probabilistic expectation rather than treating projected BPS as a deterministic realised BPS score.
- Updated complete Player Profiles to calculate and expose the same Expected Points contract already available in all-player summaries.
- Removed the zero floor from Projected Points so legitimate negative expected scoring remains representable.
- Kept Effective Confidence separate from Projected Points rather than multiplying it into the projection and double-counting playing-time uncertainty.
- Refined the Expected Points player-profile layout for clearer headline projections and component explainability.

### Deferred

- Deferred goalkeeper penalty-save modelling because complete GW1 2026/27 data contains no penalty saves and does not yet support a reliable empirical prior.
- Deferred yellow cards, red cards, own goals and penalties missed to a later calibration/backtesting milestone because current early-season evidence is too sparse for stable modelling.

### Validation

- Complete regression suite passes with 108 of 108 test files.
- 3,845 of 3,845 assertions pass with zero failures and zero test errors.
- Real-data validation confirms conservative early-season Expected Points behaviour, including modelled goalkeeper saves, defensive contributions and bonus.
- Complete regression suite passes with 110 of 110 test files.
- 3,924 of 3,924 assertions pass.
- Zero test failures and zero execution errors.
- Real-data validation confirms goals-conceded deductions integrate correctly into final Projected Points.

## [0.28.0] - Player Form Intelligence

### Added
- Added the first historical Player Form Intelligence layer built from persisted per-fixture FPL player history.
- Added `PlayerForm.php` for calculating recent player form from official stored fixture-performance evidence.
- Added position-aware Player Form modelling for goalkeepers, defenders, midfielders and forwards.
- Added bounded 0-100 Form Ratings.
- Added separate Performance Ratings so on-pitch performance can be evaluated independently from playing-time security.
- Added recent fixture sample tracking.
- Added recent appearance sample tracking.
- Added explicit zero-minute fixture tracking.
- Added participation-rate modelling across the recent fixture window.

- Added raw recent-form metrics including:
  - total points
  - total minutes
  - points per appearance
  - average appearance minutes
  - expected goals per 90
  - expected assists per 90
  - expected goal involvements per 90
  - BPS per 90
  - clean-sheet rate
  - expected goals conceded per 90

- Added Player Form component ratings including:
  - Points Rating
  - Minutes Rating
  - Expected Goal Involvement Rating
  - BPS Rating
  - Defensive Rating
- Added position-aware Form weighting so different player positions are assessed using metrics appropriate to their role.
- Added defensive weighting for goalkeepers and defenders.
- Added attacking expected-goal-involvement weighting for midfielders and forwards.
- Added stronger expected-goal-involvement weighting for forwards than midfielders.
- Added participation-sensitive Minutes Rating using the complete recent fixture window.
- Added preservation of zero-minute fixture evidence when evaluating playing-time security.

- Added recency weighting to Player Form calculations.
- Added greater weighting for more recent fixtures while retaining useful evidence from older fixtures in the recent sample.
- Added recency-weighted performance metrics.
- Added recency-weighted participation and minutes evidence.
- Added safe handling of incomplete historical samples.

- Added `PlayerFormTrend.php` for comparing short-term and longer-term player form.
- Added short-window and long-window Form analysis.
- Added Form Trend classification states:
  - Improving
  - Stable
  - Declining
  - Insufficient Data
- Added Participation Trend analysis.
- Added Minutes Trend analysis.
- Added independent trend differences for:
  - performance form
  - participation
  - minutes
- Added explicit trend sample contracts so early-season data cannot create misleading trend classifications.
- Added minimum difference thresholds before Form, Participation or Minutes trends are classified as Improving or Declining.

- Added separation between holistic Form Rating and on-pitch Performance Rating.
- Added Performance Rating calculations that exclude playing-time security from the performance comparison.
- Added independent performance-trend modelling so a player's reduced minutes do not automatically classify their on-pitch performance as declining.
- Added support for identifying cases where performance remains stable while participation or minutes decline.

- Added Player Form Intelligence fields to `PlayerIntelligenceService.php`.
- Added Form Rating to Player Intelligence summaries.
- Added Performance Rating to Player Intelligence summaries.
- Added Form Trend to Player Intelligence summaries.
- Added Participation Trend to Player Intelligence summaries.
- Added Minutes Trend to Player Intelligence summaries.
- Added Form fixture sample size to Player Intelligence summaries.
- Added Form appearance sample size to Player Intelligence summaries.
- Added zero-minute sample counts to Player Intelligence summaries.
- Added Form participation rate to Player Intelligence summaries.
- Added Form, participation and minutes trend differences to Player Intelligence diagnostics.
- Added Player Form Intelligence to individual player-profile service responses.
- Added consistent Form Intelligence output across bulk player summaries and individual player profiles.

- Added request-level Player Form caching to reduce repeated historical calculations across large Player Intelligence operations.
- Added request-level Player Form Trend caching.
- Added reuse of calculated historical Form models when the same player is evaluated repeatedly during a request.
- Added performance protection for Player Intelligence operations that analyse large portions of the current 610-player dataset.

- Added a new Historical Intelligence section to the Player Intelligence profile page.
- Added a dedicated Recent Form dashboard card.
- Added front-end display of:
  - Form Rating
  - Performance Rating
  - Participation
  - Performance Trend
  - Participation Trend
  - Minutes Trend
  - recent fixture sample
  - recent appearance sample
  - zero-minute fixture sample where applicable
- Added visual 0-100 progress bars for Form Rating and Performance Rating.
- Added visual participation progress.
- Added explanatory context distinguishing holistic recent form from on-pitch performance.
- Added a Historical Sample diagnostic strip.
- Added responsive Recent Form layout support.
- Added Recent Form styling scoped specifically to the Player Form Intelligence component so existing Player Profile components are unaffected.

### Changed
- Changed Player Form calculations to use persisted official per-fixture history rather than relying only on the current FPL bootstrap state.
- Changed recent-form evaluation to distinguish known zero-minute fixtures from missing historical evidence.
- Changed Minutes Rating to use the complete recent fixture window rather than appearances only.
- Changed on-pitch performance averages to exclude zero-minute fixtures where appropriate while retaining those fixtures for participation analysis.
- Changed Player Form to use position-aware weighting instead of applying one generic performance formula to every position.
- Changed attacking Form evaluation to use expected goal involvement per 90 where appropriate.
- Changed defensive Form evaluation to incorporate clean-sheet and expected-goals-conceded evidence where appropriate.
- Changed recent-form metrics to favour newer historical evidence through recency weighting.

- Changed Form Trend modelling so holistic playing-time changes do not incorrectly determine the player's performance direction.
- Changed the primary Form Trend comparison to use performance-only ratings.
- Separated performance direction from Participation Trend and Minutes Trend.
- Changed trend classifications to require sufficient historical evidence before claiming Improving, Stable or Declining form.
- Changed early-season trend output to `Insufficient Data` when the available historical window is incomplete.
- Preserved independent participation and minutes trends even when on-pitch performance remains stable.

- Extended Player Intelligence summaries with historical Form Intelligence diagnostics without yet allowing Form to alter the main Player Intelligence Score.
- Kept Form Intelligence diagnostic-only during the initial integration stage.
- Preserved existing Player Intelligence scoring, transfer, captaincy, squad and Wildcard behaviour while exposing the new historical evidence for validation.
- Extended individual Player Intelligence profiles with the same Form Intelligence contract used by bulk Player Intelligence summaries.

- Optimised Player Intelligence historical calculations after the additional Form layer increased complete-suite runtime.
- Reduced repeated Player Form and Form Trend calculations within the same request.
- Reduced complete regression-suite runtime from approximately 255 seconds to approximately 134 seconds while preserving identical tested behaviour.
- Reduced `PlayerIntelligenceServiceTest.php` runtime from approximately 55 seconds to approximately 18 seconds.

- Updated the Player Profile layout so Recent Form appears between Core Ratings and FPL Assessment.
- Changed Recent Form presentation from generic summary cells to dedicated full-width intelligence cards.
- Changed the trend layout to use three evenly distributed trend cards.
- Changed the Historical Sample display to a dedicated diagnostic footer.
- Kept all Recent Form styling isolated from existing Player Profile components.

### Fixed
- Fixed Player Form averages potentially allowing zero-minute fixtures to contaminate on-pitch performance calculations.
- Fixed zero-minute historical records being unsuitable for participation modelling when only appearance-based averages were considered.
- Fixed Minutes Rating not fully reflecting a player losing their place in the team.
- Fixed recent Form calculations treating all historical fixtures with equal importance despite more recent performances being more relevant.

- Fixed Form Trend potentially being classified as Declining solely because a player's recent minutes or participation had fallen.
- Fixed playing-time security and on-pitch performance being coupled too tightly in trend analysis.
- Fixed strong recent performance being obscured by declining participation.
- Fixed trend classification potentially making claims from insufficient early-season evidence.
- Prevented GW1-only historical data from being classified as a meaningful Improving, Stable or Declining trend.

- Fixed individual Player Intelligence profiles initially receiving default/null Form Intelligence values even though bulk Player Intelligence summaries contained valid Form data.
- Fixed `getPlayerProfile()` not attaching Player Form and Player Form Trend diagnostics to the profile summary.
- Unified Form Intelligence data between `getAllPlayerSummaries()` and `getPlayerProfile()`.
- Fixed the Player Profile Recent Form section initially rendering:
  - unavailable Form Rating
  - unavailable Performance Rating
  - unavailable Participation
  - zero recent fixtures
  - zero appearances
  despite valid stored fixture history being available.
- Confirmed real Player Profile Form Intelligence now resolves against persisted fixture-history data.

- Fixed excessive repeated historical Form calculations during large Player Intelligence operations.
- Prevented the expanded historical intelligence layer from causing the complete test runner to exceed its previous execution-time allowance.
- Improved complete-suite performance while preserving all existing application behaviour and regression coverage.

### Current Real-Data Validation
- Confirmed Player Form operates against the complete persisted GW1 fixture-history dataset.
- Confirmed the historical fixture-history table remains at 610 GW1 records after Player Form development and repeated test execution.
- Confirmed Player Form development does not mutate or duplicate stored fixture-history records.
- Confirmed zero-minute fixture history remains available as legitimate participation evidence.
- Confirmed early-season Form Trend correctly reports `Insufficient Data` with only GW1 historical evidence available.

- Confirmed Raya's real Player Intelligence profile resolves historical Form Intelligence from stored GW1 fixture history.
- Confirmed Raya currently exposes:
  - Form Rating: 81.4
  - Performance Rating: 75.2
  - Participation: 100.0%
  - Performance Trend: Insufficient Data
  - Participation Trend: Insufficient Data
  - Minutes Trend: Insufficient Data
  - Historical fixture sample: 1
  - Historical appearance sample: 1
- Confirmed Player Form Intelligence is visible on the live local Player Profile UI.
- Confirmed the Recent Form UI renders correctly within the existing Player Intelligence design system.

### Testing
- Added `PlayerFormTest.php`.
- Added validation of the Player Form model structure.
- Added validation that Form Ratings remain within the 0-100 intelligence scale.
- Added controlled historical fixture-sample coverage.
- Added appearance-count validation.
- Added zero-minute fixture validation.
- Added participation-rate validation.
- Added raw Form metric validation.
- Added points-per-appearance validation.
- Added average-appearance-minutes validation.
- Added BPS-per-90 validation.
- Added goalkeeper clean-sheet-rate validation.
- Added Form component-rating validation.
- Added position-aware weighting validation.
- Added goalkeeper defensive-weighting validation.
- Added midfielder attacking-weighting validation.
- Added forward expected-goal-involvement weighting validation.
- Added validation that midfielder and forward weighting differs appropriately.
- Added participation-effect validation.
- Added validation that zero-minute history prevents an incorrectly perfect Minutes Rating.
- Added position-aware output validation.
- Added invalid-player handling coverage.
- Added component rating-bound validation.

- Added `PlayerFormRecencyWeightingTest.php`.
- Added controlled recency-weighting regression coverage.
- Added validation that newer historical performances receive greater influence than older performances.
- Added validation that recency weighting preserves bounded Form outputs.
- Added protection against regressions back to equally weighted historical Form calculations.

- Added Performance Rating regression coverage.
- Added validation that holistic Form Rating and performance-only rating can differ.
- Added validation that performance calculations remain independent from participation where required.
- Added controlled reduced-minutes scenarios.

- Added `PlayerFormTrendTest.php`.
- Added Form Trend model-structure validation.
- Added short-window and long-window rating validation.
- Added controlled Improving Form classification coverage.
- Added Stable Form classification coverage.
- Added Declining Form threshold coverage.
- Added exact trend-threshold boundary validation.
- Added insufficient-data classification coverage.
- Added Participation Trend validation.
- Added Minutes Trend validation.
- Added trend sample-contract validation.
- Added controlled declining-participation scenarios with strong on-pitch performance.
- Added regression protection ensuring falling minutes do not automatically produce Declining performance form.
- Added validation that performance, participation and minutes trends remain independently classifiable.
- Added current real early-season data validation.
- Confirmed GW1-only Form, Participation and Minutes trends correctly report `Insufficient Data`.

- Extended `PlayerIntelligenceServiceTest.php` with Player Form Intelligence regression coverage.
- Added validation that all Player Intelligence summaries expose Form Intelligence fields.
- Added validation that all available Form Ratings remain between 0 and 100.
- Added validation that all available Performance Ratings remain between 0 and 100.
- Added validation that all Form Intelligence trend labels use supported states.
- Added validation that Form sample counts remain non-negative.
- Added validation that available Form participation rates remain between 0 and 100.
- Added validation that current early-season Form trends remain `Insufficient Data` where historical evidence is incomplete.
- Confirmed the expanded Player Intelligence Service test passes all 402 assertions.

- Added `PlayerFormProfilePageTest.php`.
- Added Player Profile request validation for Form Intelligence.
- Added Recent Form section rendering validation.
- Added Historical Intelligence heading and explanation validation.
- Added Form Rating rendering validation.
- Added Performance Rating rendering validation.
- Added participation rendering validation.
- Added Performance Trend rendering validation.
- Added Participation Trend rendering validation.
- Added Minutes Trend rendering validation.
- Added early-season insufficient-data validation.
- Added historical fixture-sample rendering validation.
- Added historical appearance-sample rendering validation.
- Added PHP error detection for the new profile integration.
- Added Player Form profile-page performance validation.

- Re-ran the complete project regression suite after Player Form Intelligence integration.
- Confirmed all targeted Player Form tests pass.
- Confirmed `PlayerIntelligenceServiceTest.php` passes.
- Confirmed `PlayerFormProfilePageTest.php` passes.
- Confirmed the complete `RunAllTests.php` suite passes with zero test-file failures and zero test-file errors.
- Confirmed the optimised complete suite executes in approximately 134 seconds.

## [0.27.0] - Historical Gameweek & Fixture Intelligence

### Added
- Added the historical data foundation for preserving FPL gameweek and player performance data across the season.
- Added the `gameweeks` database table for storing all 38 official FPL gameweeks.
- Added persistent gameweek identity using the official FPL gameweek ID.
- Added gameweek deadline storage.
- Added gameweek completion and data-check state.
- Added previous, current and next gameweek state flags.
- Added database uniqueness protection for official FPL gameweek IDs.
- Added `GameweekRepository.php` for accessing persisted gameweek data.
- Added repository support for retrieving gameweeks by official FPL gameweek ID.
- Added repository lookups for previous, current and next gameweeks.
- Added live FPL gameweek import support to the main FPL data updater.
- Added import of all 38 official FPL gameweeks from the FPL bootstrap API.
- Added idempotent gameweek importing so repeated FPL data updates do not create duplicate gameweeks.

- Added the `player_gameweek_snapshots` database table for preserving player bootstrap state by gameweek.
- Added historical player snapshot identity linking each snapshot to its player, team and gameweek.
- Added persistence of current player state so later FPL bootstrap updates do not destroy the state that existed during an earlier gameweek.
- Added current-gameweek player snapshot importing to the FPL data updater.
- Added complete current-player snapshot coverage during normal FPL data updates.
- Added idempotent player snapshot persistence so repeated updater runs update the existing player/gameweek snapshot rather than creating duplicates.
- Added snapshot foreign-key protection for players, teams and gameweeks.
- Added historical snapshot support for player identity, team, position and core FPL state.

- Added the `player_fixture_history` database table for preserving official per-fixture FPL player performance.
- Added historical fixture records linked to:
  - local gameweek
  - local player
  - official FPL player ID
  - local fixture
  - official FPL fixture ID
  - historical team
  - historical opponent
- Added player/fixture uniqueness protection so each player can have only one stored record for a particular fixture.
- Added database structure that safely supports normal, blank and double gameweeks by using player + fixture rather than player + gameweek as the historical uniqueness contract.
- Added historical home/away state.
- Added historical total points.
- Added historical minutes and starts.
- Added historical goals and assists.
- Added historical expected goals.
- Added historical expected assists.
- Added historical expected goal involvements.
- Added historical clean sheets and goals conceded.
- Added historical expected goals conceded.
- Added saves and penalties saved.
- Added clearances, blocks and interceptions.
- Added recoveries and tackles.
- Added defensive contribution.
- Added own goals and penalties missed.
- Added yellow and red cards.
- Added bonus and BPS.
- Added influence, creativity, threat and ICT Index.
- Added historical player price.
- Added historical selection and transfer data.

- Added `PlayerFixtureHistoryRepository.php` for persistent player fixture-history access.
- Added fixture-history upsert support.
- Added fixture-history lookup by player and fixture.
- Added fixture-history count support.
- Added safe parameter handling for repository queries.

- Added live FPL player-summary API support.
- Added retrieval of official player `element-summary` data.
- Added access to:
  - upcoming player fixtures
  - current-season fixture history
  - previous-season history
- Added validation preventing invalid zero or negative FPL player IDs from being requested.
- Added support for the current FPL player-summary response structure.

- Added `updatePlayerFixtureHistory.php` as the dedicated historical fixture-performance importer.
- Added live per-player fixture-history importing from the official FPL player-summary endpoint.
- Added local gameweek resolution from official FPL history records.
- Added local fixture resolution from official FPL fixture IDs.
- Added historical team resolution from the actual fixture rather than relying on the player's current team.
- Added historical opponent resolution from the actual fixture.
- Added validation against the official FPL opponent identity.
- Added historical home/away resolution.
- Added FPL price normalisation from tenths to pounds.
- Added fixture-history upserting so historical imports can be safely rerun.
- Added preservation of legitimate zero-minute history records as known historical evidence rather than treating them as missing data.

- Added controlled fixture-history batch importing using `limit` and `offset`.
- Added resumable fixture-history importing so interrupted imports can continue from a later player offset.
- Added full-player-pool import mode.
- Added eligible-player counting before historical imports.
- Added per-player progress output.
- Added import mode diagnostics.
- Added API request throttling between player-summary requests.
- Added request throttling after failed API calls before processing the next player.
- Added import summaries covering:
  - players selected
  - players processed
  - players failed
  - history rows found
  - history rows imported
  - history rows skipped
  - total stored history rows
  - import mode

### Changed
- Extended the database from current-state FPL storage into a historical gameweek-aware data model.
- Changed the FPL data update pipeline so official gameweek metadata is persisted before current player state is processed.
- Extended the main updater to preserve current-gameweek player snapshots.
- Preserved current `players` records as the application's live player state while introducing separate historical snapshot storage.
- Separated player fixture-history importing from the normal bootstrap updater because fixture history requires individual FPL player-summary requests.
- Kept the historical fixture importer independently resumable rather than requiring hundreds of live player-summary requests during every standard FPL data update.
- Changed historical player performance storage to use the actual fixture team context rather than assuming the player's current team is historically correct.
- Changed historical uniqueness to player + fixture so Double Gameweeks can safely contain multiple records for the same player and gameweek.
- Preserved zero-minute fixture records because an official FPL history row containing zero minutes represents known evidence rather than unavailable data.
- Added safe API throttling to reduce back-to-back requests when importing large player pools.
- Retained manual batch controls alongside full import mode for diagnostics and recovery.

- Updated Team Intelligence form rendering to support structured recent-form records produced by `TeamPerformance`.
- Changed Team Intelligence page form formatting to extract the `result` value from structured match records.
- Preserved backwards compatibility with legacy string-based recent-form values.
- Updated both the league-wide Team Intelligence page and individual Team Intelligence profile page to safely render structured W/D/L form data.
- Changed the Team Intelligence profile regression test so fixture classifications are validated against supported classification states rather than permanently requiring Arsenal to have an `Excellent` fixture classification.
- Made the Team Intelligence profile test data-aware so legitimate fixture-rating changes after completed Premier League matches do not create false regression failures.

### Fixed
- Fixed the historical database schema being incomplete compared with the live development database.
- Fixed missing persistent gameweek history that would otherwise be lost as the FPL API moves from one gameweek to the next.
- Fixed current player bootstrap updates having no historical snapshot layer.
- Fixed the inability to reconstruct a player's state from an earlier gameweek after current FPL data changes.
- Fixed the lack of persistent official per-fixture player performance history.
- Fixed historical player records potentially using a player's current team instead of the team represented by the historical fixture.
- Fixed historical opponent resolution by deriving team/opponent context from the stored fixture and validating it against FPL history data.
- Fixed fixture-history repository parameter binding that could produce `SQLSTATE[HY093]: Invalid parameter number`.
- Fixed fixture-history reruns potentially requiring manual duplicate management by introducing idempotent player/fixture upserts.
- Prevented repeated gameweek imports from creating duplicate gameweek records.
- Prevented repeated current-gameweek snapshot imports from creating duplicate player snapshots.
- Prevented repeated fixture-history imports from creating duplicate player/fixture history rows.
- Prevented blank and double gameweeks from being incorrectly constrained by player/gameweek uniqueness.
- Prevented legitimate zero-minute player appearances from being interpreted as missing historical evidence.
- Prevented invalid FPL player IDs from reaching the live player-summary API.
- Prevented failed live API requests from bypassing request throttling before the next player request.

- Fixed `Array to string conversion` PHP warnings on Team Intelligence pages after completed Premier League form data became available.
- Fixed `teams.php` attempting to directly implode structured recent-form arrays.
- Fixed `team.php` attempting to directly implode structured recent-form arrays.
- Fixed Team Intelligence page rendering that worked with empty preseason form data but produced warnings once GW1 performance records existed.
- Fixed a stale Team Intelligence profile regression assertion that permanently expected Arsenal's fixture classification to be `Excellent`.
- Updated fixture-classification regression coverage to accept the current supported classification while still validating the rendered CSS class and label.
- Confirmed Arsenal's current post-GW1 fixture classification renders correctly as `Good`.

### Historical Data Validation
- Imported all 38 official FPL gameweeks.
- Confirmed official gameweek IDs span GW1 through GW38.
- Confirmed gameweek deadlines are stored chronologically.
- Confirmed current and next gameweek state is imported correctly.
- Confirmed the absence of a previous gameweek is safely handled when the FPL API does not currently expose one.
- Confirmed repeated gameweek imports preserve exactly 38 rows.

- Imported 610 current-gameweek player snapshots.
- Confirmed snapshot coverage matches the complete current player dataset.
- Confirmed all 610 player snapshots remain unique after repeated updater runs.
- Confirmed snapshot player, team and gameweek foreign-key consistency.
- Confirmed snapshot FPL player identities match current player records.
- Confirmed snapshot team and position values match the captured current player state.

- Imported complete GW1 player fixture history for all 610 current players.
- Confirmed GW1 contains 610 stored fixture-history rows.
- Confirmed GW1 history represents 610 unique players.
- Confirmed GW1 history represents all 10 Premier League fixtures.
- Confirmed zero duplicate player/fixture history rows.
- Confirmed repeated fixture-history batches update existing rows rather than creating duplicates.
- Confirmed fixture-history importing works across small 5-player, 25-player and 100-player controlled batches.
- Confirmed all remaining GW1 player history can be imported through resumable batch processing.
- Confirmed real playing and non-playing records are both preserved, including zero-minute players.
- Confirmed historical player price values are correctly normalised and persisted.

### Testing
- Added `HistoricalSchemaTest.php` for historical database schema regression coverage.
- Added validation of historical table existence and structure.
- Added validation of historical foreign-key relationships.
- Added validation of historical uniqueness contracts.

- Added `GameweekIntegrationTest.php`.
- Added validation that exactly 38 FPL gameweeks are stored.
- Added validation that stored FPL gameweek IDs are unique.
- Added validation that stored gameweeks span GW1 to GW38.
- Added validation of gameweek names and deadlines.
- Added validation of previous, current and next state flags.
- Added validation that gameweek state flags are mutually exclusive.
- Added validation of finished and data-checked state.
- Added repository state-lookup validation.
- Added chronological deadline validation.
- Added database uniqueness validation.
- Added GW1 and GW38 identity lookup validation.
- Added repeated-import idempotency validation.

- Added player gameweek snapshot integration coverage.
- Added validation that the current FPL gameweek can be resolved locally.
- Added validation that snapshot count matches the current player count.
- Added snapshot uniqueness validation.
- Added snapshot foreign-key consistency validation.
- Added FPL player identity consistency validation.
- Added snapshot team and position consistency validation.
- Added core snapshot data-integrity validation.
- Added current-state parity validation.
- Added snapshot gameweek-distribution validation.
- Added repeated snapshot-import idempotency validation.

- Added `FPLApiPlayerSummaryTest.php`.
- Added live real-player player-summary API validation.
- Added response contract validation for `fixtures`, `history` and `history_past`.
- Added upcoming fixture contract validation.
- Added current-season history contract validation.
- Added previous-season history contract validation.
- Added invalid-player protection coverage.
- Added live player-summary performance coverage.

- Added `PlayerFixtureHistoryRepositoryTest.php`.
- Added fixture-history repository persistence coverage.
- Added retrieval coverage.
- Added uniqueness and upsert coverage.
- Added fixture-history count validation.

- Added single-player real fixture-history import testing using Raya.
- Added live current-season history validation.
- Added local gameweek resolution validation.
- Added local fixture resolution validation.
- Added historical team and opponent resolution validation.
- Added official FPL opponent cross-validation.
- Added historical record construction validation.
- Added price-normalisation validation.
- Added real fixture-history persistence validation.
- Added stored-data validation.
- Added live idempotency validation.

- Added `PlayerFixtureHistoryImportIntegrationTest.php`.
- Added validation that imported fixture history exists.
- Added gameweek-distribution validation.
- Added player/fixture uniqueness validation.
- Added player, gameweek, fixture, team and opponent foreign-key validation.
- Added FPL player and fixture identity validation.
- Added gameweek/fixture consistency validation.
- Added historical home/away team-context validation.
- Added core historical data-integrity validation.
- Added explicit normal, blank and Double Gameweek structural safety coverage.
- Added validation that the database uniqueness contract uses player + fixture.
- Added current import coverage validation without hard-coding a permanent GW1 player count.
- Added zero-minute historical record coverage.
- Added fixture-history database performance validation.

- Updated `TeamsPageTest.php` coverage for live completed-match Team Intelligence rendering.
- Updated `TeamIntelligenceProfilePageTest.php` to validate supported fixture classifications rather than a stale fixed classification.
- Added regression protection against PHP warnings caused by structured recent-form records.
- Confirmed Arsenal's current rendered fixture classification is `Good`.
- Verified `PlayerFixtureHistoryImportIntegrationTest.php` passes successfully.
- Verified `TeamsPageTest.php` passes successfully after the post-GW1 rendering fix.
- Verified `TeamIntelligenceProfilePageTest.php` passes successfully after the post-GW1 rendering fix.
- Verified the complete `RunAllTests.php` project regression suite passes successfully.

## [0.26.0] - Effective Confidence & Live Gameweek Intelligence

### Added
- Added Effective Confidence as a dedicated decision-reliability signal for live and early-season FPL intelligence.
- Added `effective_confidence` to the Player Performance model.
- Added team available-minutes awareness so player participation can be evaluated against the amount of Premier League football their team has actually completed.
- Added Effective Confidence calculation using:
  - 40% Sample Confidence
  - 60% current team participation rate
- Added explicit distinction between statistical sample maturity and current participation reliability.
- Added `team_available_minutes` to Player Intelligence summaries.
- Added `participation_rate` to Player Intelligence summaries.
- Added `effective_confidence` to Player Intelligence summaries.
- Added Effective Confidence propagation through `PlayerIntelligenceEngine`.
- Added Effective Confidence metadata propagation through `PlayerStrengthModel`.
- Added current-gameweek available-minutes support to `PlayerIntelligenceService`.
- Added completed-fixture minute aggregation by Premier League team.
- Added support for `finished_provisional` fixtures when determining completed current-gameweek evidence.
- Added Effective Confidence support to Gameweek decision-making.
- Added Effective Confidence risk handling to Gameweek Starting XI selection.
- Added Effective Confidence support to Captain Intelligence.
- Added confidence-adjusted attacking threat inputs to Captain Intelligence.
- Added Effective Confidence support to Wildcard reliability evaluation.
- Added reliability-aware goalkeeper selection to Wildcard optimisation.
- Added reliability-aware Wildcard bench evaluation.
- Added FPL-style bench ordering across Gameweek and Wildcard recommendations:
  - backup goalkeeper in bench slot one
  - first outfield substitute in bench slot two
  - second outfield substitute in bench slot three
  - third outfield substitute in bench slot four
- Added dedicated Effective Confidence regression and integration coverage.

### Changed
- Changed early-season confidence handling so decision reliability is no longer represented solely by historical/current-season Sample Confidence.
- Changed Player Intelligence to distinguish between:
  - Sample Confidence for statistical performance maturity
  - Effective Confidence for current FPL decision reliability
- Changed Player Performance so Sample Confidence remains responsible for regression of performance ratings toward neutral values.
- Changed Player Strength to continue consuming Sample Confidence-adjusted performance ratings rather than Effective Confidence-adjusted ratings.
- Changed Effective Confidence to act as a participation and decision-reliability signal rather than redefining underlying Player Strength.
- Changed live player participation evaluation to compare player minutes against the minutes their team has actually had available.
- Changed current-gameweek fixture handling so provisionally finished Premier League fixtures can contribute completed-match evidence before the gameweek is fully complete.
- Changed Gameweek intelligence so players with stronger current participation evidence can receive greater decision confidence without artificially increasing their underlying performance strength.
- Changed Captain Intelligence to use Effective Confidence when available for decision-risk adjustment.
- Changed Captain Intelligence attacking inputs to prefer confidence-adjusted performance ratings while retaining safe raw-rating fallbacks.
- Changed Wildcard optimisation to use reliability-aware confidence for goalkeeper and bench decisions.
- Changed Wildcard goalkeeper reliability requirements to operate on decision-relevant confidence rather than raw early-season Sample Confidence alone.
- Changed Wildcard bench reliability penalties to use the appropriate reliability confidence signal.
- Changed Wildcard and Gameweek bench presentation to match the FPL visual convention with the backup goalkeeper displayed first.
- Preserved the existing ranking of the three outfield substitutes while shifting their displayed bench positions to two through four.
- Improved early-season handling of players who have played all or most of their team's available Premier League minutes.
- Improved handling of teams that have not yet completed a current-gameweek fixture.
- Improved live-season test stability by replacing brittle assumptions about specific current opponents, fixture ratings and partial-gameweek state with invariant behavioural checks.
- Improved Player Strength transparency by exposing both Sample Confidence and Effective Confidence metadata without allowing Effective Confidence to alter the Strength calculation directly.

### Fixed
- Fixed early-season Sample Confidence being too weak on its own to represent the reliability of players who had played all available team minutes.
- Fixed Effective Confidence initially being applied directly to adjusted performance ratings, which caused early-season participation evidence to influence Player Strength too aggressively.
- Restored Sample Confidence as the statistical regression signal used by Player Strength.
- Fixed premium players receiving excessively distorted early-season Strength ratings when Effective Confidence was incorrectly used for performance regression.
- Fixed live/current-gameweek intelligence failing to distinguish between a player with limited minutes because their team had not yet played and a player with limited minutes despite their team having completed matches.
- Fixed provisionally completed fixtures being excluded from available-minute calculations.
- Fixed Wildcard goalkeeper selection failing because raw early-season Sample Confidence could not satisfy meaningful starter-reliability requirements.
- Fixed Wildcard reliability output using confidence semantics that did not match the optimiser's decision model.
- Fixed Gameweek and Wildcard bench displays placing the backup goalkeeper in the fourth bench slot instead of the first visual bench slot.
- Fixed stale regression expectations that required the backup goalkeeper to occupy bench position four.
- Fixed duplicate Team Fixture Rating calculation in `PlayerIntelligenceService`.
- Removed temporary premium-player and early-season diagnostic code introduced during Effective Confidence investigation.
- Removed brittle regression assumptions requiring the current gameweek to remain partially completed.
- Removed dead duplicate Team Intelligence score extraction logic from `TeamIntelligenceProfilePageTest.php`.
- Corrected Player Performance documentation so Sample Confidence and Effective Confidence responsibilities match the final model architecture.

### Testing
- Added `EffectiveConfidenceTest.php` for dedicated Effective Confidence unit coverage.
- Added validation of the Effective Confidence weighting model using Sample Confidence and current team participation.
- Added validation of full-participation Effective Confidence behaviour.
- Added validation of partial-participation Effective Confidence behaviour.
- Added validation of zero-available-minute and no-evidence behaviour.
- Added `EffectiveConfidenceServiceIntegrationTest.php` for end-to-end Player Intelligence integration coverage.
- Added validation of team available-minute aggregation from completed Premier League fixtures.
- Added validation that participation rate remains consistent with player minutes and team available minutes.
- Added validation that service-level Effective Confidence matches the underlying Effective Confidence model.
- Added `CaptainEffectiveConfidenceTest.php` for Captain Intelligence confidence regression coverage.
- Added validation that Captain Intelligence prefers Effective Confidence when available.
- Added validation of Captain confidence modifiers across low, medium and high reliability conditions.
- Added validation of safe Captain Intelligence fallback behaviour when Effective Confidence is unavailable.
- Added `CaptainConfidenceAdjustedThreatTest.php` for confidence-adjusted attacking-threat coverage.
- Added validation that Captain Intelligence prefers confidence-adjusted attacking performance ratings.
- Added validation that raw attacking ratings remain available as safe fallbacks.
- Added `GameweekEffectiveConfidenceRiskTest.php` for Gameweek decision-risk regression coverage.
- Added validation that Effective Confidence influences Gameweek risk without redefining underlying Player Strength.
- Added validation of explicit null Effective Confidence behaviour.
- Updated `GameweekStartingXITest.php` for FPL-style goalkeeper-first bench ordering.
- Updated `GameweekStartingXIRealDataTest.php` for goalkeeper-first bench ordering.
- Updated `PlayerIntelligenceServiceTest.php` for Effective Confidence and Gameweek bench-order integration.
- Updated `WildcardSquadStructureTest.php` for goalkeeper-first Wildcard bench ordering.
- Updated `WildcardOptimizerRegressionTest.php` for reliability-aware Wildcard optimisation.
- Updated `WildcardOptimizerRealDataTest.php` for Effective Confidence integration.
- Updated `WildcardPageTest.php` for the current Wildcard reliability and Premium Core output.
- Updated `FixtureRepositoryTest.php` for live/current-gameweek fixture completion behaviour.
- Updated Captain Intelligence real-data and regression tests for Effective Confidence behaviour.
- Updated Team Intelligence profile page regression coverage to avoid brittle live-fixture assumptions.
- Removed temporary diagnostic-only test coverage after the final Effective Confidence architecture was validated.
- Verified all dedicated Effective Confidence, Captain, Gameweek, Wildcard and Player Intelligence tests pass.
- Verified the complete `RunAllTests.php` regression suite passes successfully.
- Verified 82 of 82 test files pass.
- Verified 2,971 assertions pass with 0 failures and 0 errors.

## [0.25.0] - Position-Aware Fixture Intelligence

### Added
- Added Position-Aware Fixture Intelligence for evaluating immediate fixture opportunity according to a player's FPL position.
- Added `FixtureIntelligence::calculatePositionAwareOpportunity()` as the dedicated position-aware fixture scoring model.
- Added position-specific opponent analysis so:
  - goalkeepers and defenders evaluate the opponent's Attack Rating
  - midfielders and forwards evaluate the opponent's Defence Rating
- Added conversion of opponent strength into player opportunity so weaker opponent performance produces a stronger player-facing fixture opportunity.
- Added conservative position-aware fixture blending using:
  - 75% existing immediate fixture opportunity
  - 25% position-specific opponent performance opportunity
- Added explicit neutral behaviour where an opponent Attack or Defence Rating of 50 preserves a neutral position-specific contribution.
- Added clean no-evidence fallback behaviour so the existing immediate fixture opportunity is preserved when the required opponent Attack or Defence Rating is unavailable.
- Added safe handling of unsupported player positions without introducing artificial fixture adjustments.
- Added 0-100 bounds to all position-aware fixture outputs.
- Added `position_aware_fixture_rating` to Player Intelligence summaries.
- Added `base_next_fixture_rating` to preserve the original immediate fixture opportunity for diagnostics and transparency.
- Added next-opponent context to Player Intelligence summaries.
- Added `next_opponent_team_id` to Player Intelligence summaries.
- Added `next_opponent_attack_rating` to Player Intelligence summaries.
- Added `next_opponent_defence_rating` to Player Intelligence summaries.
- Added next-opponent lookup support for every Premier League team.
- Added Team Intelligence Attack and Defence Rating lookup support within the Player Intelligence pipeline.

### Changed
- Extended Fixture Intelligence from general opponent-strength analysis into position-specific FPL fixture analysis.
- Changed immediate player fixture evaluation so the same fixture can produce different opportunity depending on player position once opponent performance evidence exists.
- Changed goalkeeper and defender immediate fixture evaluation to account for the opponent's attacking performance.
- Changed midfielder and forward immediate fixture evaluation to account for the opponent's defensive performance.
- Changed `next_fixture_rating` to represent the authoritative position-aware immediate fixture opportunity.
- Preserved the previous immediate fixture value separately as `base_next_fixture_rating`.
- Integrated Team Attack & Defence Intelligence from v0.24.0 into player-level fixture evaluation.
- Reused existing Team Intelligence Attack and Defence outputs rather than duplicating TeamPerformance calculations inside Player Intelligence.
- Preserved the existing Fixture Intelligence model as the dominant component of immediate fixture evaluation.
- Improved early-season stability by limiting position-specific performance evidence to 25% of the adjusted fixture opportunity.
- Improved Player Intelligence transparency by exposing both the base fixture opportunity and the position-aware result.
- Extended existing downstream intelligence systems that consume `next_fixture_rating` so position-aware fixture information can influence immediate FPL decisions.
- Preserved existing player fixture behaviour when no completed Premier League Attack or Defence evidence is available.

### Fixed
- Fixed immediate fixture evaluation treating goalkeepers, defenders, midfielders and forwards identically despite facing different FPL-relevant opponent threats.
- Fixed opponent general strength being the only team-level context available to immediate player fixture evaluation.
- Fixed Team Attack and Defence Intelligence existing independently of player-facing fixture decisions.
- Prevented unavailable early-season Attack and Defence data from generating artificial position-aware fixture adjustments.
- Prevented extreme position-specific opponent ratings from pushing fixture opportunity outside the valid 0-100 range.
- Fixed Position-Aware Fixture Intelligence integration variables initially being calculated outside the individual player-summary loop.
- Restored correct per-player calculation of next opponent, opponent Attack Rating, opponent Defence Rating and position-aware fixture opportunity.

### Testing
- Added `PositionAwareFixtureIntelligenceTest.php` for dedicated Position-Aware Fixture Intelligence regression coverage.
- Added validation that Fixture Intelligence exposes `calculatePositionAwareOpportunity()`.
- Added validation of position-aware fixture calculation for:
  - goalkeepers
  - defenders
  - midfielders
  - forwards
- Added validation that all position-aware fixture outputs remain between 0 and 100.
- Added validation that defender fixture opportunity improves against a weak opponent attack.
- Added validation that defender fixture opportunity worsens against a strong opponent attack.
- Added validation that defensive fixture ratings remain correctly ordered by opponent Attack Rating.
- Added validation that goalkeeper opportunity is higher against a weaker opponent attack.
- Added validation that midfielder fixture opportunity improves against a weak opponent defence.
- Added validation that midfielder fixture opportunity worsens against a strong opponent defence.
- Added validation that attacking fixture ratings remain correctly ordered by opponent Defence Rating.
- Added validation that forward opportunity is higher against a weaker opponent defence.
- Added validation of no-evidence fallback behaviour for all four FPL positions.
- Added validation that missing opponent Attack or Defence evidence preserves the existing base fixture opportunity.
- Added extreme-value regression coverage for position-aware fixture score bounds.
- Added validation that invalid player positions are safely handled.
- Added Player Intelligence summary integration coverage for `position_aware_fixture_rating`.
- Added validation that all available position-aware fixture ratings remain within the 0-100 scale.
- Added integration coverage between position-aware fixture output and existing immediate `next_fixture_rating`.
- Added current no-evidence regression coverage so preseason behaviour remains stable before completed Premier League performance data exists.
- Added Position-Aware Fixture Intelligence performance regression coverage.
- Verified `PositionAwareFixtureIntelligenceTest.php` passes with 0 failures.
- Updated `SquadPageTest.php` to reflect the current development preview squad used during regression testing.
- Verified `SquadPageTest.php` passes with Position-Aware Fixture Intelligence integrated.
- Verified the complete `RunAllTests.php` project regression suite passes successfully.

## [0.24.0] - Team Attack & Defence Intelligence

### Added
- Added first-class Team Attack and Defence Intelligence to the Team Intelligence system.
- Added `attack_rating` to league-wide Team Intelligence summaries.
- Added `defence_rating` to league-wide Team Intelligence summaries.
- Added Attack Rating and Defence Rating propagation into individual Team Intelligence profiles.
- Added performance-derived Attack Rating using completed Premier League goals scored per game.
- Added performance-derived Defence Rating using completed Premier League goals conceded per game.
- Added 0-100 Attack Rating scale where stronger goalscoring performance produces a higher rating.
- Added 0-100 Defence Rating scale where stronger defensive performance and fewer goals conceded produce a higher rating.
- Added explicit no-evidence behaviour so Attack and Defence Ratings remain unavailable until a team has completed a Premier League fixture.
- Added Attack and Defence columns to the league-wide Team Intelligence rankings.
- Added Attack Rating and Defence Rating cards to the Premier League Performance section of individual team profiles.
- Added explanatory context showing that Attack Rating is derived from goals scored per game.
- Added explanatory context showing that Defence Rating is derived from goals conceded per game.
- Added Team Intelligence profile explanation clarifying that Attack and Defence Ratings use completed Premier League matches only.
- Added unavailable-state presentation using `—` before sufficient Premier League performance evidence exists.

### Changed
- Promoted the existing `TeamPerformance` attack and defence calculations into first-class Team Intelligence outputs.
- Reused the existing `calculateAttackRating()` and `calculateDefenceRating()` model rather than introducing duplicate attack and defence scoring logic.
- Extended `getAllTeamIntelligenceSummaries()` with performance-derived Attack and Defence Ratings.
- Extended `getTeamIntelligenceProfile()` with Attack and Defence Rating data.
- Improved the league-wide Team Intelligence rankings so overall team strength can be viewed alongside attacking and defensive performance.
- Improved individual Team Intelligence profiles by separating baseline/current strength from performance-derived attacking and defensive evidence.
- Changed the Premier League Performance profile layout from six desktop columns to four columns so the expanded eight-card section remains balanced and readable.
- Preserved `null` Attack and Defence Ratings when no completed Premier League match evidence exists rather than fabricating preseason performance values.
- Improved Team Intelligence readiness for live-season data so Attack and Defence Ratings automatically become numeric once completed Premier League results are available.

### Fixed
- Fixed Team Attack and Defence ratings being calculated within `TeamPerformance` but not exposed through Team Intelligence summaries.
- Fixed individual Team Intelligence profiles not receiving existing Attack and Defence Rating data.
- Fixed the Team Intelligence UI having no visibility of attacking and defensive performance despite the underlying calculations already existing.
- Prevented preseason and no-match states from being represented by misleading artificial Attack or Defence Ratings.
- Prevented duplication of existing TeamPerformance attack and defence scoring logic by integrating the established model instead of creating a second intelligence engine.

### Testing
- Added `TeamAttackDefenceIntelligenceTest.php` for dedicated Team Attack and Defence Intelligence regression coverage.
- Added validation that `TeamPerformance` exposes `calculateAttackRating()`.
- Added validation that `TeamPerformance` exposes `calculateDefenceRating()`.
- Added validation of Attack Rating against elite, average and poor attacking performance.
- Added validation that three goals scored per game produces the maximum Attack Rating.
- Added validation that one and a half goals scored per game produces a neutral Attack Rating.
- Added validation that zero goals scored produces the minimum Attack Rating.
- Added validation that stronger attacking performance produces a higher Attack Rating.
- Added validation of Defence Rating against elite, average and poor defensive performance.
- Added validation that zero goals conceded produces the maximum Defence Rating.
- Added validation that one and a half goals conceded per game produces a neutral Defence Rating.
- Added validation that three goals conceded per game produces the minimum Defence Rating.
- Added validation that fewer goals conceded produces a higher Defence Rating.
- Added validation that Attack Ratings remain bounded between 0 and 100.
- Added validation that Defence Ratings remain bounded between 0 and 100.
- Added validation that Attack Rating returns `null` when no matches have been played.
- Added validation that Defence Rating returns `null` when no matches have been played.
- Added synthetic completed-fixture analysis covering matches played, goals scored and goals conceded.
- Added validation that completed fixture evidence produces numeric Attack and Defence Ratings.
- Added validation of Attack and Defence Rating propagation into all 20 Team Intelligence summaries.
- Added validation that available Team Intelligence Attack and Defence Ratings remain between 0 and 100.
- Added validation that every team exposes either a numeric rating or a valid unavailable state.
- Added validation of Attack and Defence Rating propagation into individual Team Intelligence profiles.
- Added profile-to-summary consistency validation for Attack and Defence Ratings.
- Added Team Attack and Defence integration performance regression coverage.
- Extended `TeamsPageTest.php` with Attack and Defence table-header coverage.
- Added validation that all 20 ranked teams expose Attack Rating output.
- Added validation that all 20 ranked teams expose Defence Rating output.
- Added validation that rendered league-wide Attack Ratings are either numeric values between 0 and 100 or unavailable.
- Added validation that rendered league-wide Defence Ratings are either numeric values between 0 and 100 or unavailable.
- Extended `TeamIntelligenceProfilePageTest.php` with Attack and Defence Rating UI coverage.
- Added validation of Attack and Defence Rating explanatory content.
- Added validation of goals-scored and goals-conceded explanatory labels.
- Added validation that individual profile Attack and Defence Rating values are rendered.
- Added validation that profile Attack and Defence Ratings are either numeric values between 0 and 100 or unavailable.
- Verified `TeamAttackDefenceIntelligenceTest.php` passes with 0 failures.
- Verified `TeamsPageTest.php` passes with Team Attack and Defence Intelligence integration.
- Verified `TeamIntelligenceProfilePageTest.php` passes with Team Attack and Defence Intelligence integration.
- Verified the complete `RunAllTests.php` project regression suite passes successfully.

## [0.23.0] - Team Intelligence Profiles

### Added
- Added detailed Team Intelligence profiles for individual Premier League teams.
- Added `getTeamIntelligenceProfile()` to `PlayerIntelligenceService` as the dedicated team-profile service.
- Added complete team-profile output containing:
  - team identity
  - Premier League Team Intelligence ranking
  - Team Intelligence Score
  - Team Intelligence classification
  - overall team strength
  - home strength
  - away strength
  - fixture intelligence
  - current form
  - current FPL players
- Added league-ranking context so individual team profiles preserve their position within the complete Team Intelligence rankings.
- Added fixture-profile output containing:
  - overall fixture rating
  - fixture classification
  - fixture trend
  - next-five fixture rating
  - next-six fixture rating
  - next-eight fixture rating
  - next-ten fixture rating
  - best fixture run
  - worst fixture run
  - upcoming fixture records
- Added opponent identity to upcoming team fixtures.
- Added opponent name and short-name data to team-profile fixture output.
- Added Home/Away venue context to upcoming fixture records.
- Added current Premier League form output containing:
  - recent form
  - matches played
  - wins
  - draws
  - losses
  - points
  - goals scored
  - goals conceded
  - goal difference
- Added current FPL player collection to each Team Intelligence profile.
- Added Player Intelligence data to team-profile player output including:
  - Player Intelligence Score
  - strength rating
  - value rating
  - fixture rating
  - immediate next-fixture rating
  - availability
  - sample confidence
  - assessment verdict
- Added automatic ordering of team players by Player Intelligence Score.
- Added `public/team.php` as the dedicated Team Intelligence profile dashboard.
- Added Team Intelligence profile hero displaying:
  - league rank
  - team identity
  - Team Intelligence Score
  - Team Intelligence classification
- Added Current Strength Profile displaying overall, home and away team strength.
- Added detailed Fixture Intelligence section.
- Added upcoming fixture cards displaying opponent, venue and gameweek.
- Added Premier League Performance section for current team form.
- Added Current Player Intelligence table for all current FPL players belonging to the selected team.
- Added direct navigation from Team Intelligence profiles to existing Player Intelligence profiles.
- Added navigation from the league-wide Team Intelligence rankings into individual team profiles.
- Added controlled invalid-team profile state.
- Added responsive Team Intelligence profile styling.

### Changed
- Extended Team Intelligence from a league-wide ranking system into a navigable league-to-team-to-player intelligence structure.
- Reused the existing ranked Team Intelligence summaries as the source of truth for individual team profiles.
- Improved Team Intelligence architecture so profile generation does not duplicate the existing team scoring model.
- Moved upcoming-fixture opponent resolution into the service layer rather than resolving opponents within the presentation layer.
- Improved upcoming fixture data so rendered pages receive presentation-ready opponent and venue information.
- Removed repeated calls to `getAllTeamIntelligenceSummaries()` while rendering individual fixture cards.
- Improved Team Intelligence profile fixture presentation by moving the fixture classification into a compact heading badge.
- Improved Fixture Intelligence layout so Next 5, Next 6, Next 8 and Next 10 ratings have consistent visual hierarchy.
- Improved team ranking navigation by making team names direct links to their Team Intelligence profiles.
- Improved accessibility of team-profile navigation with standard keyboard-accessible links and visible focus handling.
- Improved Team Intelligence profile section spacing and responsive behaviour.
- Preserved current Player Intelligence as the source of truth for player-level scores displayed within team profiles.

### Fixed
- Fixed inefficient Team Intelligence profile rendering that initially rebuilt the complete 20-team Intelligence collection for every upcoming fixture.
- Fixed upcoming fixture opponent resolution being performed inside `team.php` instead of the service layer.
- Fixed fixture classification badge styling that initially caused the classification to render as an oversized empty container.
- Fixed fixture summary layout after moving the fixture classification outside the numerical rating grid.
- Fixed Team Intelligence profile page regression checks that initially failed because rendered HTML whitespace differed from literal test strings.
- Fixed Arsenal title and short-name page checks to tolerate valid rendered HTML whitespace.
- Fixed fixture classification page checks to tolerate valid rendered HTML whitespace.
- Fixed Next 5, Next 6, Next 8 and Next 10 rating checks to validate rendered markup correctly.
- Fixed Home/Away fixture venue checks to tolerate valid rendered HTML whitespace.

### Testing
- Added `TeamIntelligenceProfileTest.php` for dedicated Team Intelligence profile service coverage.
- Added validation that `PlayerIntelligenceService` exposes `getTeamIntelligenceProfile()`.
- Added real-team profile validation against the current Premier League dataset.
- Added validation of complete Team Intelligence profile result structure.
- Added validation of team identity including local team ID, FPL team ID, team name and short name.
- Added validation of league ranking and Team Intelligence Score.
- Added validation that Team Intelligence Scores remain between 0 and 100.
- Added validation of supported Team Intelligence classifications.
- Added validation of overall, home and away team strength.
- Added validation that team strength values remain between 0 and 100.
- Added validation of fixture rating, fixture classification and fixture trend.
- Added validation of upcoming fixture records.
- Added validation of current team-form structure and numeric form metrics.
- Added validation that current FPL players are returned for the selected team.
- Added validation that all returned players belong to the requested team.
- Added validation of player IDs and player names.
- Added consistency checks between individual Team Intelligence profiles and the existing ranked Team Intelligence summaries.
- Added invalid-team profile handling coverage.
- Added Team Intelligence profile performance regression coverage.
- Verified `TeamIntelligenceProfileTest.php` passes all 66 checks with 0 failures.
- Added `TeamIntelligenceProfilePageTest.php` for end-to-end Team Intelligence profile page regression coverage.
- Added HTTP validation of valid Team Intelligence profile requests.
- Added validation of the shared application shell and active Teams navigation.
- Added validation of Team Intelligence profile identity and league-ranking output.
- Added validation of the Team Intelligence hero and classification badge.
- Added validation of the five Current Strength Profile summary cards.
- Added validation of Fixture Intelligence summary output.
- Added validation that exactly ten upcoming fixture cards are rendered for the current Arsenal diagnostic profile.
- Added validation of current fixture opponents, Home/Away venue information and gameweek numbers.
- Added validation of the Premier League Performance section.
- Added validation of the Current Player Intelligence table.
- Added validation of current Arsenal Player Intelligence profile links.
- Added validation of Player Intelligence table structure and navigation.
- Added invalid Team Intelligence profile page coverage.
- Added rendered-page detection for PHP fatal errors, parse errors, uncaught errors, warnings, notices and undefined variables.
- Added valid and invalid Team Intelligence profile page performance regression coverage.
- Verified `TeamIntelligenceProfilePageTest.php` passes with 0 failures.

## [0.22.0] - Team Intelligence

### Added
- Added squad-independent Team Intelligence for evaluating and comparing all 20 Premier League teams.
- Added `getAllTeamIntelligenceSummaries()` to Player Intelligence Service.
- Added Team Intelligence summaries combining existing team strength and fixture intelligence.
- Added Team Intelligence Score for ranking Premier League teams using:
  - overall team strength
  - home strength
  - away strength
  - upcoming fixture opportunity
  - performance-adjusted team strength
- Added Team Intelligence classifications:
  - Elite
  - Strong
  - Average
  - Weak
  - Poor
- Added fixture classifications for identifying short-term fixture opportunity.
- Added fixture trend information to Team Intelligence summaries.
- Added complete 20-team Team Intelligence ranking ordered by Intelligence Score.
- Added `public/teams.php` as the dedicated Team Intelligence dashboard.
- Added Team Intelligence Summary displaying:
  - teams analysed
  - Elite team count
  - Strong team count
  - average Team Intelligence Score
  - average fixture rating
- Added Premier League Team Intelligence ranking table.
- Added compact league-style comparison of all 20 Premier League teams.
- Added ranking positions for every Premier League team.
- Added team identity and short-name information to the Team Intelligence table.
- Added Team Intelligence Score and classification badges.
- Added overall, home and away strength metrics.
- Added upcoming fixture rating and fixture classification.
- Added fixture trend information.
- Added recent-form and W-D-L placeholders driven by completed Premier League fixture data.
- Added explanatory Team Intelligence content covering:
  - Current Strength
  - Fixture Opportunity
  - Team Intelligence
- Added responsive Team Intelligence table behaviour for smaller displays.
- Added Teams navigation integration using the shared application sidebar.

### Changed
- Extended Player Intelligence Service from player-level and squad-level analysis into league-wide Team Intelligence.
- Reused existing team-strength and fixture-intelligence models rather than introducing duplicate team evaluation logic.
- Combined performance-adjusted team strength with upcoming fixture opportunity to produce Team Intelligence rankings.
- Improved Team Intelligence presentation from large individual team cards to a compact league-style ranking table.
- Improved comparison of all 20 Premier League teams by displaying key strength and fixture metrics within a single ranking view.
- Improved Team Intelligence visual hierarchy using the shared application shell, topbar and dashboard structure.
- Improved Team Intelligence section spacing so eyebrow labels clearly introduce their associated content.
- Improved responsive handling of the Team Intelligence summary and ranking table.
- Preserved completed-match-driven recent form so preseason or previous-season results are not incorrectly presented as current Premier League form.

### Fixed
- Fixed Team Intelligence page initially using a page structure inconsistent with the shared application shell.
- Fixed the fixed application sidebar overlapping Team Intelligence page content.
- Fixed Team Intelligence content alignment by restoring the shared `app-content`, `topbar` and `dashboard` structure.
- Fixed Team Intelligence section eyebrow spacing so section labels no longer visually attach to preceding content.
- Fixed excessive vertical space caused by the original full-width team ranking cards.
- Fixed inefficient use of horizontal space within the original Team Intelligence ranking presentation.

### Testing
- Added `TeamIntelligenceServiceTest.php` for dedicated Team Intelligence service coverage.
- Added validation that Player Intelligence Service exposes `getAllTeamIntelligenceSummaries()`.
- Added validation that Team Intelligence returns exactly 20 Premier League teams.
- Added validation of required Team Intelligence summary fields.
- Added validation of local and FPL team identity integrity.
- Added validation that team IDs and FPL team IDs remain unique.
- Added validation that home, away and overall strength values are numeric and remain between 0 and 100.
- Added validation that Team Intelligence Scores are numeric and remain between 0 and 100.
- Added validation of Team Intelligence classifications.
- Added validation that fixture ratings are numeric and remain between 0 and 100.
- Added validation of fixture classifications.
- Added validation that every team exposes fixture trend information.
- Added validation that Team Intelligence summaries are correctly ordered by Intelligence Score.
- Added Team Intelligence classification-distribution diagnostics.
- Added Team Intelligence performance regression coverage.
- Verified Team Intelligence returns all 20 Premier League teams successfully.
- Verified Arsenal currently ranks first in the development dataset with a Team Intelligence Score of 95.33.
- Verified Team Intelligence produces multiple classification levels across the Premier League.
- Verified `TeamIntelligenceServiceTest.php` passes all 38 checks with 0 failures.
- Added `TeamsPageTest.php` end-to-end Team Intelligence page regression coverage.
- Added HTTP validation of the Team Intelligence page.
- Added validation of the shared application shell and active Teams navigation.
- Added validation of the Team Intelligence Summary section.
- Added validation that exactly five Team Intelligence summary cards are rendered.
- Added validation of:
  - teams analysed
  - Elite team count
  - Strong team count
  - average Team Intelligence
  - average fixture rating
- Added validation of the Premier League Team Intelligence ranking section.
- Added validation of the complete ranking table structure.
- Added validation of all ranking table columns:
  - Team
  - Intelligence
  - Level
  - Overall
  - Home
  - Away
  - Next 5
  - Fixtures
  - Trend
  - Form
  - W-D-L
- Added validation that exactly 20 team ranking rows are rendered.
- Added validation that rankings run from position 1 through position 20.
- Added validation that every team has:
  - Team Intelligence Score
  - Team Intelligence classification badge
  - fixture classification badge
  - fixture trend
  - recent form output
  - W-D-L output
- Added validation that rendered teams remain ordered by Team Intelligence Score.
- Added validation that all 20 current Premier League teams are present in the ranking.
- Added validation of completed-match context for recent form and W-D-L output.
- Added validation of the Team Intelligence explanation section.
- Added validation of Current Strength, Fixture Opportunity and Team Intelligence explanations.
- Added rendered-page detection for PHP fatal errors, parse errors, uncaught errors, warnings, notices and undefined variables.
- Added Team Intelligence page performance regression coverage.
- Verified `TeamsPageTest.php` passes all 75 checks with 0 failures.
- Verified `TeamsPageTest.php` passes through `RunAllTests.php`.
- Verified the complete automated project regression suite remains green after Team Intelligence integration.

## [0.21.0] - Gameweek Decision Intelligence

### Added
- Added `GameweekDecisionEngine.php` as the manager-level Gameweek Decision Intelligence orchestration layer.
- Added Gameweek Decision Intelligence for answering the higher-level question:
  - what should I actually do this gameweek?
- Added overall Gameweek Decision classifications:
  - Hold
  - Consider Transfer
  - Make Transfer
  - Urgent Action
- Added manager-level decision output combining:
  - Gameweek Starting XI Intelligence
  - Captain Intelligence
  - Transfer Intelligence
  - squad reliability risk
  - manager-facing key insights
- Added preservation of recommended formation, Starting XI, bench, captain and vice-captain within the complete decision output.
- Added squad-risk analysis covering:
  - player availability
  - sample confidence
  - Starting XI risk
  - bench risk
  - critical severity
  - high severity
  - medium severity
  - low severity
- Added availability risk thresholds for detecting:
  - major availability concerns
  - significant availability concerns
  - partial availability concerns
- Added confidence risk thresholds for detecting:
  - very low sample confidence
  - limited sample confidence
- Added separate treatment of Starting XI and bench risks so starter issues can escalate decision severity.
- Added transfer-decision integration using the existing squad-aware single-transfer recommendation engine.
- Added transfer-advice classifications:
  - Hold
  - Consider Transfer
  - Make Transfer
  - Review
- Added transfer priority extraction from:
  - direct priority values
  - transfer-priority scores
  - priority labels
  - decision classifications
  - transfer actions
- Added support for the real nested `getSquadTransferRecommendations()` result structure.
- Added transfer recommendation unwrapping from the optimizer response.
- Added transfer priority scoring based primarily on outgoing-player transfer priority.
- Added fallback support for replacement `decision_score` where required.
- Added transfer priority mapping for:
  - High
  - Moderate
  - Medium
  - Low
- Added manager-facing Gameweek Decision insights covering:
  - recommended formation
  - captain
  - vice-captain
  - squad reliability risks
  - transfer priority
  - overall gameweek recommendation
- Added `getGameweekDecision()` to `PlayerIntelligenceService`.
- Added complete Gameweek Decision orchestration through the existing production intelligence pipeline.
- Added complete Gameweek Decision output containing:
  - overall action
  - Gameweek Starting XI output
  - Captain Intelligence output
  - Transfer Intelligence output
  - squad risk analysis
  - key insights
- Added validation that Gameweek Decision Intelligence requires a complete 15-player FPL squad.
- Added validation that negative bank values are rejected.
- Added Gameweek Decision Intelligence to the existing `public/gameweek.php` dashboard.
- Added top-level Decision Intelligence section to the Gameweek dashboard.
- Added prominent "What Should You Do?" overall action panel.
- Added action-specific visual states for:
  - Hold
  - Consider Transfer
  - Make Transfer
  - Urgent Action
- Added Gameweek Decision summary cards displaying:
  - captain
  - vice-captain
  - recommended formation
  - transfer action
- Added Captain Score and classification to the captain and vice-captain decision cards.
- Added transfer priority and top transfer recommendation to the transfer decision card.
- Added squad-risk count to the overall action panel.
- Added dedicated Squad Risks dashboard section.
- Added squad-risk summary cards displaying:
  - total risks
  - critical risks
  - high risks
  - Starting XI risks
- Added detailed squad-risk cards displaying:
  - player name
  - severity
  - risk type
  - squad location
  - risk value
  - manager-facing explanation
- Added capped squad-risk display so only the five highest-priority issues are shown in the dashboard.
- Added no-risk dashboard state when no material squad issues are detected.
- Added dedicated Key Insights section.
- Added numbered manager-facing decision insight cards.
- Added responsive styling for Gameweek Decision Intelligence, Squad Risks and Key Insights.

### Changed
- Extended Gameweek Intelligence from team-selection support into complete manager-level weekly decision support.
- Changed `gameweek.php` to use the complete `getGameweekDecision()` production pipeline rather than calling only `getGameweekStartingXI()`.
- Preserved the existing Gameweek page contract by sourcing `$gameweekResult` from the complete Gameweek Decision result.
- Reused the existing Gameweek Starting XI, Captain Intelligence and Transfer Intelligence systems rather than duplicating scoring logic.
- Improved Gameweek page hierarchy so the overall manager decision is presented before supporting Gameweek statistics.
- Improved Gameweek dashboard flow to present:
  - overall decision
  - Gameweek summary
  - squad risks
  - key insights
  - Starting XI
  - bench
  - formation comparison
- Improved transfer integration so Gameweek Decision Intelligence reads the existing nested squad-transfer optimizer output correctly.
- Improved transfer action selection so the manager-level decision uses outgoing-player transfer priority rather than relying only on generic transfer fields.
- Improved transfer priority interpretation so `Moderate` transfer labels map correctly to medium-priority decision support.
- Improved squad reliability modelling so Starting XI availability and confidence issues have greater decision impact than equivalent bench concerns.
- Improved overall action precedence so:
  - critical squad risk produces Urgent Action
  - high transfer priority produces Make Transfer
  - high squad risk or medium transfer priority produces Consider Transfer
  - otherwise the squad can be held
- Improved Gameweek Intelligence presentation by separating the manager-facing recommendation from the underlying Gameweek Summary.
- Improved squad-risk presentation so reliability problems can be understood without exposing raw diagnostic output.
- Improved key-insight presentation so the reasoning behind the recommendation can be scanned quickly.
- Improved responsive behaviour for decision cards, risk summaries and insight components.

### Fixed
- Fixed Gameweek Decision Intelligence initially failing service integration because the test referenced an undefined `$gameweekSquad` variable instead of the existing `$squadForRecommendations`.
- Fixed invalid-result tests incorrectly failing because PHP null-coalescing converted valid `null` output into fallback values.
- Fixed the decision engine initially treating real Transfer Intelligence as `Unknown` because the production transfer result is nested under optimizer output.
- Fixed transfer advice initially returning:
  - Review
  - Unknown priority
  - no transfer score
  despite valid high-priority transfer recommendations being available.
- Fixed real transfer recommendation extraction so the decision engine reads the optimizer's nested recommendation groups.
- Fixed transfer priority scoring so the outgoing player's `transfer_priority` is used as the main manager-action signal.
- Fixed transfer priority label handling so `High` and `Moderate` values from Squad Transfer Intelligence are understood by Gameweek Decision Intelligence.
- Fixed manager-level transfer advice so high-priority squad-transfer recommendations now correctly produce `Make Transfer`.
- Fixed overall action remaining at `Consider Transfer` when valid Transfer Intelligence supported a stronger `Make Transfer` recommendation.
- Fixed the Gameweek page initially exposing only Starting XI Intelligence rather than the complete manager-level decision pipeline.
- Fixed Decision Intelligence page-state handling so the idle page does not render decision, risk or insight output before a squad is analysed.
- Fixed Gameweek Decision UI spacing so Decision Intelligence, Squad Risks and Key Insights maintain consistent section hierarchy.

### Testing
- Added dedicated `GameweekDecisionEngineTest.php` unit and behaviour coverage.
- Added validation of successful Gameweek Decision generation.
- Added validation of overall action output.
- Added validation that low-risk squads can recommend Hold.
- Added validation that Gameweek Intelligence data is preserved through the decision engine.
- Added validation that recommended formation is preserved.
- Added validation that Starting XI Score and Bench Score are preserved.
- Added validation that the complete Starting XI and bench are preserved.
- Added validation that Captain Intelligence recommendations are preserved.
- Added validation that captain and vice-captain remain distinct decision outputs.
- Added validation that Captain Score is preserved.
- Added validation of clean squad-risk analysis.
- Added validation that unavailable starters create critical squad risk.
- Added validation that critical starter risk produces Urgent Action.
- Added validation that very low-confidence starters create high squad risk.
- Added validation that high confidence risk can produce Consider Transfer.
- Added validation of low, medium and high transfer-priority actions.
- Added validation that transfer recommendations are preserved.
- Added validation of numeric transfer-priority fallback behaviour.
- Added validation that Gameweek Decision Intelligence succeeds without Transfer Intelligence data.
- Added validation of manager-facing key insights.
- Added validation that 0-1 confidence values are normalised before risk analysis.
- Added validation that invalid Gameweek Intelligence is rejected.
- Added validation that incomplete Starting XI structures are rejected.
- Added validation that invalid Captain Intelligence is rejected.
- Added validation of the complete Gameweek Decision result structure.
- Verified `GameweekDecisionEngineTest.php` passes all 57 checks with 0 failures.
- Added Gameweek Decision Intelligence integration coverage to `PlayerIntelligenceServiceTest.php`.
- Added validation of the complete service orchestration path:
  - Gameweek Starting XI
  - Captain Intelligence
  - Transfer Intelligence
  - Gameweek Decision Engine
- Added validation that Gameweek Decision Intelligence returns a valid overall action.
- Added validation that Gameweek, Captain and Transfer outputs are included in the complete decision response.
- Added validation that decision output preserves formation, captain and vice-captain recommendations.
- Added validation of squad-risk count and detailed risk output.
- Added validation that key insights are generated.
- Added validation that incomplete squads are rejected.
- Added validation that negative bank values are rejected.
- Verified `PlayerIntelligenceServiceTest.php` passes all 395 checks with 0 failures after Gameweek Decision integration.
- Added `GameweekDecisionRealDataTest.php` against the current live project dataset.
- Verified Gameweek Decision Intelligence against 599 current Player Intelligence summaries.
- Added real-data construction of a legal 15-player squad.
- Added validation of:
  - two goalkeepers
  - five defenders
  - five midfielders
  - three forwards
  - three-player-per-club limit
- Added real-data validation of the complete Gameweek Decision production path.
- Added real-data diagnostics for:
  - overall action
  - recommended formation
  - Starting XI Score
  - Bench Score
  - captain
  - vice-captain
  - squad-risk summary
  - detailed squad risks
  - transfer advice
  - raw Transfer Intelligence structure
  - outgoing transfer priorities
  - ranked replacements
  - replacement decision types
  - replacement decision scores
  - key insights
- Added validation that detailed squad-risk counts match reported risk totals.
- Added validation of risk type, severity and numeric risk values.
- Added validation that raw squad-transfer analysis remains valid.
- Added validation that the transfer optimizer returns successful recommendation output.
- Added validation of Gameweek Decision action classifications.
- Added validation that the final decision preserves Gameweek and Captain Intelligence output.
- Added complete Gameweek Decision performance regression coverage.
- Verified `GameweekDecisionRealDataTest.php` passes all 37 checks with 0 failures.
- Added real-data validation that high-priority transfer output is correctly mapped into Gameweek Decision Intelligence.
- Verified high-priority transfer output correctly produces:
  - `Make Transfer`
  - `High` priority
  - numeric transfer score
- Extended `GameweekPageTest.php` with Gameweek Decision Intelligence UI coverage.
- Added validation that the idle page does not prematurely render:
  - Decision Intelligence
  - Squad Risks
  - Key Insights
- Added validation of the "What Should You Do?" section.
- Added validation of the Overall Action panel.
- Added validation of supported action-specific hero classes.
- Added validation that exactly four supporting decision cards are rendered.
- Added validation of Captain, Vice-Captain, Formation and Transfer decision cards.
- Added validation of Captain Score, Starting XI Score and transfer priority output.
- Added validation of the Squad Reliability section.
- Added validation that exactly four squad-risk summary cards are rendered.
- Added validation of Total Risks, Critical, High and Starting XI Risks summaries.
- Added validation that detailed risk output is capped at five cards.
- Added validation of risk severity styling.
- Added validation of clean-state squad-risk handling.
- Added validation of the Decision Explanation and Key Insights sections.
- Added validation that at least one numbered insight is rendered.
- Added validation that the overall gameweek recommendation appears in Key Insights.
- Verified extended `GameweekPageTest.php` passes successfully with all Decision Intelligence UI checks green.
- Verified `RunAllTests.php` passes successfully after complete Gameweek Decision Intelligence integration.
- Complete automated project regression suite passes successfully.

## [0.20.0] - Gameweek Intelligence

### Added
- Added dedicated Gameweek Intelligence for optimising a complete 15-player FPL squad for the immediate upcoming gameweek.
- Added Gameweek Score for evaluating each squad player's short-term starting value.
- Added Gameweek Score using:
  - Player Intelligence
  - player strength
  - immediate next-fixture opportunity
  - sample confidence
  - player availability
- Added calibrated immediate-fixture scoring so Gameweek Intelligence uses the same controlled fixture scale established by Captain Intelligence.
- Added Gameweek core score representing underlying immediate-gameweek quality before reliability adjustments.
- Added confidence and availability modifiers as multiplicative Gameweek Score risk adjustments.
- Added automatic Starting XI optimisation across all eight legal FPL formations:
  - 3-4-3
  - 3-5-2
  - 4-3-3
  - 4-4-2
  - 4-5-1
  - 5-2-3
  - 5-3-2
  - 5-4-1
- Added automatic selection of the highest-scoring legal Starting XI.
- Added automatic ordered substitute bench generation.
- Added enforced backup-goalkeeper placement at Bench 4.
- Added formation comparison output for all eight legal FPL formations.
- Added Starting XI Score and Bench Score metrics.
- Added complete Gameweek Score component output for every evaluated squad player.
- Added summary matching between imported squad players and current Player Intelligence summaries.
- Added fallback tracking for squad players that cannot be matched to current Player Intelligence summaries.
- Added `public/gameweek.php` as the dedicated Gameweek Intelligence dashboard.
- Added Gameweek Intelligence to the shared application sidebar navigation.
- Added FPL Entry ID input for analysing a manager's gameweek squad.
- Added development preview mode so Gameweek Intelligence can be inspected before the live FPL gameweek squad becomes publicly available.
- Added Gameweek Summary cards displaying:
  - recommended formation
  - Starting XI Score
  - Bench Score
  - gameweek
  - squad size
- Added Starting XI pitch view for visualising the recommended line-up by position.
- Added dedicated goalkeeper, defender, midfielder and forward pitch rows.
- Added Starting XI player cards displaying:
  - player name
  - team
  - price
  - Gameweek Score
  - immediate fixture rating
- Added direct player-profile navigation from Starting XI player cards.
- Added Ordered Bench section displaying all four substitutes in recommended order.
- Added bench player cards displaying:
  - substitute order
  - player identity
  - position
  - team
  - price
  - Gameweek Score
  - immediate fixture rating
  - sample confidence
  - availability
- Added Formation Intelligence section comparing every legal FPL formation.
- Added formation comparison cards displaying:
  - formation rank
  - formation
  - Starting XI Score
  - Bench Score
- Added recommended-formation highlighting within the formation comparison.
- Added responsive Gameweek Intelligence dashboard styling.

### Changed
- Extended Player Intelligence with squad-level Gameweek Starting XI optimisation.
- Changed immediate Gameweek fixture evaluation to use calibrated next-fixture values rather than uncompressed extreme fixture ratings.
- Calibrated raw next-fixture ratings so:
  - strongest fixtures are reduced from 100 to 80
  - strong fixtures are reduced toward 60
  - neutral fixtures remain around 50
  - difficult fixtures are raised toward 40
  - weakest fixtures are raised from 0 to 20
- Improved Gameweek Score balance so immediate fixture quality supports selection decisions without overwhelming underlying player quality.
- Improved confidence handling so low-sample players receive stronger reliability penalties when competing for Starting XI places.
- Improved availability handling so players with availability concerns are appropriately reduced in Gameweek Score.
- Improved formation selection so all eight legal FPL structures are evaluated against the same 15-player squad.
- Improved bench construction so outfield substitutes are ordered separately while the backup goalkeeper remains fixed at Bench 4.
- Extended the application from captaincy decision support into complete weekly team-selection decision support.
- Improved Gameweek Intelligence presentation with a position-based pitch rather than a standard player list.
- Improved visual separation between goalkeeper, defence, midfield and forward lines.
- Improved Gameweek dashboard section spacing so Starting XI, Substitutes and Formation Intelligence sections have consistent visual hierarchy.
- Improved formation comparison presentation so the recommended structure can be identified immediately.
- Improved responsive behaviour of Gameweek summary, pitch, bench and formation components.

### Fixed
- Fixed Gameweek fixture scoring initially using uncalibrated raw next-fixture ratings, causing extreme fixture values to have excessive influence on Starting XI selection.
- Fixed strongest fixtures initially contributing a full 100-point fixture component instead of the calibrated 80-point value.
- Fixed weakest fixtures initially contributing a zero fixture component instead of the calibrated 20-point floor.
- Fixed low-confidence players retaining too much Gameweek value before confidence penalties were strengthened.
- Fixed Gameweek dashboard section eyebrow spacing so section labels visually belong to the content they introduce.
- Fixed Gameweek page regression checks that initially failed because rendered HTML whitespace differed from literal test strings.
- Fixed bench-order page tests so Bench 1 through Bench 4 are validated against the actual rendered bench-card markup.
- Fixed Gameweek Score, formation Bench metric and immediate-gameweek content checks to tolerate valid rendered HTML whitespace.

### Testing
- Added Gameweek Starting XI service coverage to `PlayerIntelligenceServiceTest.php`.
- Added validation that Gameweek Starting XI optimisation returns a successful result.
- Added validation that the recommended Starting XI contains exactly 11 players.
- Added validation that the ordered bench contains exactly four players.
- Added validation that the recommended formation is returned.
- Added validation that all eight legal FPL formations are evaluated.
- Added validation of legal Starting XI positional structure:
  - exactly one goalkeeper
  - three to five defenders
  - two to five midfielders
  - one to three forwards
- Added validation that all Gameweek Scores are numeric and remain between 0 and 100.
- Added validation that Gameweek Score components are returned for every squad player.
- Added validation of immediate fixture components.
- Added validation that all 15 squad players are evaluated.
- Added validation of Player Intelligence summary matching and fallback counts.
- Added validation of sequential bench ordering.
- Added validation that the backup goalkeeper is always Bench 4.
- Added validation of Starting XI Score and Bench Score.
- Added validation that incomplete squads are rejected.
- Added validation that duplicate-player squads are rejected.
- Verified Player Intelligence service coverage passes with Gameweek Starting XI integration.
- Added `GameweekStartingXIRealDataTest.php` against the live project player dataset.
- Verified Gameweek Intelligence against 581 current Player Intelligence summaries.
- Added construction of a valid real-data 15-player FPL squad containing:
  - two goalkeepers
  - five defenders
  - five midfielders
  - three forwards
- Added validation of the three-player-per-club squad constraint.
- Added real-data validation that all 15 squad players match current Player Intelligence summaries.
- Added real-data validation that no Player Intelligence summary fallbacks are required for the diagnostic squad.
- Added detailed Starting XI diagnostics including:
  - Gameweek Score
  - Player Intelligence
  - strength
  - calibrated fixture rating
  - core score
  - confidence
  - confidence modifier
  - availability
  - availability modifier
- Added detailed ordered-bench diagnostics.
- Added real-data comparison of all eight legal formations.
- Added validation that formation results are ordered by Starting XI Score.
- Added real-data Starting XI positional-distribution validation.
- Added Gameweek Score and reliability-modifier integrity checks.
- Added complete real-data Gameweek Intelligence performance regression coverage.
- Verified `GameweekStartingXIRealDataTest.php` passes all 32 checks with 0 failures.
- Added `GameweekPageTest.php` end-to-end Gameweek Intelligence page regression coverage.
- Added HTTP validation of the initial Gameweek Intelligence page.
- Added HTTP validation of development preview mode.
- Added validation of the Gameweek Intelligence application shell and active navigation.
- Added validation of the FPL Entry ID input and Analyse Gameweek action.
- Added validation that the idle page does not prematurely render generated recommendation output.
- Added validation of all five Gameweek Summary cards.
- Added validation of the Starting XI pitch structure.
- Added validation of goalkeeper, defender, midfielder and forward pitch rows.
- Added validation that the pitch contains exactly 11 Starting XI player cards.
- Added validation of the Ordered Bench section.
- Added validation that exactly four bench player cards are rendered.
- Added validation of Bench 1 through Bench 4 ordering.
- Added validation that Bench 4 is the backup goalkeeper.
- Added validation of bench Gameweek Score, confidence and availability output.
- Added validation of the Formation Intelligence section.
- Added validation that exactly eight formation comparison cards are rendered.
- Added validation that exactly one formation is marked as recommended.
- Added validation of formation rankings from first through eighth.
- Added validation that all 15 generated squad players provide player-profile navigation.
- Added validation of Gameweek Intelligence explanatory content.
- Added rendered-page detection for PHP fatal errors, parse errors, uncaught errors, warnings, notices and undefined variables.
- Added Gameweek Intelligence page performance regression checks.
- Verified the initial Gameweek Intelligence page loads within the two-second regression threshold.
- Verified Gameweek Intelligence development preview renders within the 15-second regression threshold.
- Verified `GameweekPageTest.php` passes all 71 checks with 0 failures.
- Verified `GameweekPageTest.php` passes through `RunAllTests.php`.
- Complete automated project regression suite passes successfully.

## [0.19.0] - Captain Intelligence

### Added
- Added `CaptainIntelligence.php` as the dedicated captaincy evaluation engine.
- Added Captain Intelligence scoring for evaluating FPL captaincy candidates.
- Added Captain Score using:
  - player strength
  - immediate next-fixture opportunity
  - attacking threat
  - sample confidence
  - player availability
- Added position-aware attacking threat modelling for goalkeepers, defenders, midfielders and forwards.
- Added attacking threat inputs derived from:
  - goals
  - assists
  - expected goals
  - expected assists
- Added Captain Intelligence fields to Player Intelligence summaries:
  - goals rating
  - assists rating
  - expected goals rating
  - expected assists rating
  - next-fixture rating
- Added dedicated next-fixture intelligence so captaincy decisions use the immediate fixture rather than the broader fixture horizon.
- Added calibrated captain fixture scoring to prevent extreme fixture ratings from dominating Captain Score.
- Added raw and calibrated fixture components to Captain Intelligence output.
- Added Captain Intelligence core score representing underlying captaincy quality before reliability adjustments.
- Added confidence and availability modifiers as multiplicative risk adjustments.
- Added Captain Intelligence classifications:
  - Elite Captain
  - Strong Captain
  - Good Option
  - Differential
  - Avoid
- Added squad-level Captain Intelligence recommendations.
- Added automatic captain and vice-captain recommendations from a complete 15-player FPL squad.
- Added ranked alternative captaincy options.
- Added complete squad captaincy rankings.
- Added preservation of current FPL captain and vice-captain metadata within recommendation output.
- Added Captain Intelligence integration to Squad Intelligence.
- Added Captain Recommendations section to the Squad Intelligence dashboard.
- Added prominent captain and vice-captain recommendation cards.
- Added alternative captaincy cards for the next-best ranked options.
- Added Captain Score, classification, fixture and attacking threat metrics to captain recommendation cards.
- Added development preview support so Captain Intelligence can be inspected before the live FPL gameweek squad becomes publicly available.
- Added responsive Captain Intelligence dashboard styling.

### Changed
- Extended Player Intelligence to expose the attacking and fixture metrics required by Captain Intelligence.
- Changed captain fixture evaluation to use the player's immediate next fixture rather than the multi-fixture Fixture Rating.
- Calibrated extreme next-fixture ratings toward neutral values before applying them to Captain Score.
- Changed confidence from an additive Captain Score component to a risk modifier.
- Changed availability from an additive Captain Score component to a risk modifier.
- Improved Captain Score balance so strong fixtures support captaincy decisions without overwhelming player quality and attacking threat.
- Improved low-confidence handling so high underlying scores are reduced when the available player sample is unreliable.
- Improved captaincy ranking behaviour so attacking midfielders and forwards naturally compete for the highest recommendations.
- Improved goalkeeper handling so goalkeepers do not dominate captaincy rankings through strength and fixture scores alone.
- Extended Squad Intelligence from squad analysis into captaincy decision support.
- Improved Captain Intelligence presentation with clear visual hierarchy between captain, vice-captain and alternative options.

### Fixed
- Fixed Captain Intelligence initially receiving zero-valued strength, fixture and availability inputs from Player Intelligence.
- Fixed attacking threat initially returning zero because required attacking ratings were not exposed by Player Intelligence.
- Fixed next-fixture calculations using broader fixture-horizon data instead of the immediate upcoming fixture.
- Fixed extreme fixture ratings having excessive influence on Captain Score.
- Fixed goalkeeper and defender captaincy candidates ranking disproportionately highly before position-aware attacking threat was introduced.
- Fixed low-confidence players receiving insufficient penalties despite unreliable sample sizes.
- Fixed Captain Intelligence component output containing duplicated `components` array declarations during development.
- Fixed undefined next-fixture rating usage within player replacement analysis.
- Fixed Captain Intelligence classification thresholds so score labels correctly reflect the calibrated scoring model.
- Fixed real-squad Captain Intelligence testing incorrectly failing when FPL has not yet exposed a public gameweek squad.

### Testing
- Added dedicated `CaptainIntelligenceTest.php` unit and behaviour coverage.
- Added validation of strong captain scoring and Captain Score bounds.
- Added validation that better fixtures improve Captain Score.
- Added validation of raw and calibrated fixture scores.
- Added validation that higher attacking threat improves Captain Score.
- Added validation of position-aware attacking threat behaviour.
- Added validation of confidence normalisation and confidence modifiers.
- Added validation of availability modifiers.
- Added validation of invalid Captain Intelligence inputs.
- Added validation of Captain Intelligence component output.
- Added explicit Captain Intelligence classification threshold tests.
- Verified `CaptainIntelligenceTest.php` passes all 36 checks with 0 failures.
- Added `CaptainIntelligenceRealDataTest.php` against the live project player dataset.
- Verified Captain Intelligence against 581 Player Intelligence summaries.
- Verified 400 current players can be evaluated as valid captaincy candidates.
- Added next-fixture team-distribution diagnostics covering all 20 Premier League teams.
- Added validation that real Captain Scores remain numeric and between 0 and 100.
- Added validation that real captain rankings are not dominated by goalkeepers.
- Added validation that attacking players appear within the highest-ranked captaincy candidates.
- Verified `CaptainIntelligenceRealDataTest.php` passes all 14 checks with 0 failures.
- Added `CaptainIntelligenceRegressionTest.php` for long-term Captain Intelligence regression protection.
- Added regression validation of required Player Intelligence captain fields.
- Added regression validation of fixture calibration.
- Added regression validation of confidence and availability modifier bounds.
- Added regression validation of captain ranking composition.
- Added regression validation of Captain Intelligence classification distribution.
- Added Captain Intelligence performance regression coverage.
- Verified `CaptainIntelligenceRegressionTest.php` passes all 33 checks with 0 failures.
- Added squad-level Captain Intelligence recommendation tests.
- Added validation of captain and vice-captain ranking.
- Added validation of alternative captaincy recommendations.
- Added validation that all 15 squad players are ranked.
- Added validation of sequential captaincy ranking.
- Added validation that Captain Score ordering is preserved.
- Added validation of Captain Intelligence component propagation through squad recommendations.
- Added validation of current FPL captaincy metadata preservation.
- Added validation of invalid recommendation limits.
- Added validation that incomplete and duplicate squads are rejected.
- Added real FPL squad Captain Intelligence production-path diagnostics.
- Added graceful test handling for the FPL API `no_public_squad` state before gameweek squads become publicly available.
- Verified Captain Intelligence real-squad diagnostics pass without treating unavailable pre-gameweek FPL squad data as an application failure.
- Verified Player Intelligence, Captain Intelligence and Captain Recommendation regression coverage passes successfully.

## [0.18.0] - Wildcard Intelligence UI

### Added
- Added `public/wildcard.php` as the dedicated Wildcard Intelligence dashboard.
- Added Wildcard Intelligence to the shared application sidebar navigation.
- Added on-demand wildcard squad generation through the Wildcard Intelligence interface.
- Added wildcard summary cards displaying:
  - squad cost
  - remaining bank
  - best formation
  - Starting XI Score
  - Structure Score
- Added Starting XI pitch view for visualising the optimizer's recommended team by position.
- Added dedicated goalkeeper, defender, midfielder and forward pitch rows.
- Added Starting XI player cards displaying:
  - player name
  - team
  - price
  - Starter Score
  - Wildcard Score
- Added direct player-profile navigation from Starting XI player cards.
- Added Ordered Bench section displaying all four substitutes in optimizer-selected order.
- Added bench player cards displaying:
  - substitute order
  - player identity
  - position
  - team
  - price
  - sample confidence
  - reliability penalty
  - adjusted bench value
- Added visual reliability warnings for low-confidence bench players.
- Added Structure & Reliability intelligence section displaying:
  - Wildcard Score
  - Raw Bench Score
  - Adjusted Bench Score
  - Bench Reliability Penalty
  - goalkeeper minimum confidence
  - goalkeeper Starter Score quality floor
- Added Why This Squad explanation section.
- Added generated squad insights covering:
  - best formation
  - reliable starting goalkeeper
  - premium squad core
  - wildcard budget usage
  - bench reliability
- Added Generate Wildcard Squad and Regenerate Squad actions.
- Added responsive Wildcard Intelligence styling for summary cards, pitch layout, bench cards and intelligence panels.

### Changed
- Extended the shared application layout to support the Wildcard Intelligence dashboard.
- Improved wildcard output presentation so optimizer results are presented as decision-support intelligence rather than raw diagnostic data.
- Improved Starting XI readability by replacing a standard player list with a position-based pitch layout.
- Improved visual separation between goalkeeper, defence, midfield and forward lines.
- Improved section spacing and hierarchy throughout the Wildcard Intelligence page.
- Improved bench presentation so substitute order and reliability risk can be understood at a glance.
- Improved goalkeeper intelligence presentation so minimum confidence and adaptive Starter Score requirements are visible in the UI.
- Improved wildcard squad explanation so important optimizer decisions are translated into user-facing insights.
- Improved responsive behaviour of wildcard summary, pitch, bench and intelligence components.

### Fixed
- Fixed duplicate Wildcard Starting XI output during pitch-view development.
- Fixed Wildcard pitch player cards being compressed into narrow vertical columns.
- Fixed Starting XI pitch rows not using the available dashboard width effectively.
- Fixed inconsistent spacing between Wildcard dashboard sections and section eyebrow headings.
- Fixed goalkeeper reliability values not reading from the correct optimizer result fields.
- Fixed Wildcard summary cards initially rendering without the intended card styling.
- Fixed Starting XI and substitute sections lacking sufficient visual separation.

### Testing
- Added `WildcardPageTest.php` end-to-end Wildcard Intelligence page regression coverage.
- Added HTTP validation of the initial Wildcard Intelligence page.
- Added HTTP validation of generated wildcard mode.
- Added validation of the Wildcard Intelligence application shell and navigation.
- Added validation of Generate Wildcard Squad and Regenerate Squad actions.
- Added validation that the idle page does not prematurely render generated squad output.
- Added validation of wildcard summary output including:
  - squad cost
  - remaining bank
  - formation
  - Starting XI Score
  - Structure Score
- Added validation of the Starting XI pitch structure.
- Added validation of goalkeeper, defender, midfielder and forward pitch rows.
- Added validation that the pitch contains exactly 11 Starting XI player cards.
- Added validation that Starting XI player cards link to player profiles.
- Added validation of the Ordered Bench section.
- Added validation that exactly four bench player cards are rendered.
- Added validation of bench ordering from Bench 1 through Bench 4.
- Added validation of bench confidence information.
- Added validation of Structure & Reliability intelligence output.
- Added validation of Wildcard Score, Raw Bench Score, Adjusted Bench Score and Reliability Penalty.
- Added validation of goalkeeper minimum confidence and adaptive quality-floor output.
- Added validation of Why This Squad intelligence.
- Added validation of Best Formation, Reliable Goalkeeper, Premium Core, Budget Use and Bench Reliability insights.
- Added validation that all 15 generated squad players provide player-profile navigation.
- Added rendered-page detection for PHP fatal errors, parse errors, uncaught errors, warnings and notices.
- Added Wildcard Intelligence page performance regression checks.
- Verified the initial Wildcard Intelligence page loads within the two-second regression threshold.
- Verified generated wildcard optimisation and page rendering complete within the 15-second regression threshold.
- Verified `WildcardPageTest.php` passes all 59 checks with 0 failures.
- Verified `WildcardPageTest.php` passes through `runAllTests.php`.
- Complete automated project regression suite passes successfully.


## [0.17.0] - Wildcard / Full Squad Optimizer

### Added
- Added `WildcardOptimizer` for generating complete 15-player FPL squads from Player Intelligence data.
- Added full wildcard squad construction using the required FPL structure of:
  - 2 goalkeepers
  - 5 defenders
  - 5 midfielders
  - 3 forwards
- Added £100.0m wildcard budget enforcement.
- Added maximum three-player-per-club enforcement.
- Added duplicate-player protection.
- Added beam-search based squad optimisation to evaluate strong full-squad combinations without exhaustive brute-force searching.
- Added position-aware candidate pools combining high-scoring players with lower-cost budget enablers.
- Added separate wildcard optimisation scores:
  - Starter Score
  - Squad Value Score
  - Wildcard Score
- Added role-aware squad optimisation so likely starters and supporting squad players are valued differently.
- Added `WildcardSquadStructure` for analysing the strongest legal Starting XI from a generated wildcard squad.
- Added evaluation of all legal FPL formations.
- Added automatic best-formation selection.
- Added ordered four-player bench generation.
- Added separate Starting XI Score, Bench Score and Structure Score.
- Added goalkeeper starter reliability requirements.
- Added minimum goalkeeper sample-confidence requirement.
- Added adaptive goalkeeper Starter Score quality floor based on the strongest reliable goalkeeper in the available player pool.
- Added goalkeeper starter eligibility so low-confidence goalkeepers can remain as backup options without being selected in the Starting XI.
- Added goalkeeper-aware minimum remaining budget calculation so beam-search states reserve enough budget for a valid starting goalkeeper.
- Added bench reliability scoring.
- Added confidence-based bench reliability penalties.
- Added reliability-adjusted bench scoring while retaining low-confidence budget enablers as legal squad options.
- Added weighted bench importance based on substitute order.
- Added raw bench score and reliability-adjusted bench score reporting.
- Added wildcard search diagnostic metadata including:
  - beam width
  - position score limit
  - position cheap-player limit
  - goalkeeper minimum confidence
  - goalkeeper quality ratio
  - goalkeeper Starter Score floor
  - final states considered

### Changed
- Improved wildcard squad ranking so complete squad structure is prioritised over simply selecting the fifteen individually highest-scoring players.
- Improved wildcard budget allocation so premium players can be selected when their Starting XI contribution justifies their price.
- Improved role-aware beam-search ranking to distinguish likely starters from bench and budget-enabling squad players.
- Improved candidate generation to preserve both high-quality and low-cost alternatives.
- Improved final squad evaluation to consider the quality of the best legal Starting XI.
- Improved goalkeeper selection so inexpensive but unreliable goalkeepers cannot become the recommended starter solely because they release budget elsewhere.
- Improved beam-search budget feasibility checks to account for the cost of a reliable starting goalkeeper.
- Improved bench construction so low-confidence players remain available as budget enablers but receive an appropriate reliability penalty.
- Improved final squad ranking using Starting XI strength and reliability-adjusted bench contribution.
- Improved wildcard diagnostics to expose Starter Score, Squad Value Score and Wildcard Score for every selected player.
- Improved wildcard diagnostics to expose individual bench reliability penalties and adjusted bench values.

### Model
- Starter Score prioritises player quality and expected Starting XI contribution rather than price efficiency alone.
- Squad Value Score measures the usefulness of a player within the overall £100.0m squad budget.
- Wildcard Score provides a balanced player score for full-squad construction.
- Starting XI quality contributes 85% of the final Structure Score.
- Reliability-adjusted bench quality contributes 15% of the final Structure Score.
- Starting goalkeeper eligibility requires both sufficient sample confidence and sufficient Starter Score quality.
- Goalkeeper quality eligibility is adaptive rather than based on a fixed absolute score.
- The goalkeeper Starter Score floor is set to 85% of the strongest goalkeeper meeting the minimum confidence requirement.
- Goalkeeper starter minimum sample confidence is 50%.
- Bench reliability remains score-based rather than a hard eligibility requirement.
- Bench reliability penalties currently use:
  - 75-100% confidence: 0% penalty
  - 50-74.9% confidence: 2% penalty
  - 25-49.9% confidence: 5% penalty
  - 10-24.9% confidence: 10% penalty
  - below 10% confidence: 18% penalty
- Bench substitute contribution is weighted by bench order, with the first substitute carrying the greatest importance and the backup goalkeeper the least.

### Performance
- Added candidate pruning to keep full-squad optimisation practical against the complete Player Intelligence dataset.
- Added fast minimum-remaining-cost calculations during beam search.
- Added goalkeeper-aware budget reservation to remove impossible beam-search states earlier.
- Current real-data optimisation evaluates approximately 400 valid wildcard candidates from the complete imported player pool.
- Current wildcard optimisation runtime remains approximately six seconds on the local development environment.
- Added regression protection requiring real-data wildcard optimisation to complete within 15 seconds.

### Testing
- Added `WildcardOptimizerTest.php` for synthetic wildcard optimiser behaviour.
- Added validation of:
  - successful squad generation
  - squad size
  - positional structure
  - £100.0m budget
  - remaining bank
  - maximum three-player-per-club rule
  - duplicate-player protection
  - invalid squad handling
  - over-budget squad handling
  - empty player-pool handling
  - invalid budget handling
  - insufficient position-pool handling
  - confidence normalisation
- Added `WildcardSquadStructureTest.php`.
- Added validation of:
  - 11-player Starting XI construction
  - four-player bench construction
  - legal FPL formations
  - best-formation selection
  - bench ordering
  - backup goalkeeper positioning
  - all supported legal formations
  - invalid squad rejection
- Added `WildcardOptimizerRealDataTest.php`.
- Added full real-data wildcard optimisation diagnostics using imported Player Intelligence data.
- Added real-data validation of:
  - 15-player squad construction
  - squad validity
  - positional structure
  - budget
  - duplicate protection
  - maximum club limit
  - Starting XI structure
  - bench structure
  - best formation
  - Starter Score
  - Squad Value Score
  - Wildcard Score
  - goalkeeper reliability
  - bench reliability
- Added individual bench reliability diagnostic output showing:
  - sample confidence
  - reliability penalty
  - adjusted bench value
- Added `WildcardOptimizerRegressionTest.php`.
- Added 47 end-to-end real-data wildcard regression checks.
- Added regression protection for squad legality, formation legality, goalkeeper reliability, bench reliability, score integrity, search metadata and runtime.
- Verified the Wildcard Optimizer regression test passes all 47 checks.
- Verified current real-data optimisation produces a legal £100.0m wildcard squad.
- Verified goalkeeper confidence and adaptive quality-floor requirements are enforced.
- Verified reliability-adjusted bench scoring operates correctly.
- Verified the complete automated project regression suite passes successfully.

## [0.16.0] - Squad Intelligence UI

### Added
- Added `public/squad.php` as the dedicated Squad Intelligence dashboard.
- Added Squad Intelligence to the shared application sidebar navigation.
- Added development preview mode for Squad Intelligence using `?preview=1`.
- Added a complete synthetic 15-player preview squad for frontend development without requiring a live FPL squad import.
- Added development preview banner and squad-status information.
- Added Squad Intelligence Summary displaying:
  - average squad Intelligence
  - weakest position
  - squad bank
  - squad validity
- Added Position Intelligence breakdown for GK, DEF, MID and FWD.
- Added ranked Transfer Priorities showing the squad players most in need of review.
- Added Best Single Moves section using the existing squad-aware single-transfer optimiser.
- Added single-transfer recommendation cards displaying:
  - outgoing player
  - transfer priority
  - replacement player
  - transfer decision
  - Intelligence movement
  - replacement score
  - resulting squad bank
- Added Best Double Transfers section using the existing squad-aware double-transfer optimiser.
- Added double-transfer recommendation cards displaying:
  - Squad Score
  - combination classification
  - Transfer A
  - Transfer B
  - individual transfer decisions
  - Intelligence movement
  - resulting squad bank
- Added expandable Current Squad section.
- Added accessible Current Squad toggle with `aria-expanded` state handling.
- Added player-profile navigation throughout Squad Intelligence.
- Added responsive Squad Intelligence frontend styling.
- Added dedicated styling for summary cards, position intelligence, transfer priorities, single-transfer recommendations, double-transfer plans and current-squad display.

### Changed
- Extended the shared application layout to support the Squad Intelligence dashboard.
- Improved Squad Intelligence information hierarchy and spacing for clearer separation between analysis sections.
- Improved transfer recommendation presentation so detailed intelligence remains available without overcrowding the interface.
- Updated Squad Intelligence to use the shared `PlayerRepository` instance required by the squad analysis workflow.
- Improved development preview output so the complete Squad Intelligence interface can be tested independently of a real FPL squad.
- Improved responsive behaviour and content positioning alongside the fixed application sidebar.

### Fixed
- Fixed Squad Intelligence content overlapping the fixed sidebar.
- Fixed overly compressed Squad Intelligence sections and recommendation output.
- Fixed Current Squad content being permanently visible by introducing an expandable panel.
- Fixed development preview presentation so synthetic squad data renders through the intended frontend structure.
- Fixed `SquadPageTest.php` preview URL construction when executed through `runAllTests.php`.
- Fixed test URL handling that incorrectly combined the localhost base URL with the Windows filesystem path.

### Testing
- Added `SquadPageTest.php` end-to-end Squad Intelligence page regression coverage.
- Added HTTP validation of the development preview page.
- Added validation of the Squad Intelligence application shell.
- Added validation of development preview mode and the synthetic 15-player squad.
- Added validation of Squad Intelligence Summary output.
- Added validation of Position Intelligence output.
- Added validation of Transfer Priority output.
- Added validation of Best Single Moves recommendations.
- Added validation of Best Double Transfers recommendations.
- Added validation that double-transfer plans expose Squad Score, Transfer A and Transfer B.
- Added validation of the expandable Current Squad control and accessibility state.
- Added validation of player-profile navigation.
- Added detection for PHP fatal errors, parse errors, uncaught errors, warnings and notices in rendered Squad Intelligence output.
- Verified `SquadPageTest.php` passes when executed directly.
- Verified `SquadPageTest.php` passes through `runAllTests.php`.
- Complete automated regression suite passes successfully.

## [0.15.0] - Squad Transfer Intelligence

### Added
- Added FPL squad importing for complete 15-player squads.
- Added squad validation for position structure, duplicate players and club limits.
- Added squad intelligence analysis including:
  - average squad Intelligence
  - positional Intelligence averages
  - weakest-position detection
  - individual transfer-priority scoring
  - ranked transfer priorities
- Added squad-aware single-transfer optimisation.
- Added legal replacement filtering based on:
  - player position
  - available squad budget
  - existing squad players
  - FPL maximum-three-players-per-club rule
- Added squad-aware double-transfer optimisation.
- Added automatic evaluation of priority outgoing-player pairs.
- Added same-position replacement preservation across double transfers.
- Added combined affordability validation using the squad bank and outgoing player value.
- Added final-squad validation after proposed double transfers.
- Added candidate pruning to keep double-transfer optimisation efficient against the complete player pool.
- Added squad-aware double-transfer ranking.
- Added outgoing transfer-priority totals to double-transfer recommendations.
- Added squad-priority bonus scoring.
- Added `squad_score` combining transfer-combination quality with the importance of replacing the outgoing squad players.
- Added resulting squad bank calculation and reporting.
- Added squad-aware recommendation summaries.
- Added service-level squad transfer optimisation through `PlayerIntelligenceService`.

### Changed
- Improved transfer-priority scoring so low sample confidence does not automatically make a player a high-priority sale.
- Improved double-transfer ranking so recommendations consider both the quality of the incoming transfers and the weaknesses being removed from the existing squad.
- Improved double-transfer candidate generation to prevent excessive memory usage with real FPL data.
- Improved double-transfer result ordering using squad-aware scoring while preserving combination quality.
- Improved recommendation summaries to report the actual resulting squad bank.

### Testing
- Added automated squad-import testing.
- Added real-data squad-import diagnostics.
- Added squad transfer intelligence testing.
- Added real-data squad intelligence diagnostics.
- Added squad transfer optimizer testing.
- Added real-data squad transfer optimizer diagnostics.
- Added double-transfer optimizer testing.
- Added real-data double-transfer optimizer diagnostics.
- Added validation for squad structure and transfer legality.
- Added validation for transfer position preservation.
- Added validation for incoming-player uniqueness.
- Added validation for double-transfer affordability.
- Added validation for squad-aware transfer-priority handling.
- Added validation for sequential optimizer ranking.
- Verified double-transfer optimisation against the complete real FPL player pool.
- Verified optimizer runtime remains practical with real data.
- Complete regression suite passes successfully.

## [0.14.0] - Transfer Optipmizer

### Added
- Added Transfer Optimizer intelligence for automatically finding the strongest affordable two-transfer combinations.
- Added automatic same-position replacement pairing and affordability filtering.
- Added optimizer ranking based on combination classification, combination score, Intelligence movement and remaining budget.
- Added support for available bank when evaluating transfer combinations.
- Added Transfer Optimizer unit and real-data diagnostic coverage.
- Added `optimizeTransferCombination()` to `PlayerIntelligenceService`.
- Added lightweight optimizer candidate generation from player summaries to reduce full-profile loading.
- Added Transfer Optimizer frontend with outgoing-player selection, bank input and configurable recommendation limits.
- Added ranked optimizer result cards with individual transfer decisions and combined movement breakdowns.
- Added direct handoff from Transfer Optimizer results into the Transfer Planner.
- Added Transfer Optimizer navigation to the shared sidebar.

### Changed
- Improved Transfer Optimizer real-data performance by using the production service path and lightweight player summaries.
- Added per-test runtime reporting and slow-test summaries to `runAllTests.php`.
- Reduced complete test-suite runtime from roughly 100 seconds to roughly 36 seconds.

## [0.13.0] - Player Intelligence

### Added
- Added individual player intelligence profile page.
- Added player profile navigation from Player Explorer.
- Added complete player profile retrieval through PlayerIntelligenceService.
- Added player performance intelligence including totals and per-90 metrics.
- Added expected goals, assists and goal involvement metrics.
- Added confidence-adjusted performance component ratings.
- Added player availability rating and status display.
- Added fixture outlook with upcoming 10-fixture run.
- Added Next 5, Next 6, Next 8 and Next 10 opportunity averages.
- Added player-facing fixture opportunity trend analysis.
- Added best and toughest five-fixture opportunity runs.
- Added opportunity-specific fixture intelligence tests.
- Added PlayerIntelligenceService profile tests.
- Integrated PlayerAssessment into PlayerIntelligenceService player profiles.
- Added FPL Assessment decision-support section to individual player profiles.
- Added assessment verdicts, component labels, strengths, concerns, and generated player summaries.
- Added Player Profile Summary / FPL Decision Snapshot for quick player evaluation.
- Added snapshot metrics for intelligence score, price, strength, value, next-five fixture opportunity, availability, fixture trend, and performance sample confidence.
- Added responsive styling and verdict-specific presentation for player assessment and summary components.
- Added `PlayerComparison` service for direct player-to-player intelligence comparisons.
- Added reusable metric comparison logic covering:
  - Intelligence Score
  - Player Strength
  - Value
  - Fixture Rating
  - Availability
  - Sample Confidence
- Added metric winner, difference and win-count calculations.
- Overall comparison winner is determined by Player Intelligence Score rather than raw metric-win count.
- Removed duplicate Next 5 fixture comparison because Fixture Rating already represents the current five-fixture opportunity window.
- Added support for player assessment verdicts within comparison results.
- Added validation for missing players, invalid IDs and self-comparisons.
- Integrated player comparison into `PlayerIntelligenceService` through `comparePlayers()`.
- Added `PlayerComparisonTest.php` automated test coverage.
- Added `PlayerComparisonRealDataTest.php` diagnostic coverage using live imported FPL data.
- Validated comparison behaviour across premium, value and low-sample player scenarios.
- Added `/compare.php` player comparison interface.
- Added player selectors with preselected player support via query parameters.
- Added side-by-side player identity, price, position, team, assessment and Intelligence Score display.
- Added visual metric comparison with winning values highlighted.
- Added overall comparison result and Intelligence Score difference.
- Added supporting metric-win summary.
- Added Compare entry points from Player Explorer and individual player profiles.
- Added Compare to primary application navigation.
- Added responsive styling for the player comparison interface.
- Added `PlayerReplacement` service for identifying same-position replacement candidates.
- Added replacement filtering by:
  - player position
  - maximum replacement price
  - player availability
  - valid Intelligence Score
  - exclusion of the outgoing player
- Added deterministic replacement ranking using:
  - Player Intelligence Score
  - Player Strength
  - Value Rating
- Added Intelligence movement between outgoing and replacement players.
- Added price movement between outgoing and replacement players.
- Added replacement classifications:
  - Upgrade
  - Sidegrade
  - Downgrade
- Added replacement summary generation describing Intelligence movement and budget impact.
- Added assessment verdict support within replacement candidates.
- Added sample confidence visibility for replacement recommendations.
- Added `PlayerReplacementTest.php` automated test coverage.
- Added `PlayerReplacementRealDataTest.php` diagnostic coverage using imported FPL data.
- Validated replacement behaviour across premium, budget and low-sample player scenarios.
- Extended `getAllPlayerSummaries()` with assessment verdict and verdict key data.
- Added automated service tests for summary assessment data.
- Integrated `PlayerReplacement` into `PlayerIntelligenceService` through `findPlayerReplacements()`.
- Added service-level validation for invalid players, budgets and result limits.
- Added `transfers.php` Transfer Intelligence interface.
- Added player-to-sell selector, maximum replacement budget and result-limit controls.
- Added outgoing player summary with Intelligence, Strength, Value, Fixtures and assessment.
- Added ranked replacement candidate cards with:
  - Intelligence Score
  - Intelligence movement
  - price movement
  - Strength
  - Value
  - Fixtures
  - Availability
  - Sample Confidence
  - assessment verdict
  - replacement classification
- Added direct links from replacement candidates to Player Profile and Player Comparison.
- Added Transfer Intelligence to the primary application navigation.
- Added responsive Transfer Intelligence styling.
- Added `ReplacementRecommendation` intelligence layer for interpreting ranked replacement candidates.
- Added five replacement recommendation categories:
  - Best Overall
  - Best Value
  - Best Fixtures
  - Safest Pick
  - High Upside
- Added deterministic Intelligence-based tiebreakers for recommendation categories.
- Added confidence-aware Best Value selection with a minimum 25% performance sample.
- Added Safest Pick scoring using:
  - 40% sample confidence
  - 30% availability
  - 30% Player Intelligence
- Added High Upside scoring using:
  - 60% Player Intelligence
  - 25% Value
  - 15% Fixtures
- Added a 10% to below 75% sample-confidence window for High Upside candidates.
- Added support for returning no High Upside recommendation when no suitable candidate exists.
- Added `ReplacementRecommendationTest.php` automated test coverage.
- Added `ReplacementRecommendationRealDataTest.php` diagnostic coverage.
- Validated recommendation behaviour using real replacement searches for B.Fernandes, Haaland, Dowman and Gabriel.
- Integrated Replacement Recommendation Intelligence into `PlayerIntelligenceService::findPlayerReplacements()`.
- Extended replacement service results with Best Overall, Best Value, Best Fixtures, Safest Pick and High Upside recommendations.
- Extended `PlayerIntelligenceServiceTest.php` with recommendation contract and consistency checks.
- Added Recommendation Intelligence panel to Transfer Intelligence.
- Added recommendation cards displaying Intelligence, Value, Fixtures, Sample Confidence and assessment verdict.
- Added player profile and direct comparison actions from recommendation cards.
- Added graceful handling when no suitable recommendation exists for a category.
- Added responsive styling for Replacement Recommendation Intelligence.
- Added `TransferDecision` engine for evaluating direct FPL player transfers.
- Added weighted transfer decision scoring using Intelligence, strength, value, fixtures, sample confidence, and budget movement.
- Added transfer classifications:
  - Upgrade
  - Budget Enabler
  - Strategic Sidegrade
  - Sidegrade
  - Risky Punt
  - Downgrade
  - Insufficient Data
- Added safeguards preventing large Intelligence losses from being disguised by budget savings or value improvements.
- Added low-sample confidence handling for risky incoming players.
- Added human-readable transfer decision summaries.
- Added `evaluatePlayerTransfer()` to `PlayerIntelligenceService`.
- Integrated Transfer Decision Intelligence into replacement candidate results.
- Added Transfer Decision information to the Transfer Intelligence interface.
- Added decision score, Intelligence movement, and budget released information to replacement cards.
- Retained player assessment verdicts alongside transfer-specific recommendations.
- Added `TransferDecisionTest.php` covering classification, movement calculations, summaries, and missing-data handling.
- Added `TransferDecisionRealDataTest.php` for real-player calibration.
- Extended `PlayerIntelligenceServiceTest.php` to cover Transfer Decision integration and invalid player handling.
- Calibrated decision logic against real transfers including B.Fernandes → Saka, Gabriel → Frimpong, Haaland → Osula, and Dowman → Chiesa.
- All automated tests passing.
- Added Transfer Combination Intelligence for evaluating linked two-player transfer strategies.
- Added combined transfer scoring across Intelligence, Strength, Value, Fixtures, sample confidence and budget movement.
- Added combination classifications including Strong Improvement, Improvement, Risky Restructure, Downgrade and Unaffordable.
- Added affordability analysis for linked transfers.
- Added individual Transfer Decision results within combination evaluations.
- Added `evaluateTransferCombination()` to `PlayerIntelligenceService`.
- Added Transfer Combination unit and real-data diagnostic coverage.
- Added Transfer Planner interface for interactively evaluating two-transfer strategies.
- Added links from Transfer Planner results to player profiles and direct player comparisons.

### Changed
- Improved Player Explorer availability visibility.
- Extended FixtureIntelligence with player-facing opportunity analysis.
- Extended PlayerIntelligenceService with complete player profile data.
- Updated test runner to recognise both supported test success messages.
- Extended PlayerIntelligenceServiceTest coverage to validate PlayerAssessment data within complete player profiles.
- Reorganised the player profile layout into a clearer decision-making hierarchy:
  Player Hero → FPL Decision Snapshot → Core Ratings → FPL Assessment → Performance Intelligence → Fixture Intelligence.
- Added Transfer Planner navigation to the Transfers workflow.
- Extracted duplicated sidebar navigation into shared `public/includes/sidebar.php`.
- Updated application pages to use the shared sidebar include.
- Centralised active navigation state through `$activeNav`.
- Player profile pages now correctly retain Players as their active navigation section.

## [0.12.0] - Player Explorer

### Added

- Added `PlayerIntelligenceService` as the application-level player intelligence orchestration layer.
- Added reusable generation of complete player intelligence summaries from live repository data.
- Added reusable ranked-player retrieval through the existing `PlayerRanking` model.
- Added team fixture opportunity calculation for application-level player intelligence.
- Added Player Explorer page at `public/players.php`.
- Added complete FPL player browsing alongside intelligence-ranked player browsing.
- Added Ranked and All Players modes.
- Added live player search.
- Added Premier League team filtering.
- Added position filtering for GK, DEF, MID and FWD.
- Added maximum player price filtering.
- Added player availability filtering.
- Added minimum intelligence-score filtering.
- Added minimum value-rating filtering.
- Added combinable Player Explorer filters.
- Added clear-filter functionality.
- Added live visible-player result count.
- Added sortable Player Explorer columns.
- Added sorting by overall intelligence rank, player, team, position, price, strength, value, fixtures and intelligence score.
- Added persistent overall Player Intelligence rank to Player Explorer rows.
- Added Player Explorer summary cards for ranked-player count, top-rated player and top intelligence score.
- Added complete team names and short names to reusable player intelligence summaries.
- Added player minutes, sample-confidence and BPS-per-90 information to application-level player summaries for future player-detail functionality.

### Refactored

- Moved reusable player-intelligence orchestration out of individual public pages and into `PlayerIntelligenceService`.
- Reduced the need for dashboard and future application pages to understand the complete intelligence calculation pipeline.
- Standardised Player Explorer layout around the same application shell used by the main dashboard.
- Standardised the fixed sidebar, application content area, top bar and footer across dashboard-style pages.
- Updated Player Explorer to retain all players while separately identifying players with usable intelligence scores.
- Kept `PlayerRanking` as the single source of truth for overall player ranking order.
- Updated Player Explorer ranking so the `#` column represents true overall Intelligence rank rather than current table order.
- Refactored frontend styling into a consolidated high-contrast dark application theme.
- Integrated the previously tested accessibility/contrast colour improvements directly into the core stylesheet.
- Removed duplicated colour-scheme overrides.
- Removed obsolete player-strength calibration and positional-diagnostic CSS.
- Cleaned and reorganised Player Explorer, table, filter, navigation and responsive styles.

### Player Explorer

- Default Explorer view shows intelligence-ranked players.
- All Players mode allows new and zero-minute players to remain discoverable without forcing them into intelligence rankings.
- Players without sufficient performance evidence display missing intelligence data safely rather than receiving artificial rankings.
- Search, team, position, price, availability, intelligence and value filters can be combined.
- Maximum-price filtering operates directly against each player's live FPL price.
- Minimum intelligence filtering excludes players without a qualifying intelligence score.
- Minimum value filtering supports identification of strong value options.
- Player Explorer initially sorts by overall intelligence score.
- Numeric and text columns support ascending and descending sorting.
- Unavailable numeric ratings are safely handled during sorting.
- Player Explorer Top Rated and Top Score use the official `PlayerRanking` output and therefore remain consistent with the dashboard.

### Frontend

- Added full Player Explorer user interface.
- Added responsive Player Explorer filter controls.
- Added responsive player ranking table.
- Added position badges and rating colour indicators.
- Added brighter text, border, card and control contrast throughout the application.
- Improved readability of sidebar navigation, dashboard cards, tables and filter controls.
- Added hover and focus states for Explorer controls.
- Added active states for player-pool and position filters.
- Added table row hover states and alternating row separation.
- Preserved responsive sidebar/navigation behaviour.
- Consolidated application CSS to remove temporary override layers and development-only styles.

### Fixed

- Fixed Player Explorer content overlapping the fixed application sidebar.
- Fixed Player Explorer Top Rated player using the first unranked repository record rather than the highest intelligence-ranked player.
- Fixed initial Explorer table order not matching the active Intelligence sort indicator.
- Fixed maximum-price filtering not working because player rows were missing the required `data-price` value.
- Fixed Player Explorer rank numbers being derived from the unranked complete player list.
- Fixed Ranked Players summary displaying the total player count instead of the ranked-player count.
- Fixed Player Explorer heading and player-count wording to support both Ranked and All Players modes.
- Removed a duplicate PHP closing tag from `players.php`.
- Removed obsolete calibration styling remaining from Player Strength development.
- Removed duplicated legacy colour declarations superseded by the higher-contrast application palette.

### Architecture

- Public pages can now retrieve player intelligence through a dedicated service rather than reconstructing the intelligence pipeline themselves.
- `PlayerIntelligenceService` coordinates:
  - `PlayerRepository`
  - `TeamRepository`
  - `FixtureRepository`
  - `PlayerIntelligenceEngine`
  - `PlayerRanking`
  - `FixtureIntelligence`
  - `TeamStrength`
  - `TeamPerformance`
  - `TeamStrengthModel`
- The service architecture provides a reusable foundation for future:
  - player detail pages
  - transfer analysis
  - hidden-gem discovery
  - squad optimisation
  - wildcard optimisation
  - captain analysis

### Testing

- Verified the complete existing automated regression suite after introducing `PlayerIntelligenceService`.
- Verified existing Player Intelligence calculations remain unchanged.
- Verified existing Player Strength calibration remains unchanged.
- Verified dashboard player rankings remain valid.
- Verified Player Explorer uses the same official ranking model as the dashboard.
- Verified Ranked and All Players modes.
- Verified player search.
- Verified team filtering.
- Verified position filtering.
- Verified maximum-price filtering.
- Verified availability filtering.
- Verified minimum intelligence filtering.
- Verified minimum value filtering.
- Verified combined filters.
- Verified filter reset behaviour.
- Verified sortable Player Explorer columns.
- Verified missing player intelligence is handled safely.
- Verified responsive Dashboard and Player Explorer layouts.
- Verified consolidated CSS preserves the intended application appearance.
- Complete automated regression suite passes successfully.
- No test files fail.
- No test files produce errors.

## [0.11.8] - Player Intelligence & Strength Calibration

### Added

- Added player-facing fixture opportunity scoring based purely on opposition strength.
- Added rolling fixture opportunity averages for upcoming 5, 6, 8 and 10 fixture runs.
- Added player performance sample-confidence calculation based on minutes played.
- Added confidence-adjusted player performance ratings.
- Added BPS per 90 calculation and normalisation.
- Added core player intelligence score output.
- Added availability multiplier output to the complete player intelligence model.
- Added live Top 10 player intelligence rankings to the dashboard.

### Refactored

- Refactored overall PlayerIntelligenceScore weighting to use player strength at 65% and fixture opportunity at 35%.
- Removed player value as a direct contributor to overall player intelligence.
- Changed player availability from a weighted score component into a risk modifier.
- Updated fixture opportunity scoring so a team's own strength is not counted twice within player intelligence.
- Updated PlayerStrengthModel to consume confidence-adjusted performance ratings where available.
- Updated BPS performance rating from cumulative BPS to BPS per 90.
- Updated forward strength weighting so clean sheets no longer contribute to FWD strength.
- Reallocated the previous forward clean-sheet weighting to BPS.
- Updated player intelligence ranking so player strength is required before an overall intelligence score can be generated.
- Preserved value rating as a separate metric for future value-pick, transfer and squad optimisation features.
- Preserved the existing team matchup fixture score separately from player-facing fixture opportunity.

### Model

- Overall player intelligence now represents player quality combined with short-term fixture opportunity.
- Player strength contributes 65% of the core intelligence score.
- Fixture opportunity contributes 35% of the core intelligence score.
- Availability no longer rewards fully fit players with additional intelligence points.
- Fully available players receive no availability penalty.
- Availability concerns progressively reduce the core intelligence score through a multiplier.
- Missing availability information does not automatically penalise a player.
- Player value remains visible but does not directly influence overall player quality ranking.
- Player strength is mandatory for an overall intelligence score.
- Fixture opportunity alone cannot create a player intelligence score when no performance evidence exists.
- Missing fixture data remains supported by redistributing the core score fully to player strength.
- Small performance samples are pulled towards a neutral 50 rating rather than being treated with the same confidence as established samples.
- 900 minutes represents full performance-sample confidence.
- BPS is now evaluated on a per-90 basis for consistency with other performance metrics.
- Forwards no longer receive strength credit from clean sheets.

### Fixed

- Fixed value indirectly double-counting player strength within the overall intelligence score.
- Fixed fully available players receiving an automatic 20-point intelligence contribution.
- Fixed player fixture ratings double-counting the strength of the player's own team.
- Fixed zero-minute players being ranked highly solely because their team had favourable fixtures.
- Fixed very small per-90 samples being treated with the same confidence as established player performance.
- Fixed cumulative BPS being compared alongside per-90 attacking performance metrics.
- Fixed forwards receiving strength credit for clean sheets despite not receiving FPL clean-sheet points.
- Reduced small-sample inflation that previously caused low-minute players to rank disproportionately highly.

### Dashboard

- Replaced the placeholder Top Players card with live PlayerIntelligenceEngine rankings.
- Added live position, price, availability, strength, value, fixture opportunity and intelligence score information.
- Expanded the live player ranking from Top 5 to Top 10.
- Used the live dashboard output to validate player intelligence against real FPL data.
- Removed temporary deep performance-component and positional-strength diagnostic output after calibration was completed.

### Testing

- Updated PlayerIntelligenceScore regression tests for the calibrated 65/35 core weighting model.
- Added availability multiplier testing.
- Added validation that value does not directly alter overall intelligence.
- Added validation that player strength is required for an overall intelligence score.
- Added BPS per 90 integration testing.
- Updated PlayerIntelligenceEngine tests for the new BPS rating model.
- Verified missing fixture data remains supported.
- Verified zero-minute players cannot rank solely from fixture opportunity.
- Verified low-minute performance samples receive reduced confidence.
- Complete automated regression suite passes successfully.
- No test files fail.
- No test files produce errors.

## [0.11.7] - Repository & Runtime Structure Hardening

### Refactored

- Simplified `public/index.php` into a clean application entry point.
- Removed development-only team strength and fixture intelligence calculations from the public homepage.
- Removed unnecessary live FPL API calls from normal homepage requests.
- Updated the homepage to use the local database as the primary application data source.
- Updated `public/index.php` to use location-safe `__DIR__` autoload paths.
- Improved public application database error handling.
- Refactored `cron/updateFixtures.php` for command-line and scheduled execution.
- Changed fixture update output from HTML to cron-friendly plain text.
- Added transaction handling to fixture imports.
- Added malformed fixture validation and skipped-fixture reporting.
- Improved cron failure handling with `Throwable` support and non-zero failure exit codes.

### Removed

- Removed the unused empty `cron/updateBootstrap.php` script.
- Removed the unused empty `intelligence` directory.
- Removed the obsolete `database/version.txt` file.
- Removed the obsolete `database` directory after confirming schema versioning is already represented by the project changelog.

### Runtime

- Confirmed `cache/` is retained as a runtime cache directory.
- Confirmed `logs/` is retained as a runtime logging directory.
- Preserved `.gitkeep` files so runtime directories remain available in fresh repository clones.
- Confirmed generated cache and log contents are excluded from Git.
- Confirmed environment and local configuration files remain excluded from Git.
- Preserved the `assets/css`, `assets/images` and `assets/js` structure for future frontend development.

### Application

- Established `public/index.php` as the clean foundation for the future FPL Intelligence homepage/dashboard.
- Added database connectivity status to the application entry point.
- Added current team, player and fixture database counts to the application status page.
- Separated external FPL data synchronisation from normal public application requests.
- Established cron imports as the boundary between the FPL API and locally stored application data.

### Testing

- Complete automated regression suite passes successfully.
- No test files fail.
- No test files produce errors.
- Fixture update script executes successfully.
- FPL data update completes successfully.
- Public application entry point loads successfully.
- Database connection succeeds from the public application.
- Current team, player and fixture data are available through the application entry point.

## [0.11.6] - Player Intelligence Model Hardening

### Refactored

- Hardened PlayerPerformance input handling and performance metric calculations.
- Hardened PlayerStrengthModel position-specific weighting and rating calculations.
- Improved PlayerStrengthModel handling of missing and malformed performance ratings.
- Hardened PlayerValue strength-per-million and value rating calculations.
- Improved PlayerValue handling of missing, invalid and zero price data.
- Hardened PlayerAvailability availability and reliability calculations.
- Improved PlayerAvailability handling of malformed chance-of-playing, status and minutes data.
- Hardened PlayerIntelligenceScore component validation and weighted score calculations.
- Improved handling of missing intelligence components while preserving proportional weight redistribution.
- Hardened PlayerIntelligenceEngine integration across performance, strength, value, availability and intelligence models.
- Improved decision-friendly player intelligence summary consistency.
- Hardened PlayerIntelligence fixture-run and profile processing.
- Improved player identity and name resolution across supported player data structures.
- Added safer handling for malformed numeric strength and fixture-profile data.

### Fixed

- Fixed player intelligence models relying on potentially malformed numeric input.
- Fixed inconsistent player identity fallback behaviour within PlayerIntelligence.
- Fixed blank player names when alternative player-name fields are available.
- Fixed malformed rolling-average and fixture-profile data potentially producing invalid intelligence output.
- Clarified the distinction between the legacy fixture-focused PlayerIntelligence score and the modern PlayerIntelligenceScore model.

### Model

- PlayerPerformance remains the source of normalised player performance metrics.
- PlayerStrengthModel continues to use position-specific performance weighting.
- PlayerValue continues to measure player strength relative to FPL price.
- PlayerAvailability continues to combine current FPL availability with playing involvement.
- PlayerIntelligenceScore remains the primary overall player intelligence model.
- Overall player intelligence continues to weight strength at 35%, value at 25%, availability at 20% and fixtures at 20%.
- Missing intelligence components continue to redistribute available weighting proportionally.
- PlayerIntelligence remains responsible for fixture-run analysis and legacy fixture-focused profiling.
- PlayerIntelligenceEngine remains the primary coordinator of the complete modern player intelligence pipeline.
- Existing player intelligence scoring behaviour and weighting have been preserved.

### Testing

- Complete automated regression suite passes successfully.
- No test files fail.
- No test files produce errors.
- Existing player performance, strength, value, availability, fixture-intelligence and overall intelligence tests remain compatible.
- Existing ranking, recommendation and transfer-model integration remains compatible.
- Player fixture intelligence continues to use venue-aware team and opposition strength correctly.
- Full classes-layer regression suite remains green following player intelligence hardening.

## [0.11.5] - Team Intelligence & Fixture Model Hardening

### Refactored

- Hardened TeamStrength range and baseline calculations.
- Added safer handling for missing and malformed team strength data.
- Hardened TeamPerformance fixture processing and aggregate calculations.
- Added safer handling for malformed fixtures and missing performance fields.
- Improved recent-form handling and invalid limit behaviour.
- Hardened TeamStrengthModel weighting and combined rating calculations.
- Added baseline field validation to TeamStrengthModel.
- Preserved original baseline value types in returned team models.
- Hardened TeamPerformanceAdjusted opposition-adjustment processing.
- Added safer handling for missing team-strength models and incomplete fixtures.
- Hardened OppositionAdjustedPerformance input validation and fixture processing.
- Added explicit invalid-result handling.
- Hardened TeamStrengthHistoricalDecay against malformed and out-of-range performance values.
- Updated TeamIntelligence to support modern venue-aware team strength models.
- Preserved compatibility with legacy flat team-strength structures.
- Updated TeamFixtureProfile to prefer already-calculated fixture intelligence.
- Hardened legacy fixture-profile construction when strength data is incomplete.
- Hardened FixtureIntelligence calculations, fixture processing, rolling averages, run detection and trend analysis.
- Added support for nullable fixture gameweeks throughout team and fixture intelligence processing.

### Fixed

- Fixed missing team-strength data being silently converted into misleading strength values.
- Fixed malformed fixtures potentially producing undefined-key warnings.
- Fixed invalid recent-form limits producing unexpected results.
- Fixed historical-decay tests referencing a non-existent `average_performance` field.
- Removed PHP warnings from historical-decay regression testing.
- Fixed TeamStrengthModel output type changes that broke strict model-contract assertions.
- Fixed FixtureIntelligence baseline output type changes that broke player fixture-intelligence assertions.
- Fixed legacy TeamIntelligence logic overriding explicit venue-aware home and away strengths.

### Model

- TeamStrength continues to normalise FPL baseline ratings onto a 0-100 scale.
- TeamPerformance continues to combine points, goal difference, attack and defence ratings.
- TeamStrengthModel continues to progressively blend FPL baseline and actual performance as matches are played.
- Opposition-adjusted performance continues to compare actual results against opponent-strength expectations.
- Historical decay continues to weight recent performances more heavily than older performances.
- Fixture intelligence continues to use venue-aware team and opposition strength.
- TeamFixtureProfile continues to use the next five fixtures as its primary short-term fixture rating.
- TeamIntelligence continues to combine team strength and fixture rating without changing existing weighting behaviour.

### Testing

- Complete automated regression suite passes successfully.
- No test files fail.
- No test files produce errors.
- No PHP warnings remain in the historical-decay test path.
- Existing team-strength, venue, opposition, historical-decay, fixture-intelligence and player-fixture integration tests remain compatible.

## [0.11.4] - Database & Schema Hardening

### Added

- Added dedicated Database regression tests.
- Added validation of PDO connection behaviour.
- Added validation that PDO exception mode is enabled.
- Added validation that native prepared statements are enabled.
- Added validation of associative default fetch behaviour.
- Added validation of utf8mb4 connection encoding.
- Added database storage-engine validation.
- Added table collation validation.
- Added foreign-key validation.
- Added fixture gameweek nullability validation.
- Added player position column contract validation.
- Added environment-variable support for database and FPL API configuration.

### Refactored

- Simplified Database so it is responsible only for creating and exposing the PDO connection.
- Removed team lookup responsibility from Database in favour of TeamRepository.
- Updated PDO configuration to use native prepared statements.
- Updated PDO configuration to use associative fetch mode by default.
- Updated database connection encoding to utf8mb4.
- Updated configuration handling to support deployment-specific environment variables while preserving local development defaults.
- Expanded ignored environment configuration files.
- Updated FixtureRepository queries to use distinct named placeholders when native prepared statements are enabled.
- Updated database schema to use InnoDB instead of MyISAM.
- Updated database and table character sets to utf8mb4.
- Updated fixture gameweek storage to allow null values.
- Updated player position storage to match canonical `GK`, `DEF`, `MID` and `FWD` codes.

### Database

- Converted `teams` table to InnoDB.
- Converted `players` table to InnoDB.
- Converted `fixtures` table to InnoDB.
- Added `players.team_id -> teams.id` foreign key.
- Added `fixtures.home_team_id -> teams.id` foreign key.
- Added `fixtures.away_team_id -> teams.id` foreign key.
- Added cascading updates and restricted deletes for team relationships.
- Aligned live development database schema with the tracked `schema.sql` definition.

### Fixed

- Fixed transaction protection being ineffective because core tables were using MyISAM.
- Fixed duplicate named SQL placeholders that were incompatible with native prepared statements.
- Fixed schema encoding mismatch between the PDO connection and database tables.
- Fixed fixture gameweek schema not supporting legitimately unscheduled FPL fixtures.
- Removed duplicate team lookup logic from Database.

### Testing

- Verified Database can create a valid PDO connection.
- Verified exception error mode is enabled.
- Verified emulated prepares are disabled.
- Verified associative default fetch mode.
- Verified utf8mb4 connection encoding.
- Verified `teams`, `players` and `fixtures` tables exist.
- Verified all repositories accept the shared PDO connection.
- Verified all core tables use InnoDB.
- Verified all core tables use utf8mb4.
- Verified all required foreign-key relationships exist.
- Verified fixture gameweek supports null values.
- Verified player position column supports canonical position codes.
- Complete automated regression suite passes with no failures or errors.

## [0.11.3] - Repository, API & Data Import Hardening

### Added

- Added comprehensive PlayerRepository regression tests.
- Added comprehensive TeamRepository regression tests.
- Added comprehensive FixtureRepository regression tests.
- Added FPL API integration tests.
- Added FPL data import validation tests.
- Added repository lookup methods for local and FPL identifiers.
- Added finished-fixture lookup support for individual teams.
- Added defensive limit handling for repository queries.
- Added post-import player position contract validation.
- Added live database compatibility testing between PlayerRepository and PlayerPerformance.
- Added live database compatibility testing through the complete PlayerIntelligenceEngine.
- Added validation that imported player positions use the canonical `GK`, `DEF`, `MID` and `FWD` values.
- Added validation that real imported player data can produce a complete intelligence profile and decision-friendly summary.

### Refactored

- Hardened PlayerRepository so player queries return complete player records suitable for downstream intelligence models.
- Hardened TeamRepository with consistent local-ID and FPL-ID lookup behaviour.
- Hardened FixtureRepository with local-ID, FPL-ID and team-specific fixture retrieval.
- Improved FixtureRepository handling of invalid and zero query limits.
- Improved FPLApi response validation and error handling.
- Refactored FPL data importing to use the canonical player position contract.
- Improved player and team import/update behaviour.
- Improved import failure handling to protect database consistency.
- Aligned repository output with the normalised data structures expected by the intelligence layer.

### Fixed

- Fixed imported player positions being stored as raw FPL element-type identifiers instead of `GK`, `DEF`, `MID` and `FWD`.
- Fixed repository methods returning incomplete records that could not safely feed downstream intelligence models.
- Fixed fixture repository edge cases around invalid limits.
- Fixed data import behaviour that could leave inconsistent data when an import failed.
- Fixed compatibility between live imported database records and the PlayerIntelligenceEngine.

### Data Validation

- Successfully executed a live FPL data update.
- Imported 20 Premier League teams.
- Imported 581 FPL players.
- 0 players were skipped.
- Verified all imported players use valid intelligence-compatible positions.
- Verified the database contains goalkeepers, defenders, midfielders and forwards.
- Verified a real imported player can pass through PlayerPerformance.
- Verified a real imported player can pass through the complete PlayerIntelligenceEngine.
- Verified player, FPL player and team identity are preserved through the intelligence pipeline.
- Verified intelligence scores generated from live database data remain within the 0-100 range.

### Testing

- Added PlayerRepositoryTest coverage for retrieval, lookup, ordering, limits and intelligence compatibility.
- Added TeamRepositoryTest coverage for retrieval, lookup, ordering and team-strength compatibility.
- Added FixtureRepositoryTest coverage for retrieval, fixture relationships, upcoming fixtures, completed fixtures, limits and intelligence compatibility.
- Added FPLApiTest coverage for live bootstrap and fixture API responses.
- Added FPLDataImportTest coverage for imported database integrity and FPL data compatibility.
- Added post-import regression tests against the live database.
- Complete automated regression suite passes successfully with no failures or errors.

## [0.11.2] - Player Intelligence Data Structure Refactor

### Refactored

- Standardised downstream player models around the structured `PlayerIntelligenceEngine` output.
- Updated `PlayerRanking` to consume player intelligence summary data.
- Updated `PlayerRecommendation` to consume player intelligence summary data.
- Updated `TransferRecommendation` to consume player intelligence summary data.
- Updated `TransferTargetFinder` to consume player intelligence summary data.
- Added backwards-compatible handling for legacy flat player intelligence arrays where required.
- Reduced reliance on the older flat player data structure.
- Improved consistency of player identity, ratings and intelligence data between downstream models.
- Standardised PHP class filenames to match their class names and improve compatibility with case-sensitive environments.

### Removed

- Removed the obsolete `PlayerModel` class.
- Removed the obsolete `PlayerModelTest`.
- Removed the obsolete `PlayerPerformanceModel` class and associated test after confirming the functionality is provided by the current `PlayerPerformance` model.

### Improved

- Player ranking now works directly with the decision-friendly summary generated by `PlayerIntelligenceEngine`.
- Player recommendations can be generated directly from complete player intelligence profiles.
- Transfer recommendations can compare players using the standardised intelligence summary structure.
- Transfer target discovery can consume standardised player intelligence profiles.
- Player identity and rating fields are now handled more consistently throughout the recommendation and transfer pipeline.
- Improved compatibility with Linux and other case-sensitive production environments by matching filenames to PHP class names.

### Testing

- Expanded `PlayerIntelligenceEngineTest` coverage for the standardised summary structure.
- Expanded `PlayerRankingTest` coverage for PlayerIntelligenceEngine summary integration.
- Expanded `PlayerRecommendationTest` coverage for PlayerIntelligenceEngine summary integration.
- Expanded `TransferRecommendationTest` coverage for PlayerIntelligenceEngine summary integration.
- Expanded `TransferTargetFinderTest` coverage for PlayerIntelligenceEngine summary integration.
- Verified legacy flat player intelligence data remains supported where required.
- Verified player ranking continues to operate correctly.
- Verified player recommendation logic continues to operate correctly.
- Verified transfer recommendation logic continues to operate correctly.
- Verified transfer target discovery continues to operate correctly.
- Verified obsolete PlayerModel removal does not break the application.
- Verified renamed class files continue to autoload correctly.
- Complete automated regression test suite passes with 0 failed test files and 0 errors.

## [0.11.1] - Automated Regression Test Runner

### Added

- Added a complete automated test suite runner.
- Added automatic discovery of all `*Test.php` files in the tests directory.
- Added isolated PHP CLI execution for each test file.
- Added protection against duplicate test helper function and class declarations.
- Added automatic WAMP PHP CLI discovery.
- Added aggregate test-file pass, fail and error reporting.
- Added aggregate assertion pass and fail reporting.
- Added a single browser-accessible regression test entry point.

### Fixed

- Updated PlayerAvailability test data to use the normalised player model field structure.
- Corrected player ID field usage from `id` to `player_id`.
- Corrected player name field usage from `web_name` to `name`.

### Testing

- 28 test files passed.
- 0 test files failed.
- 0 test files produced errors.
- 902 assertions passed.
- 0 assertions failed.
- Complete project regression suite passes successfully.

## [0.11.0] - Front-End Friendly Player Intelligence Engine

### Added

- Added PlayerIntelligenceEngine to combine player analysis models into a single complete player intelligence profile
- Added unified player identity information across performance, strength, value, availability and intelligence models
- Added front-end-friendly player intelligence output structure
- Added complete player performance model integration
- Added complete player strength model integration
- Added complete player value model integration
- Added complete player availability model integration
- Added fixture intelligence integration into the overall player intelligence score
- Added support for analysing players when fixture intelligence is unavailable
- Added cross-model identity consistency validation
- Added BPS performance rating calculation
- Added BPS position-specific benchmark normalisation
- Added complete player profile output containing player, performance, strength, value, availability and intelligence sections

### Model

- Player identity is preserved consistently across all intelligence models
- Player performance provides the underlying statistical and per-90 metrics
- Player performance ratings are normalised against position-specific benchmarks
- BPS is included as a normalised performance rating
- Player strength receives the normalised performance ratings
- Player value receives the calculated player strength rating
- Player availability provides availability and reliability ratings
- Fixture intelligence is incorporated into the overall player intelligence score when available
- Missing fixture intelligence does not prevent a player intelligence score from being calculated
- Intelligence scores remain constrained to the 0–100 range
- Complete player profiles provide a consistent structure suitable for future front-end consumption

### Testing

- Verified PlayerIntelligenceEngine returns a valid array
- Verified player identity is preserved
- Verified FPL player ID is preserved
- Verified team ID is preserved
- Verified player name and position are preserved
- Verified performance model integration
- Verified performance minutes, goals and assists are preserved
- Verified goals per 90 calculation
- Verified assists per 90 calculation
- Verified expected goals per 90 calculation
- Verified expected assists per 90 calculation
- Verified expected goal involvements per 90 calculation
- Verified clean sheets per 90 calculation
- Verified goals, assists, expected goals, expected assists and clean sheet ratings
- Verified BPS rating calculation
- Verified player strength model integration
- Verified strength rating remains within the 0–100 range
- Verified player value model integration
- Verified strength-per-million calculation
- Verified value rating remains within the 0–100 range
- Verified value label generation
- Verified player availability model integration
- Verified availability and reliability ratings
- Verified availability label generation
- Verified intelligence score integration
- Verified strength, value, availability and fixture ratings are correctly connected
- Verified intelligence score remains within the 0–100 range
- Verified intelligence label generation
- Verified cross-model player identity consistency
- Verified engine operates correctly when fixture rating is unavailable
- Verified intelligence score remains valid when fixture rating is unavailable
- Verified complete player profile structure
- Verified front-end-friendly player intelligence output
- **69 tests passed**
- **0 tests failed**


## 0.10.0 - Transfer Target Finder

### Added

- Added TransferTargetFinder model.
- Added automatic transfer target discovery.
- Added same-position filtering.
- Added current-player exclusion.
- Added transfer budget filtering.
- Added intelligence score requirements.
- Added availability filtering.
- Integrated TransferRecommendation into transfer target selection.
- Added transfer target ranking.
- Added intelligence score tie-breaking.
- Added configurable top-N transfer target selection.
- Added comprehensive TransferTargetFinder tests.

### Testing

- 27 transfer target tests passed.
- 0 tests failed.

## [0.9.0] - Player Intelligence Scoring

### Added

- Added PlayerIntelligenceScore model
- Added overall player intelligence scoring
- Added position-independent intelligence weighting across player strength, value, availability and fixtures
- Added player strength weighting at 35%
- Added player value weighting at 25%
- Added player availability weighting at 20%
- Added fixture intelligence weighting at 20%
- Added proportional redistribution of weighting when intelligence components are unavailable
- Added 0–100 intelligence score normalisation
- Added human-readable intelligence score labels
- Added complete player intelligence score model combining all intelligence components

### Model

- Player strength is the largest component of the overall intelligence score
- Player value provides the second-largest weighting
- Player availability contributes to the overall reliability of the player
- Fixture rating contributes to the player's short-term opportunity
- Missing intelligence components do not invalidate the overall score
- Available component weightings are redistributed proportionally when data is unavailable
- Intelligence ratings are constrained to the standard 0–100 scale
- Intelligence score labels range from Elite to Weak

### Testing

- Verified intelligence weight configuration
- Verified all intelligence weights total 100%
- Verified standard weighted intelligence score calculation
- Verified perfect player produces a 100 intelligence score
- Verified poor player produces a 0 intelligence score
- Verified intelligence score ordering
- Verified individual component influence on the overall score
- Verified missing component weighting redistribution
- Verified completely missing intelligence data handling
- Verified intelligence score bounds
- Verified intelligence score labels
- Verified complete player intelligence model structure
- Verified player identity and position consistency
- Verified all intelligence components are correctly connected
- Verified front-end-friendly intelligence output
- Verified 37 tests with 0 failures

## [0.8.0] - Complete Player Intelligence Model

### Added

- Added PlayerPerformance model for analysing individual player statistics
- Added per-90 performance metrics for goals, assists, expected goals, expected assists, expected goal involvements and clean sheets
- Added PlayerStrengthModel for calculating position-specific player strength ratings
- Added position-specific weighting for GK, DEF, MID and FWD players
- Added normalised player performance ratings across key metrics
- Added missing-metric handling with proportional weight redistribution
- Added PlayerValue model for calculating player strength per £1m
- Added player value ratings and value labels
- Added PlayerAvailability model for calculating availability ratings from FPL chance-of-playing data
- Added status-based availability fallback
- Added player reliability ratings using playing minutes
- Added availability labels
- Added PlayerIntelligence for analysing player fixture runs
- Added rolling fixture averages
- Added best and worst fixture-run analysis
- Added fixture trend analysis
- Added PlayerModel for combining performance, strength, value and availability data into a complete player model

### Model

- Player performance is normalised into a consistent structure before intelligence calculations are performed
- Per-90 metrics are used to account for differences in playing time
- Player strength ratings use position-specific metric weightings
- Player strength ratings are constrained to the 0–100 scale
- Missing performance metrics are excluded and their weighting redistributed across available metrics
- Player value is calculated from strength rating relative to player price
- Value ratings are constrained to the 0–100 scale
- Player availability uses FPL chance-of-playing data when available
- FPL status is used as a fallback when explicit chance-of-playing data is unavailable
- Reliability combines current availability with demonstrated playing involvement
- Player fixture intelligence uses the player's team to analyse upcoming opposition and venue context
- Player models preserve consistent player identity and position information across all analysis components

### Testing

- Verified PlayerPerformance model structure and player data preservation
- Verified per-90 performance calculations
- Verified expected-statistic per-90 calculations
- Verified zero-minute handling
- Verified missing optional player data handling
- Verified PlayerStrengthModel position-specific weighting
- Verified player strength ratings remain within the 0–100 range
- Verified perfect and poor player strength ratings
- Verified missing metric handling
- Verified all position weightings total 1.00
- Verified PlayerValue strength-per-million calculations
- Verified value rating calculations and 0–100 capping
- Verified value labels across all rating ranges
- Verified missing price and strength handling
- Verified PlayerAvailability chance-of-playing calculations
- Verified status-based availability calculations
- Verified availability bounds and missing data handling
- Verified reliability calculations using playing minutes
- Verified availability labels
- Verified PlayerIntelligence fixture-run analysis
- Verified home and away fixture handling
- Verified opposition strength calculations
- Verified rolling fixture averages
- Verified best and worst fixture runs
- Verified fixture trend analysis
- Verified missing team ID handling
- Verified empty fixture-run handling
- Verified complete PlayerModel integration
- Verified performance, strength, value and availability models remain connected
- Verified player identity and position remain consistent across the complete player model
- Verified front-end-friendly test output for the complete player model
- All Sprint 0.8 tests passed successfully


## [0.7.0] - Team Strength Historical Decay & Fixture Intelligence

### Added

- Added TeamStrengthHistoricalDecay for recency-weighted historical performance
- Added configurable historical decay factor
- Added progressive weighting where recent matches have greater influence than older matches
- Added historical performance integration into the team strength model
- Added opposition-adjusted performance analysis
- Added opposition strength delta calculations
- Added venue-aware opposition strength calculations
- Added recovery behaviour following poor historical results
- Added support for historical performance progression over multiple matches
- Added fixture difficulty analysis based on team and opposition strength
- Added fixture score ordering based on opposition strength
- Added venue-aware fixture scoring
- Added rolling fixture average calculations
- Added best fixture run detection
- Added worst fixture run detection
- Added fixture trend analysis

### Model

- Historical performance is weighted according to match recency
- The most recent completed match receives a weight of 1.00
- Older matches receive progressively lower weights
- Historical decay uses a configurable default factor of 0.90
- Strong performances against stronger opposition receive a positive adjustment
- Poor performances against weaker opposition receive a negative adjustment
- Home fixtures use the opposition's away strength
- Away fixtures use the opposition's home strength
- FPL baseline strength remains the foundation of the team rating
- Actual performance progressively influences the team rating as competitive matches accumulate
- Fixture difficulty is derived from the relative strength of the team and its opponent

### Refactored

- Updated TeamStrengthModel to use TeamPerformance as the source of performance ratings
- Updated FixtureIntelligence to consume complete team strength models
- Updated fixture analysis to use model-based home and away team strengths
- Removed temporary historical decay and fixture intelligence test output from index.php
- Kept index.php focused on application health checks and core application output

### Testing

- Verified historical decay weight progression
- Verified expected decay values
- Verified recent results have greater influence than older results
- Verified identical histories produce identical results
- Verified single-match performance handling
- Verified empty historical performance handling
- Verified custom decay factors
- Verified strong-opposition wins produce positive performance adjustments
- Verified weak-opposition losses produce negative performance adjustments
- Verified historical decay integration with TeamPerformance
- Verified venue-aware opposition strength calculations
- Verified historical recovery behaviour
- Verified ten-match historical progression
- Verified team strength model integration
- Verified fixture intelligence integration with complete team models
- Verified fixture difficulty ordering
- Verified fixture score ordering
- Verified difficulty bounds
- Verified home and away fixture handling
- Verified strong opposition produces increased fixture difficulty
- Verified venue reversal affects fixture scoring
- Verified fixture ordering by gameweek
- Verified all current TeamStrength and FixtureIntelligence integration tests pass

## [0.6.0] - Team Performance & Dynamic Strength Model

### Added

- Added TeamPerformance analysis for completed fixtures
- Added team wins, draws, losses and points calculations
- Added home and away performance statistics
- Added goals for, goals against and goal difference calculations
- Added recent-form tracking
- Added points-per-game calculation
- Added goals-per-game calculation
- Added goals-against-per-game calculation
- Added points performance rating
- Added goal-difference performance rating
- Added attacking performance rating
- Added defensive performance rating
- Added combined team performance rating
- Added TeamStrengthModel for combining FPL baseline strength with actual performance
- Added progressive baseline/performance weighting based on matches played
- Added combined home, away and overall team strength ratings

### Model

- FPL baseline strength is used as the primary rating before competitive matches are available
- Actual team performance progressively influences the rating as matches are played
- Baseline weighting decreases as the number of completed matches increases
- Performance weighting increases correspondingly as more real-world results become available
- Team strength remains based on separate home and away baselines
- Combined ratings are constrained by the underlying 0–100 rating system

### Testing

- Verified TeamPerformance returns a consistent structure when no fixtures have been completed
- Verified finished fixture filtering
- Verified home and away performance calculations
- Verified points calculations
- Verified goal difference calculations
- Verified performance rating components
- Verified combined performance rating
- Verified baseline weighting at different numbers of completed matches
- Verified TeamStrengthModel with zero completed fixtures
- Verified all 20 teams can be processed through the complete team model
- Verified teams retain their baseline ratings when no completed fixtures are available
- Removed temporary team performance and strength model test output from index.php

## [0.5.0] - Fixture Analysis Run

### Added
- Added individual team fixture-run analysis to FixtureIntelligence
- Added support for analysing a team's upcoming fixtures
- Added home and away context to fixture-run analysis
- Added team-specific matchup calculations
- Added team-specific fixture difficulty ratings
- Added fixture run output containing:
- Gameweek
- Home team
- Away team
- Home baseline
- Away baseline
- Home/away status
- Matchup score
- Difficulty rating

### Refactored
- Moved fixture-run analysis out of index.php and into FixtureIntelligence
- Updated fixture processing to use the selected team's home or away strength appropriately
- Removed temporary fixture-run testing output from index.php
- Cleaned index.php so it now contains only basic application health checks

### Testing
- Verified Arsenal's first five fixtures
- Verified home fixture matchup calculations
- Verified away fixture matchup calculations
- Verified home/away status detection
- Verified fixture difficulty calculations within fixture runs
- Verified fixture-run results against existing matchup calculations
- Verified index.php loads correctly after removal of development tests

## [0.4.0] - Fixture Difficulty Scoring

### Added
- Added fixture difficulty calculation to FixtureIntelligence
- Added normalised 1–5 fixture difficulty rating
- Added difficulty output to fixture intelligence results
- Retained raw matchup score alongside difficulty rating

### Testing
- Verified matchup scores remain unchanged
- Verified difficulty conversion from 1–5
- Verified fixture intelligence output using live fixture data
- Removed temporary difficulty calculation tests after verification

## [0.3.0] - Team Strength & Fixture Intelligence

### Added
- Added fixture retrieval through FixtureRepository
- Added team strength calculation through TeamStrength
- Added home and away team strength baselines
- Added overall team strength baseline
- Added fixture matchup calculations using team strength
- Added fixture intelligence test output

### Refactored
- Moved team strength calculations out of index.php
- Removed repeated team searching during fixture processing
- Updated fixture processing to use team IDs for direct team strength lookups
- Reused calculateOverall() for combined team strength calculations

### Testing
- Verified 20 teams are loaded
- Verified 380 fixtures are loaded
- Verified 380 unique FPL fixtures
- Verified team strength baselines
- Verified fixture matchup calculations
- Verified existing fixture intelligence results after refactoring

## [0.2.0] - 11-08-26

### Added
- FPL fixture API integration
- FixtureRepository
- TeamRepository
- Fixture import/update script
- Fixture-to-team database relationships
- Fixture difficulty and kickoff data storage
- Fixture upsert support to prevent duplicates

### Database
- Created fixtures table
- Added unique index for FPL fixture IDs
- Added indexes for gameweek, teams and kickoff time
- Added foreign key relationships to the teams table

### Testing
- Verified FPL fixture API connectivity
- Verified 380 fixtures received from the FPL API
- Verified all 380 fixtures imported into the database
- Verified 380 unique FPL fixture IDs
- Verified home and away team relationships
- Verified fixture upsert behaviour
- Verified fixture difficulty and kickoff data

## [0.1.0] - Foundation Complete

### Added
- Initial project folder structure
- Git repository
- Database configuration
- Database connection class
- FPL API integration
- Team importer
- Player importer
- Autoloading
- PlayerRepository
- Initial database queries

### Database
- Created `teams` table
- Created `players` table
- Added indexes for FPL IDs
- Implemented UPSERT imports

### Testing
- Verified database connectivity
- Verified API connectivity
- Verified team imports
- Verified player imports
- Verified repository queries