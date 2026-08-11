# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

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