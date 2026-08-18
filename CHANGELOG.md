# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

## [0.13.0] - In Progress

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

### Changed
- Improved Player Explorer availability visibility.
- Extended FixtureIntelligence with player-facing opportunity analysis.
- Extended PlayerIntelligenceService with complete player profile data.
- Updated test runner to recognise both supported test success messages.
- Extended PlayerIntelligenceServiceTest coverage to validate PlayerAssessment data within complete player profiles.
- Reorganised the player profile layout into a clearer decision-making hierarchy:
  Player Hero → FPL Decision Snapshot → Core Ratings → FPL Assessment → Performance Intelligence → Fixture Intelligence.

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