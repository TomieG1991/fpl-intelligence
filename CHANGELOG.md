# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

## [0.3.0] - eam Strength & Fixture Intelligence

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