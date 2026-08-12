# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

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