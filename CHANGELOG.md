# FPL Intelligence Changelog

All notable changes to this project will be documented in this file.

The project follows a sprint-based development process.

---

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