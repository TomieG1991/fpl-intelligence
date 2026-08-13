# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

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