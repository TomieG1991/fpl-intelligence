<?php

class PlayerIntelligenceService
{
    private PlayerRepository $playerRepository;

    private TeamRepository $teamRepository;

    private FixtureRepository $fixtureRepository;

    private PlayerIntelligenceEngine $playerEngine;

    private PlayerRanking $playerRanking;

    private FixtureIntelligence $fixtureIntelligence;

    private TeamStrength $teamStrength;

    private TeamPerformance $teamPerformance;

    private TeamStrengthModel $teamStrengthModel;
    
    private PlayerAssessment $playerAssessment;
    
    private PlayerComparison $playerComparison;
    
    private PlayerReplacement $playerReplacement;
    
    private ReplacementRecommendation $replacementRecommendation;
    
    private TransferDecision $transferDecision;
    
    private TransferCombination $transferCombination;
    
    private TransferOptimizer $transferOptimizer;


    /**
     * Build the application-level player intelligence service.
     *
     * This service coordinates repository data and the
     * intelligence models so public pages do not need to
     * duplicate the complete calculation pipeline.
     */
    public function __construct(
        PDO $db
    ) {

        /*
         * --------------------------------------------------------
         * REPOSITORIES
         * --------------------------------------------------------
         */

        $this->playerRepository =
            new PlayerRepository(
                $db
            );


        $this->teamRepository =
            new TeamRepository(
                $db
            );


        $this->fixtureRepository =
            new FixtureRepository(
                $db
            );


        /*
         * --------------------------------------------------------
         * PLAYER INTELLIGENCE
         * --------------------------------------------------------
         */

        $this->playerEngine =
            new PlayerIntelligenceEngine(

                new PlayerPerformance(),

                new PlayerStrengthModel(),

                new PlayerValue(),

                new PlayerAvailability(),

                new PlayerIntelligenceScore()
            );


        $this->playerRanking =
            new PlayerRanking();        
        
        $this->playerAssessment =
            new PlayerAssessment();
            
        $this->playerComparison =
            new PlayerComparison();
            
        $this->playerReplacement =
            new PlayerReplacement();
            
        $this->replacementRecommendation =
            new ReplacementRecommendation();
            
        $this->transferDecision =
            new TransferDecision();
            
        $this->transferCombination =
            new TransferCombination();
            
        $this->transferOptimizer =
            new TransferOptimizer();


        /*
         * --------------------------------------------------------
         * TEAM / FIXTURE INTELLIGENCE
         * --------------------------------------------------------
         */

        $this->fixtureIntelligence =
            new FixtureIntelligence();


        $this->teamStrength =
            new TeamStrength();


        $this->teamPerformance =
            new TeamPerformance();


        $this->teamStrengthModel =
            new TeamStrengthModel();
    }


    /**
     * Build intelligence summaries for every player
     * currently stored in the database.
     *
     * Players without sufficient performance evidence
     * are retained but may have a null intelligence score.
     */
    public function getAllPlayerSummaries(): array
    {
        $players =
            $this->playerRepository
                ->getAll();


        $teams =
            $this->teamRepository
                ->getAll();


        $fixtures =
            $this->fixtureRepository
                ->getAll();


        /*
         * --------------------------------------------------------
         * TEAM LOOKUP
         * --------------------------------------------------------
         */

        $teamLookup =
            [];


        foreach ($teams as $team) {

            if (!isset($team['id'])) {
                continue;
            }


            $teamLookup[
                (int) $team['id']
            ] = $team;
        }


        /*
         * --------------------------------------------------------
         * FIXTURE OPPORTUNITY
         * --------------------------------------------------------
         */

        $teamFixtureRatings =
            $this->buildTeamFixtureRatings(
                $teams,
                $fixtures
            );
            
        $teamNextFixtureRatings =
            $this->buildTeamNextFixtureRatings(
                $teams,
                $fixtures
            );
            
        /*
         * --------------------------------------------------------
         * POSITION-AWARE NEXT-FIXTURE SUPPORT
         * --------------------------------------------------------
         *
         * Build:
         *
         * - the next opponent for each Premier League team
         * - current opponent Attack / Defence Ratings
         *
         * Position-aware Fixture Intelligence is deliberately
         * applied only to the immediate fixture.
         */

        $teamNextOpponentIds =
            [];


        foreach (
            $teams
            as $team
        ) {

            $teamId =
                (int) (
                    $team[
                        'id'
                    ]
                    ?? 0
                );


            if (
                $teamId <= 0
            ) {

                continue;
            }


            $upcomingFixtures =
                $this->fixtureRepository
                    ->getUpcomingForTeam(
                        $teamId,
                        1
                    );


            $nextFixture =
                $upcomingFixtures[
                    0
                ]
                ?? null;


            if (
                !is_array(
                    $nextFixture
                )
            ) {

                $teamNextOpponentIds[
                    $teamId
                ] =
                    null;

                continue;
            }


            $homeTeamId =
                (int) (
                    $nextFixture[
                        'home_team_id'
                    ]
                    ?? 0
                );


            $awayTeamId =
                (int) (
                    $nextFixture[
                        'away_team_id'
                    ]
                    ?? 0
                );


            if (
                $homeTeamId === $teamId
            ) {

                $teamNextOpponentIds[
                    $teamId
                ] =
                    $awayTeamId > 0
                        ? $awayTeamId
                        : null;

            } elseif (
                $awayTeamId === $teamId
            ) {

                $teamNextOpponentIds[
                    $teamId
                ] =
                    $homeTeamId > 0
                        ? $homeTeamId
                        : null;

            } else {

                $teamNextOpponentIds[
                    $teamId
                ] =
                    null;
            }
        }


        /*
         * Team Intelligence already owns Attack / Defence
         * performance ratings, so reuse those outputs rather
         * than rebuilding TeamPerformance calculations here.
         */

        $teamIntelligenceSummaries =
            $this->getAllTeamIntelligenceSummaries();


        $teamAttackDefenceLookup =
            [];


        foreach (
            $teamIntelligenceSummaries
            as $teamSummary
        ) {

            $summaryTeamId =
                (int) (
                    $teamSummary[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $summaryTeamId <= 0
            ) {

                continue;
            }


            $teamAttackDefenceLookup[
                $summaryTeamId
            ] = [

                'attack_rating' =>
                    isset(
                        $teamSummary[
                            'attack_rating'
                        ]
                    )
                    &&
                    is_numeric(
                        $teamSummary[
                            'attack_rating'
                        ]
                    )
                        ? (float) $teamSummary[
                            'attack_rating'
                        ]
                        : null,

                'defence_rating' =>
                    isset(
                        $teamSummary[
                            'defence_rating'
                        ]
                    )
                    &&
                    is_numeric(
                        $teamSummary[
                            'defence_rating'
                        ]
                    )
                        ? (float) $teamSummary[
                            'defence_rating'
                        ]
                        : null
            ];
        }


        /*
         * --------------------------------------------------------
         * PLAYER INTELLIGENCE
         * --------------------------------------------------------
         */

        $summaries =
            [];


        foreach ($players as $player) {

            $teamId =
                (int) (
                    $player['team_id']
                    ?? 0
                );


            $fixtureRating =
                $teamFixtureRatings[$teamId]
                ?? null;
                
            $nextFixtureRating =
                $teamNextFixtureRatings[
                    $teamId
                ]
                ?? null;


            /*
             * --------------------------------------------------------
             * POSITION-AWARE IMMEDIATE FIXTURE
             * --------------------------------------------------------
             */

            $position =
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $nextOpponentId =
                $teamNextOpponentIds[
                    $teamId
                ]
                ?? null;


            $opponentAttackRating =
                null;


            $opponentDefenceRating =
                null;


            if (
                $nextOpponentId !== null
                &&
                isset(
                    $teamAttackDefenceLookup[
                        $nextOpponentId
                    ]
                )
            ) {

                $opponentAttackRating =
                    $teamAttackDefenceLookup[
                        $nextOpponentId
                    ][
                        'attack_rating'
                    ]
                    ?? null;


                $opponentDefenceRating =
                    $teamAttackDefenceLookup[
                        $nextOpponentId
                    ][
                        'defence_rating'
                    ]
                    ?? null;
            }


            /*
             * No upcoming fixture means there is no player-facing
             * immediate fixture opportunity to calculate.
             */

            $positionAwareFixtureRating =
                $nextFixtureRating !== null
                &&
                is_numeric(
                    $nextFixtureRating
                )
                    ? $this->fixtureIntelligence
                        ->calculatePositionAwareOpportunity(
                            (float) $nextFixtureRating,
                            $position,
                            $opponentAttackRating,
                            $opponentDefenceRating
                        )
                    : null;


            try {

                $profile =
                    $this->playerEngine
                        ->analysePlayer(
                            $player,
                            $fixtureRating
                        );


                $summary =
                    $profile['summary']
                    ?? [];

                /*
                 * Preserve the original team-level immediate fixture
                 * opportunity for transparency.
                 */

                $summary['base_next_fixture_rating'] =
                    $nextFixtureRating;


                /*
                 * Expose the new explicit player-facing fixture metric.
                 */

                $summary['position_aware_fixture_rating'] =
                    $positionAwareFixtureRating;


                /*
                 * Downstream Gameweek and Captain Intelligence already
                 * consume next_fixture_rating.
                 *
                 * Make the position-aware value authoritative while
                 * retaining the raw value above for diagnostics.
                 */

                $summary['next_fixture_rating'] =
                    $positionAwareFixtureRating;
                    
                $summary['next_opponent_team_id'] =
                    $nextOpponentId;


                $summary['next_opponent_attack_rating'] =
                    $opponentAttackRating;


                $summary['next_opponent_defence_rating'] =
                    $opponentDefenceRating;

                /*
                 * Preserve useful explorer data that does
                 * not currently belong to the flat engine
                 * summary.
                 */

                $summary['minutes'] =
                    (int) (
                        $profile[
                            'performance'
                        ]['minutes']
                        ?? 0
                    );


                $summary['selected_by_percent'] =
                    isset(
                        $player[
                            'selected_by_percent'
                        ]
                    )
                    &&
                    is_numeric(
                        $player[
                            'selected_by_percent'
                        ]
                    )
                        ? (float)
                            $player[
                                'selected_by_percent'
                            ]
                        : null;


                $summary['team_name'] =
                    $teamLookup[$teamId]['name']
                    ?? null;


                $summary['team_short_name'] =
                    $teamLookup[$teamId]['short_name']
                    ?? null;


                /*
                 * Useful performance information for
                 * future player detail views.
                 */

                $summary['sample_confidence'] =
                    $profile[
                        'strength'
                    ]['sample_confidence']
                    ?? null;


                /*
                 * --------------------------------------------------------
                 * ATTACKING PERFORMANCE RATINGS
                 * --------------------------------------------------------
                 *
                 * Expose the normalised attacking ratings already
                 * calculated by PlayerPerformance so downstream
                 * intelligence models such as Captain Intelligence
                 * can use them without rebuilding the player profile.
                 */

                $summary['goals_rating'] =
                    $profile[
                        'performance'
                    ]['goals_rating']
                    ?? null;


                $summary['assists_rating'] =
                    $profile[
                        'performance'
                    ]['assists_rating']
                    ?? null;


                $summary['expected_goals_rating'] =
                    $profile[
                        'performance'
                    ]['expected_goals_rating']
                    ?? null;


                $summary['expected_assists_rating'] =
                    $profile[
                        'performance'
                    ]['expected_assists_rating']
                    ?? null;


                $summary['bps_per_90'] =
                    $profile[
                        'performance'
                    ]['bps_per_90']
                    ?? null;
                                    
                $summaryAssessment =
                    $this->playerAssessment
                        ->buildAssessment(
                            [
                                'summary' =>
                                    $summary,

                                'performance' =>
                                    $profile[
                                        'performance'
                                    ]
                                    ?? [],

                                /*
                                 * Explorer summaries do not calculate the
                                 * complete 10-fixture trend. That is fine:
                                 * fixture trend affects commentary rather than
                                 * the core assessment verdict.
                                 */
                                'fixtures' => [

                                    'trend' =>
                                        'Insufficient Data'
                                ]
                            ]
                        );


                $summary['assessment_verdict'] =
                    $summaryAssessment[
                        'verdict'
                    ]
                    ?? null;


                $summary['assessment_verdict_key'] =
                    $summaryAssessment[
                        'verdict_key'
                    ]
                    ?? null;    


                $summaries[] =
                    $summary;

            } catch (Throwable $exception) {

                /*
                 * One malformed player record should not
                 * prevent the entire explorer loading.
                 */
                continue;
            }
        }
        


        return $summaries;
    }


    /**
     * Return only players with a usable intelligence score,
     * ordered by PlayerRanking.
     */
    public function getRankedPlayers(
        ?int $limit = null
    ): array {

        $players =
            array_values(
                array_filter(
                    $this->getAllPlayerSummaries(),
                    function (
                        array $player
                    ): bool {

                        return isset(
                            $player[
                                'intelligence_score'
                            ]
                        )
                        &&
                        $player[
                            'intelligence_score'
                        ] !== null;
                    }
                )
            );


        /*
         * PlayerRanking already owns the official
         * intelligence ordering behaviour.
         */
        if ($limit !== null) {

            if ($limit <= 0) {
                return [];
            }


            return $this->playerRanking
                ->getTopPlayers(
                    $players,
                    $limit
                );
        }


        /*
         * Request every available player so ordering
         * remains owned by PlayerRanking rather than
         * duplicated here.
         */
        return $this->playerRanking
            ->getTopPlayers(
                $players,
                count($players)
            );
    }
    
    
    /**
     * Build Team Intelligence summaries for every current
     * Premier League team.
     *
     * This method reuses the existing team strength,
     * performance and fixture intelligence models rather
     * than recalculating those concepts independently.
     *
     * Results are ordered by Team Intelligence Score,
     * highest first.
     */
    public function getAllTeamIntelligenceSummaries(): array
    {
        /*
         * ========================================================
         * LOAD SOURCE DATA
         * ========================================================
         */

        $teams =
            $this->teamRepository
                ->getAll();


        $fixtures =
            $this->fixtureRepository
                ->getAll();


        if (
            empty(
                $teams
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * BASELINE TEAM STRENGTH
         * ========================================================
         */

        $teamBaselines =
            $this->teamStrength
                ->calculateTeamStrengths(
                    $teams
                );


        /*
         * ========================================================
         * COMPLETE CURRENT TEAM MODELS
         * ========================================================
         *
         * TeamStrengthModel blends the FPL baseline with actual
         * completed-match performance as the season progresses.
         */

        $completeTeamModels =
            [];


        $teamPerformanceLookup =
            [];


        foreach (
            $teamBaselines
            as $teamId => $baseline
        ) {

            $performance =
                $this->teamPerformance
                    ->analyse(
                        $fixtures,
                        (int) $teamId
                    );


            $teamPerformanceLookup[
                (int) $teamId
            ] =
                $performance;


            $completeTeamModels[
                (int) $teamId
            ] =
                $this->teamStrengthModel
                    ->buildTeamModel(
                        $baseline,
                        $performance,
                        $this->teamPerformance
                    );
        }


        /*
         * ========================================================
         * SUPPORTING TEAM INTELLIGENCE MODELS
         * ========================================================
         */

        $teamIntelligence =
            new TeamIntelligence();


        $teamFixtureProfile =
            new TeamFixtureProfile();


        /*
         * ========================================================
         * BUILD TEAM LOOKUP
         * ========================================================
         */

        $teamLookup =
            [];


        foreach (
            $teams
            as $team
        ) {

            $teamId =
                (int) (
                    $team[
                        'id'
                    ]
                    ?? 0
                );


            if (
                $teamId <= 0
            ) {

                continue;
            }


            $teamLookup[
                $teamId
            ] =
                $team;
        }


        /*
         * ========================================================
         * BUILD SUMMARIES
         * ========================================================
         */

        $summaries =
            [];


        foreach (
            $completeTeamModels
            as $teamId => $teamModel
        ) {

            $team =
                $teamLookup[
                    $teamId
                ]
                ?? null;


            if (
                !is_array(
                    $team
                )
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * UPCOMING FIXTURE RUN
             * ----------------------------------------------------
             *
             * Request up to ten upcoming fixtures so the existing
             * TeamFixtureProfile can calculate next-5/6/8/10 data,
             * trend information and best/worst runs.
             */

            $upcomingFixtures =
                $this->fixtureRepository
                    ->getUpcomingForTeam(
                        (int) $teamId,
                        10
                    );


            $fixtureRun =
                $this->fixtureIntelligence
                    ->analyseFixtureRun(
                        $upcomingFixtures,
                        $completeTeamModels,
                        (int) $teamId
                    );


            $fixtureProfile =
                $teamFixtureProfile
                    ->buildProfileFromAnalysis(
                        (int) $teamId,
                        (string) (
                            $team[
                                'name'
                            ]
                            ?? ''
                        ),
                        $fixtureRun,
                        $this->fixtureIntelligence
                    );


            /*
             * ----------------------------------------------------
             * CURRENT STRENGTH
             * ----------------------------------------------------
             */

            $strengthHome =
                isset(
                    $teamModel[
                        'home'
                    ]
                )
                &&
                is_numeric(
                    $teamModel[
                        'home'
                    ]
                )
                    ? (float) $teamModel[
                        'home'
                    ]
                    : null;


            $strengthAway =
                isset(
                    $teamModel[
                        'away'
                    ]
                )
                &&
                is_numeric(
                    $teamModel[
                        'away'
                    ]
                )
                    ? (float) $teamModel[
                        'away'
                    ]
                    : null;


            $strengthOverall =
                isset(
                    $teamModel[
                        'overall'
                    ]
                )
                &&
                is_numeric(
                    $teamModel[
                        'overall'
                    ]
                )
                    ? (float) $teamModel[
                        'overall'
                    ]
                    : null;


            /*
             * ----------------------------------------------------
             * FIXTURE RATING
             * ----------------------------------------------------
             */

            $fixtureRating =
                isset(
                    $fixtureProfile[
                        'fixture_rating'
                    ]
                )
                &&
                is_numeric(
                    $fixtureProfile[
                        'fixture_rating'
                    ]
                )
                    ? (float) $fixtureProfile[
                        'fixture_rating'
                    ]
                    : null;


            /*
             * ----------------------------------------------------
             * TEAM INTELLIGENCE SCORE
             * ----------------------------------------------------
             *
             * TeamIntelligence already owns the official weighting:
             *
             * 60% current team strength
             * 40% fixture opportunity
             */

            $intelligenceScore =
                $teamIntelligence
                    ->calculateIntelligenceScore(
                        $strengthOverall,
                        $fixtureRating
                    );


            $intelligenceLabel =
                $teamIntelligence
                    ->getIntelligenceLabel(
                        $intelligenceScore
                    );


            /*
             * ----------------------------------------------------
             * PERFORMANCE
             * ----------------------------------------------------
             */

            $performance =
                $teamPerformanceLookup[
                    $teamId
                ]
                ?? [];
                
            
            $attackRating =
                $this->teamPerformance
                    ->calculateAttackRating(
                        $performance
                    );


            $defenceRating =
                $this->teamPerformance
                    ->calculateDefenceRating(
                        $performance
                    );


            /*
             * ----------------------------------------------------
             * SUMMARY
             * ----------------------------------------------------
             */

            $summaries[] = [

                'team_id' =>
                    (int) $teamId,

                'fpl_team_id' =>
                    isset(
                        $team[
                            'fpl_team_id'
                        ]
                    )
                        ? (int) $team[
                            'fpl_team_id'
                        ]
                        : null,

                'name' =>
                    (string) (
                        $team[
                            'name'
                        ]
                        ?? ''
                    ),

                'short_name' =>
                    (string) (
                        $team[
                            'short_name'
                        ]
                        ?? ''
                    ),

                /*
                 * Current strength model.
                 */

                'strength_home' =>
                    $strengthHome,

                'strength_away' =>
                    $strengthAway,

                'strength_overall' =>
                    $strengthOverall,

                /*
                 * Team Intelligence.
                 */

                'intelligence_score' =>
                    $intelligenceScore,

                'intelligence_label' =>
                    $intelligenceLabel,

                /*
                 * Fixture Intelligence.
                 */

                'fixture_rating' =>
                    $fixtureRating,

                'fixture_label' =>
                    $fixtureProfile[
                        'fixture_label'
                    ]
                    ?? null,

                'fixture_trend' =>
                    $fixtureProfile[
                        'trend'
                    ]
                    ?? 'Insufficient Data',

                'next_5' =>
                    $fixtureProfile[
                        'next_5'
                    ]
                    ?? null,

                'next_6' =>
                    $fixtureProfile[
                        'next_6'
                    ]
                    ?? null,

                'next_8' =>
                    $fixtureProfile[
                        'next_8'
                    ]
                    ?? null,

                'next_10' =>
                    $fixtureProfile[
                        'next_10'
                    ]
                    ?? null,

                'best_run' =>
                    $fixtureProfile[
                        'best_run'
                    ]
                    ?? null,

                'worst_run' =>
                    $fixtureProfile[
                        'worst_run'
                    ]
                    ?? null,

                /*
                 * Performance context.
                 */

                'played' =>
                    (int) (
                        $performance[
                            'played'
                        ]
                        ?? 0
                    ),

                'wins' =>
                    (int) (
                        $performance[
                            'wins'
                        ]
                        ?? 0
                    ),

                'draws' =>
                    (int) (
                        $performance[
                            'draws'
                        ]
                        ?? 0
                    ),

                'losses' =>
                    (int) (
                        $performance[
                            'losses'
                        ]
                        ?? 0
                    ),

                'points' =>
                    (int) (
                        $performance[
                            'points'
                        ]
                        ?? 0
                    ),

                'goals_for' =>
                    (int) (
                        $performance[
                            'goals_for'
                        ]
                        ?? 0
                    ),

                'goals_against' =>
                    (int) (
                        $performance[
                            'goals_against'
                        ]
                        ?? 0
                    ),

                'goal_difference' =>
                    (int) (
                        $performance[
                            'goal_difference'
                        ]
                        ?? 0
                    ),

                'recent_form' =>
                    $performance[
                        'recent_form'
                    ]
                    ?? [],
                    
                'attack_rating' =>
                    $attackRating,

                'defence_rating' =>
                    $defenceRating,

                /*
                 * Model transparency.
                 */

                'performance_rating' =>
                    $teamModel[
                        'performance_rating'
                    ]
                    ?? null,

                'baseline_weight' =>
                    $teamModel[
                        'baseline_weight'
                    ]
                    ?? null,

                'performance_weight' =>
                    $teamModel[
                        'performance_weight'
                    ]
                    ?? null
            ];
        }


        /*
         * ========================================================
         * RANK BY TEAM INTELLIGENCE SCORE
         * ========================================================
         */

        usort(
            $summaries,
            function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    $a[
                        'intelligence_score'
                    ]
                    ?? null;


                $scoreB =
                    $b[
                        'intelligence_score'
                    ]
                    ?? null;


                /*
                 * Null scores always belong at the bottom.
                 */

                if (
                    $scoreA === null
                    &&
                    $scoreB === null
                ) {

                    return strcasecmp(
                        (string) (
                            $a[
                                'name'
                            ]
                            ?? ''
                        ),
                        (string) (
                            $b[
                                'name'
                            ]
                            ?? ''
                        )
                    );
                }


                if (
                    $scoreA === null
                ) {

                    return 1;
                }


                if (
                    $scoreB === null
                ) {

                    return -1;
                }


                $comparison =
                    (float) $scoreB
                    <=>
                    (float) $scoreA;


                /*
                 * Stable human-readable tie-break.
                 */

                if (
                    $comparison === 0
                ) {

                    return strcasecmp(
                        (string) (
                            $a[
                                'name'
                            ]
                            ?? ''
                        ),
                        (string) (
                            $b[
                                'name'
                            ]
                            ?? ''
                        )
                    );
                }


                return $comparison;
            }
        );


        return $summaries;
    }
    
    
    /**
     * Build the complete Team Intelligence profile for one
     * current Premier League team.
     *
     * The profile deliberately reuses the ranked Team Intelligence
     * summaries so Team Intelligence, strength, fixture opportunity
     * and performance calculations continue to have one source
     * of truth.
     */
    public function getTeamIntelligenceProfile(
        int $teamId
    ): array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            $teamId <= 0
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'A valid Team Intelligence team ID is required.',

                'team' =>
                    null,

                'ranking' =>
                    null,

                'strength' =>
                    null,

                'fixtures' =>
                    null,

                'form' =>
                    null,

                'players' =>
                    []
            ];
        }


        /*
         * ========================================================
         * TEAM INTELLIGENCE SUMMARY
         * ========================================================
         *
         * Reuse the ranked collection rather than rebuilding the
         * Team Intelligence calculations here.
         *
         * The array position also gives us the team's current
         * Intelligence ranking.
         */

        $teamSummaries =
            $this->getAllTeamIntelligenceSummaries();


        $teamSummary =
            null;


        $teamRank =
            null;


        foreach (
            $teamSummaries
            as $index => $summary
        ) {

            $summaryTeamId =
                (int) (
                    $summary[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $summaryTeamId
                !==
                $teamId
            ) {

                continue;
            }


            $teamSummary =
                $summary;


            $teamRank =
                $index + 1;


            break;
        }


        /*
         * ========================================================
         * UNKNOWN TEAM
         * ========================================================
         */

        if (
            $teamSummary === null
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'The requested Team Intelligence team could not be found.',

                'team' =>
                    null,

                'ranking' =>
                    null,

                'strength' =>
                    null,

                'fixtures' =>
                    null,

                'form' =>
                    null,

                'players' =>
                    []
            ];
        }


        /*
         * ========================================================
         * UPCOMING FIXTURES
         * ========================================================
         *
         * The summary already owns the calculated fixture rating,
         * label and trend.
         *
         * Here we additionally expose the underlying upcoming
         * fixture records for the detailed team profile page.
         */

        $upcomingFixtures =
            $this->fixtureRepository
                ->getUpcomingForTeam(
                    $teamId,
                    10
                );


        if (
            !is_array(
                $upcomingFixtures
            )
        ) {

            $upcomingFixtures =
                [];
        }
        
        
        /*
         * ========================================================
         * FIXTURE OPPONENT LOOKUP
         * ========================================================
         *
         * Resolve opponent identity once inside the service so
         * public/team.php does not need to rebuild the complete
         * Team Intelligence collection for every fixture card.
         */

        $teamLookup =
            [];


        foreach (
            $teamSummaries
            as $summary
        ) {

            $summaryTeamId =
                (int) (
                    $summary[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $summaryTeamId <= 0
            ) {

                continue;
            }


            $teamLookup[
                $summaryTeamId
            ] = [

                'team_id' =>
                    $summaryTeamId,

                'name' =>
                    (string) (
                        $summary[
                            'name'
                        ]
                        ?? ''
                    ),

                'short_name' =>
                    (string) (
                        $summary[
                            'short_name'
                        ]
                        ?? ''
                    )
            ];
        }


        /*
         * ========================================================
         * DECORATE UPCOMING FIXTURES
         * ========================================================
         */

        $profileFixtures =
            [];


        foreach (
            $upcomingFixtures
            as $fixture
        ) {

            $homeTeamId =
                (int) (
                    $fixture[
                        'home_team_id'
                    ]
                    ?? 0
                );


            $awayTeamId =
                (int) (
                    $fixture[
                        'away_team_id'
                    ]
                    ?? 0
                );


            $isHome =
                $homeTeamId
                ===
                $teamId;


            $opponentId =
                $isHome
                    ? $awayTeamId
                    : $homeTeamId;


            $opponent =
                $teamLookup[
                    $opponentId
                ]
                ?? [];


            $profileFixtures[] =
                array_merge(
                    $fixture,
                    [

                        'opponent_id' =>
                            $opponentId,

                        'opponent_name' =>
                            (string) (
                                $opponent[
                                    'name'
                                ]
                                ?? 'Unknown'
                            ),

                        'opponent_short_name' =>
                            (string) (
                                $opponent[
                                    'short_name'
                                ]
                                ?? ''
                            ),

                        'venue' =>
                            $isHome
                                ? 'Home'
                                : 'Away',

                        'is_home' =>
                            $isHome
                    ]
                );
        }


        /*
         * ========================================================
         * TEAM PLAYERS
         * ========================================================
         *
         * Reuse the lightweight Player Intelligence summaries so
         * the team profile can later display and rank the club's
         * current FPL assets without loading a full profile for
         * every player.
         */

        $playerSummaries =
            $this->getAllPlayerSummaries();


        $teamPlayers =
            [];


        foreach (
            $playerSummaries
            as $playerSummary
        ) {

            $playerId =
                (int) (
                    $playerSummary[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
            ) {

                continue;
            }


            /*
             * Player summaries currently expose the team name but
             * may not always expose the local team ID directly.
             *
             * Resolve the player's repository record when required
             * so the profile contract always returns a reliable
             * local team_id.
             */

            $playerTeamId =
                isset(
                    $playerSummary[
                        'team_id'
                    ]
                )
                    ? (int) $playerSummary[
                        'team_id'
                    ]
                    : 0;


            if (
                $playerTeamId <= 0
            ) {

                $playerRecord =
                    $this->playerRepository
                        ->getById(
                            $playerId
                        );


                $playerTeamId =
                    (int) (
                        $playerRecord[
                            'team_id'
                        ]
                        ?? 0
                    );
            }


            if (
                $playerTeamId
                !==
                $teamId
            ) {

                continue;
            }


            $teamPlayers[] = [

                'player_id' =>
                    $playerId,

                'team_id' =>
                    $playerTeamId,

                'name' =>
                    (string) (
                        $playerSummary[
                            'name'
                        ]
                        ?? ''
                    ),

                'position' =>
                    $playerSummary[
                        'position'
                    ]
                    ?? null,

                'price' =>
                    $playerSummary[
                        'price'
                    ]
                    ?? null,

                'intelligence_score' =>
                    $playerSummary[
                        'intelligence_score'
                    ]
                    ?? null,

                'strength_rating' =>
                    $playerSummary[
                        'strength_rating'
                    ]
                    ?? null,

                'value_rating' =>
                    $playerSummary[
                        'value_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $playerSummary[
                        'fixture_rating'
                    ]
                    ?? null,

                'next_fixture_rating' =>
                    $playerSummary[
                        'next_fixture_rating'
                    ]
                    ?? null,

                'availability_rating' =>
                    $playerSummary[
                        'availability_rating'
                    ]
                    ?? null,

                'sample_confidence' =>
                    $playerSummary[
                        'sample_confidence'
                    ]
                    ?? null,

                'assessment_verdict' =>
                    $playerSummary[
                        'assessment_verdict'
                    ]
                    ?? null
            ];
        }


        /*
         * --------------------------------------------------------
         * PLAYER ORDERING
         * --------------------------------------------------------
         *
         * Highest Player Intelligence first.
         *
         * Null Intelligence Scores belong at the bottom, with
         * player name providing a stable tie-break.
         */

        usort(
            $teamPlayers,
            function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    $a[
                        'intelligence_score'
                    ]
                    ?? null;


                $scoreB =
                    $b[
                        'intelligence_score'
                    ]
                    ?? null;


                if (
                    $scoreA === null
                    &&
                    $scoreB === null
                ) {

                    return strcasecmp(
                        (string) (
                            $a[
                                'name'
                            ]
                            ?? ''
                        ),
                        (string) (
                            $b[
                                'name'
                            ]
                            ?? ''
                        )
                    );
                }


                if (
                    $scoreA === null
                ) {

                    return 1;
                }


                if (
                    $scoreB === null
                ) {

                    return -1;
                }


                $comparison =
                    (float) $scoreB
                    <=>
                    (float) $scoreA;


                if (
                    $comparison !== 0
                ) {

                    return $comparison;
                }


                return strcasecmp(
                    (string) (
                        $a[
                            'name'
                        ]
                        ?? ''
                    ),
                    (string) (
                        $b[
                            'name'
                        ]
                        ?? ''
                    )
                );
            }
        );


        /*
         * ========================================================
         * COMPLETE PROFILE
         * ========================================================
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Team Intelligence profile generated successfully.',


            /*
             * ----------------------------------------------------
             * TEAM IDENTITY
             * ----------------------------------------------------
             */

            'team' => [

                'team_id' =>
                    (int) (
                        $teamSummary[
                            'team_id'
                        ]
                        ?? 0
                    ),

                'fpl_team_id' =>
                    isset(
                        $teamSummary[
                            'fpl_team_id'
                        ]
                    )
                        ? (int) $teamSummary[
                            'fpl_team_id'
                        ]
                        : null,

                'name' =>
                    (string) (
                        $teamSummary[
                            'name'
                        ]
                        ?? ''
                    ),

                'short_name' =>
                    (string) (
                        $teamSummary[
                            'short_name'
                        ]
                        ?? ''
                    )
            ],


            /*
             * ----------------------------------------------------
             * TEAM INTELLIGENCE RANKING
             * ----------------------------------------------------
             */

            'ranking' => [

                'rank' =>
                    $teamRank,

                'intelligence_score' =>
                    $teamSummary[
                        'intelligence_score'
                    ]
                    ?? null,

                'intelligence_label' =>
                    $teamSummary[
                        'intelligence_label'
                    ]
                    ?? null
            ],


            /*
             * ----------------------------------------------------
             * CURRENT STRENGTH
             * ----------------------------------------------------
             */

            'strength' => [

                'overall' =>
                    $teamSummary[
                        'strength_overall'
                    ]
                    ?? null,

                'home' =>
                    $teamSummary[
                        'strength_home'
                    ]
                    ?? null,

                'away' =>
                    $teamSummary[
                        'strength_away'
                    ]
                    ?? null,

                'performance_rating' =>
                    $teamSummary[
                        'performance_rating'
                    ]
                    ?? null,

                'baseline_weight' =>
                    $teamSummary[
                        'baseline_weight'
                    ]
                    ?? null,

                'performance_weight' =>
                    $teamSummary[
                        'performance_weight'
                    ]
                    ?? null
            ],


            /*
             * ----------------------------------------------------
             * FIXTURE INTELLIGENCE
             * ----------------------------------------------------
             */

            'fixtures' => [

                'rating' =>
                    $teamSummary[
                        'fixture_rating'
                    ]
                    ?? null,

                'label' =>
                    $teamSummary[
                        'fixture_label'
                    ]
                    ?? null,

                'trend' =>
                    $teamSummary[
                        'fixture_trend'
                    ]
                    ?? 'Insufficient Data',

                'next_5' =>
                    $teamSummary[
                        'next_5'
                    ]
                    ?? null,

                'next_6' =>
                    $teamSummary[
                        'next_6'
                    ]
                    ?? null,

                'next_8' =>
                    $teamSummary[
                        'next_8'
                    ]
                    ?? null,

                'next_10' =>
                    $teamSummary[
                        'next_10'
                    ]
                    ?? null,

                'best_run' =>
                    $teamSummary[
                        'best_run'
                    ]
                    ?? null,

                'worst_run' =>
                    $teamSummary[
                        'worst_run'
                    ]
                    ?? null,

                'upcoming' =>
                    $profileFixtures
            ],


            /*
             * ----------------------------------------------------
             * CURRENT PREMIER LEAGUE FORM
             * ----------------------------------------------------
             */

            'form' => [

                'recent_form' =>
                    is_array(
                        $teamSummary[
                            'recent_form'
                        ]
                        ?? null
                    )
                        ? $teamSummary[
                            'recent_form'
                        ]
                        : [],

                'played' =>
                    (int) (
                        $teamSummary[
                            'played'
                        ]
                        ?? 0
                    ),

                'wins' =>
                    (int) (
                        $teamSummary[
                            'wins'
                        ]
                        ?? 0
                    ),

                'draws' =>
                    (int) (
                        $teamSummary[
                            'draws'
                        ]
                        ?? 0
                    ),

                'losses' =>
                    (int) (
                        $teamSummary[
                            'losses'
                        ]
                        ?? 0
                    ),

                'points' =>
                    (int) (
                        $teamSummary[
                            'points'
                        ]
                        ?? 0
                    ),

                'goals_for' =>
                    (int) (
                        $teamSummary[
                            'goals_for'
                        ]
                        ?? 0
                    ),

                'goals_against' =>
                    (int) (
                        $teamSummary[
                            'goals_against'
                        ]
                        ?? 0
                    ),

                'goal_difference' =>
                    (int) (
                        $teamSummary[
                            'goal_difference'
                        ]
                        ?? 0
                    ),
                    
                'attack_rating' =>
                    $teamSummary[
                        'attack_rating'
                    ]
                    ?? null,

                'defence_rating' =>
                    $teamSummary[
                        'defence_rating'
                    ]
                    ?? null
            ],


            /*
             * ----------------------------------------------------
             * CURRENT FPL PLAYERS
             * ----------------------------------------------------
             */

            'players' =>
                $teamPlayers
        ];
    }


    /**
     * Build the next-five fixture opportunity rating
     * for each Premier League team.
     */
    private function buildTeamFixtureRatings(
        array $teams,
        array $fixtures
    ): array {

        if (
            empty($teams)
            ||
            empty($fixtures)
        ) {

            return [];
        }


        /*
         * --------------------------------------------------------
         * BASELINE TEAM STRENGTH
         * --------------------------------------------------------
         */

        $teamBaselines =
            $this->teamStrength
                ->calculateTeamStrengths(
                    $teams
                );


        /*
         * --------------------------------------------------------
         * COMPLETE TEAM MODELS
         * --------------------------------------------------------
         */

        $completeTeamModels =
            [];


        foreach (
            $teamBaselines
            as $teamId => $baseline
        ) {

            $performance =
                $this->teamPerformance
                    ->analyse(
                        $fixtures,
                        (int) $teamId
                    );


            $completeTeamModels[
                $teamId
            ] =
                $this->teamStrengthModel
                    ->buildTeamModel(
                        $baseline,
                        $performance,
                        $this->teamPerformance
                    );
        }


        /*
         * --------------------------------------------------------
         * UPCOMING FIXTURE OPPORTUNITY
         * --------------------------------------------------------
         */

        $ratings =
            [];


        foreach (
            $completeTeamModels
            as $teamId => $teamModel
        ) {

            $upcomingFixtures =
                $this->fixtureRepository
                    ->getUpcomingForTeam(
                        (int) $teamId,
                        5
                    );


            $fixtureRun =
                $this->fixtureIntelligence
                    ->analyseFixtureRun(
                        $upcomingFixtures,
                        $completeTeamModels,
                        (int) $teamId
                    );


            $opportunityAverages =
                $this->fixtureIntelligence
                    ->calculateOpportunityAverages(
                        $fixtureRun
                    );


            $ratings[$teamId] =
                $opportunityAverages[
                    'next_5'
                ]
                ?? null;
        }


        return $ratings;
    }
    
    /**
     * Build the immediate next-fixture opportunity rating
     * for each Premier League team.
     *
     * This is intentionally separate from buildTeamFixtureRatings(),
     * which represents the rolling next-five fixture opportunity used
     * by general Player Intelligence.
     *
     * Captain Intelligence needs the single upcoming fixture only.
     */
    private function buildTeamNextFixtureRatings(
        array $teams,
        array $fixtures
    ): array {

        if (
            empty(
                $teams
            )
            ||
            empty(
                $fixtures
            )
        ) {

            return [];
        }


        /*
         * --------------------------------------------------------
         * BASELINE TEAM STRENGTH
         * --------------------------------------------------------
         */

        $teamBaselines =
            $this->teamStrength
                ->calculateTeamStrengths(
                    $teams
                );


        /*
         * --------------------------------------------------------
         * COMPLETE TEAM MODELS
         * --------------------------------------------------------
         */

        $completeTeamModels =
            [];


        foreach (
            $teamBaselines
            as $teamId => $baseline
        ) {

            $performance =
                $this->teamPerformance
                    ->analyse(
                        $fixtures,
                        (int) $teamId
                    );


            $completeTeamModels[
                $teamId
            ] =
                $this->teamStrengthModel
                    ->buildTeamModel(
                        $baseline,
                        $performance,
                        $this->teamPerformance
                    );
        }


        /*
         * --------------------------------------------------------
         * NEXT FIXTURE OPPORTUNITY
         * --------------------------------------------------------
         */

        $ratings =
            [];


        foreach (
            $completeTeamModels
            as $teamId => $teamModel
        ) {

            /*
             * We deliberately request one fixture only.
             */

            $upcomingFixtures =
                $this->fixtureRepository
                    ->getUpcomingForTeam(
                        (int) $teamId,
                        1
                    );


            if (
                empty(
                    $upcomingFixtures
                )
            ) {

                $ratings[
                    $teamId
                ] =
                    null;

                continue;
            }


            $fixtureRun =
                $this->fixtureIntelligence
                    ->analyseFixtureRun(
                        $upcomingFixtures,
                        $completeTeamModels,
                        (int) $teamId
                    );


            /*
             * analyseFixtureRun() returns opportunity_score
             * for each analysed fixture.
             */

            $firstFixture =
                $fixtureRun[
                    0
                ]
                ?? null;


            if (
                !is_array(
                    $firstFixture
                )
                ||
                !isset(
                    $firstFixture[
                        'opportunity_score'
                    ]
                )
                ||
                !is_numeric(
                    $firstFixture[
                        'opportunity_score'
                    ]
                )
            ) {

                $ratings[
                    $teamId
                ] =
                    null;

                continue;
            }


            $ratings[
                $teamId
            ] =
                round(
                    max(
                        0.0,
                        min(
                            100.0,
                            (float) $firstFixture[
                                'opportunity_score'
                            ]
                        )
                    ),
                    2
                );
        }


        return $ratings;
    }
    
    /**
     * Return a complete intelligence profile for one player.
     */
    public function getPlayerProfile(
        int $playerId
    ): ?array {

        if ($playerId <= 0) {
            return null;
        }


        /*
         * --------------------------------------------------------
         * LOAD PLAYER
         * --------------------------------------------------------
         */

        $player =
            $this->playerRepository
                ->getById(
                    $playerId
                );


        if ($player === null) {
            return null;
        }


        /*
         * --------------------------------------------------------
         * LOAD TEAM
         * --------------------------------------------------------
         */

        $teamId =
            (int) (
                $player['team_id']
                ?? 0
            );


        $team =
            $teamId > 0
                ? $this->teamRepository
                    ->getById(
                        $teamId
                    )
                : null;


        /*
         * --------------------------------------------------------
         * FIXTURE OPPORTUNITY
         * --------------------------------------------------------
         */

        $teams =
            $this->teamRepository
                ->getAll();


        $fixtures =
            $this->fixtureRepository
                ->getAll();


        $teamFixtureRatings =
            $this->buildTeamFixtureRatings(
                $teams,
                $fixtures
            );


        $fixtureRating =
            $teamFixtureRatings[$teamId]
            ?? null;


        /*
         * --------------------------------------------------------
         * COMPLETE PLAYER INTELLIGENCE
         * --------------------------------------------------------
         */

        $profile =
            $this->playerEngine
                ->analysePlayer(
                    $player,
                    $fixtureRating
                );


        /*
         * --------------------------------------------------------
         * UPCOMING FIXTURE RUN
         * --------------------------------------------------------
         */

        $fixtureRun =
            [];


        if ($teamId > 0) {

            $upcomingFixtures =
                $this->fixtureRepository
                    ->getUpcomingForTeam(
                        $teamId,
                        10
                    );


            /*
             * We need the complete team strength models
             * for venue-aware fixture intelligence.
             */

            $teamBaselines =
                $this->teamStrength
                    ->calculateTeamStrengths(
                        $teams
                    );


            $completeTeamModels =
                [];


            foreach (
                $teamBaselines
                as $id => $baseline
            ) {

                $performance =
                    $this->teamPerformance
                        ->analyse(
                            $fixtures,
                            (int) $id
                        );


                $completeTeamModels[$id] =
                    $this->teamStrengthModel
                        ->buildTeamModel(
                            $baseline,
                            $performance,
                            $this->teamPerformance
                        );
            }


            $fixtureRun =
                $this->fixtureIntelligence
                    ->analyseFixtureRun(
                        $upcomingFixtures,
                        $completeTeamModels,
                        $teamId
                    );
        }


        /*
         * --------------------------------------------------------
         * FIXTURE ROLLING AVERAGES
         * --------------------------------------------------------
         */

        $rollingAverages =
            $this->fixtureIntelligence
                ->calculateOpportunityAverages(
                    $fixtureRun
                );
                
        $bestOpportunityRun =
            $this->fixtureIntelligence
                ->findBestOpportunityRun(
                    $fixtureRun,
                    5
                );


        $worstOpportunityRun =
            $this->fixtureIntelligence
                ->findWorstOpportunityRun(
                    $fixtureRun,
                    5
                );


        $opportunityTrend =
            $this->fixtureIntelligence
                ->calculateOpportunityTrend(
                    $fixtureRun
                );


        /*
         * --------------------------------------------------------
         * COMPLETE FRONT-END PROFILE
         * --------------------------------------------------------
         */

        $completeProfile = [

            'player' =>
                $profile['player'],

            'team' => [

                'team_id' =>
                    $teamId,

                'name' =>
                    $team['name']
                    ?? null,

                'short_name' =>
                    $team['short_name']
                    ?? null
            ],

            'performance' =>
                $profile['performance'],

            'strength' =>
                $profile['strength'],

            'value' =>
                $profile['value'],

            'availability' =>
                $profile['availability'],

            'intelligence' =>
                $profile['intelligence'],

            'summary' =>
                $profile['summary'],

            'fixtures' => [

                'rating' =>
                    $fixtureRating,

                'rolling_averages' =>
                    $rollingAverages,

                'best_run' =>
                    $bestOpportunityRun,

                'worst_run' =>
                    $worstOpportunityRun,

                'trend' =>
                    $opportunityTrend,

                'fixture_count' =>
                    count(
                        $fixtureRun
                    ),

                'upcoming' =>
                    $fixtureRun
            ]
        ];


        $completeProfile['assessment'] =
            $this->playerAssessment
                ->buildAssessment(
                    $completeProfile
                );


        return $completeProfile;
    }
    
    /**
     * Compare two complete player intelligence profiles.
     */
    public function comparePlayers(
        int $playerIdA,
        int $playerIdB
    ): ?array {

        if (
            $playerIdA <= 0
            ||
            $playerIdB <= 0
        ) {

            return null;
        }


        /*
         * --------------------------------------------------------
         * PREVENT SELF-COMPARISON
         * --------------------------------------------------------
         */

        if ($playerIdA === $playerIdB) {
            return null;
        }


        /*
         * --------------------------------------------------------
         * LOAD COMPLETE PLAYER PROFILES
         * --------------------------------------------------------
         */

        $profileA =
            $this->getPlayerProfile(
                $playerIdA
            );


        $profileB =
            $this->getPlayerProfile(
                $playerIdB
            );


        if (
            $profileA === null
            ||
            $profileB === null
        ) {

            return null;
        }


        /*
         * --------------------------------------------------------
         * PLAYER COMPARISON
         * --------------------------------------------------------
         */

        return $this->playerComparison
            ->compare(
                $profileA,
                $profileB
            );
    }
    
    /**
     * Find suitable replacement candidates for a player.
     */
    public function findPlayerReplacements(
        int $playerId,
        float $maxPrice,
        int $limit = 10
    ): ?array {

        if (
            $playerId <= 0
            ||
            $maxPrice < 0
            ||
            $limit <= 0
        ) {

            return null;
        }


        /*
         * ========================================================
         * CURRENT PLAYER PROFILE
         * ========================================================
         */

        $profile =
            $this->getPlayerProfile(
                $playerId
            );


        if ($profile === null) {
            return null;
        }


        $summary =
            $profile['summary']
            ?? [];


        $currentPlayer = [

            'player_id' =>
                (int) (
                    $profile[
                        'player'
                    ]['player_id']
                    ?? $playerId
                ),

            'name' =>
                $profile[
                    'player'
                ]['name']
                ?? null,

            'position' =>
                $profile[
                    'player'
                ]['position']
                ?? null,

            'team_name' =>
                $profile[
                    'team'
                ]['name']
                ?? null,

            'price' =>
                $summary['price']
                ?? null,

            'intelligence_score' =>
                $summary[
                    'intelligence_score'
                ]
                ?? null,

            'strength_rating' =>
                $summary[
                    'strength_rating'
                ]
                ?? null,

            'value_rating' =>
                $summary[
                    'value_rating'
                ]
                ?? null,

            'fixture_rating' =>
                $summary[
                    'fixture_rating'
                ]
                ?? null,

            'availability_rating' =>
                $summary[
                    'availability_rating'
                ]
                ?? null,

            'sample_confidence' =>
                $profile[
                    'performance'
                ]['sample_confidence']
                ?? null,
                
            'goals_rating' =>
                $profile[
                    'performance'
                ][
                    'goals_rating'
                ]
                ?? null,

            'assists_rating' =>
                $profile[
                    'performance'
                ][
                    'assists_rating'
                ]
                ?? null,

            'expected_goals_rating' =>
                $profile[
                    'performance'
                ][
                    'expected_goals_rating'
                ]
                ?? null,

            'expected_assists_rating' =>
                $profile[
                    'performance'
                ][
                    'expected_assists_rating'
                ]
                ?? null,

            'verdict' =>
                $profile[
                    'assessment'
                ]['verdict']
                ?? null
        ];


        /*
         * ========================================================
         * CANDIDATE POOL
         * ========================================================
         */

        $playerSummaries =
            $this->getAllPlayerSummaries();


        $candidates =
            [];


        foreach (
            $playerSummaries
            as $playerSummary
        ) {

            $candidateId =
                (int) (
                    $playerSummary[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($candidateId <= 0) {
                continue;
            }


            $candidates[] = [

                'player_id' =>
                    $candidateId,

                'name' =>
                    $playerSummary[
                        'name'
                    ]
                    ?? null,

                'team_name' =>
                    $playerSummary[
                        'team_name'
                    ]
                    ?? null,

                'team_short_name' =>
                    $playerSummary[
                        'team_short_name'
                    ]
                    ?? null,

                'position' =>
                    $playerSummary[
                        'position'
                    ]
                    ?? null,

                'price' =>
                    $playerSummary[
                        'price'
                    ]
                    ?? null,

                'intelligence_score' =>
                    $playerSummary[
                        'intelligence_score'
                    ]
                    ?? null,

                'strength_rating' =>
                    $playerSummary[
                        'strength_rating'
                    ]
                    ?? null,

                'value_rating' =>
                    $playerSummary[
                        'value_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $playerSummary[
                        'fixture_rating'
                    ]
                    ?? null,

                'availability_rating' =>
                    $playerSummary[
                        'availability_rating'
                    ]
                    ?? null,

                'sample_confidence' =>
                    $playerSummary[
                        'sample_confidence'
                    ]
                    ?? null,

                'verdict' =>
                    $playerSummary[
                        'assessment_verdict'
                    ]
                    ?? null
            ];
        }


        /*
         * ========================================================
         * REPLACEMENT SEARCH
         * ========================================================
         */

        $replacements =
            $this->playerReplacement
                ->findReplacements(
                    $currentPlayer,
                    $candidates,
                    $maxPrice,
                    $limit
                );


        /*
         * Add presentation-friendly replacement type and summary.
         */

        foreach (
            $replacements
            as &$replacement
        ) {

            $replacement[
                'replacement_type'
            ] =
                $this->playerReplacement
                    ->getReplacementType(
                        $replacement[
                            'intelligence_gain'
                        ]
                        ?? null
                    );


            $replacement[
                'replacement_summary'
            ] =
                $this->playerReplacement
                    ->buildReplacementSummary(
                        $replacement
                    );
                    
            $replacement[
                'transfer_decision'
            ] =
                $this->transferDecision
                    ->evaluateTransfer(
                        $currentPlayer,
                        $replacement
                    );
        }


        unset(
            $replacement
        );

        /*
         * ========================================================
         * REPLACEMENT RECOMMENDATION INTELLIGENCE
         * ========================================================
         */

        $recommendations =
            $this->replacementRecommendation
                ->buildRecommendations(
                    $replacements
                );

        return [

            'current_player' =>
                $currentPlayer,

            'max_price' =>
                round(
                    $maxPrice,
                    2
                ),

            'limit' =>
                $limit,

            'replacement_count' =>
                count(
                    $replacements
                ),
                
            'recommendations' =>
                $recommendations,

            'replacements' =>
                $replacements                
            
        ];
    }
    
    /**
     * Evaluate a direct transfer from one player to another.
     */
    public function evaluatePlayerTransfer(
        int $currentPlayerId,
        int $replacementPlayerId
    ): ?array {

        if (
            $currentPlayerId <= 0
            ||
            $replacementPlayerId <= 0
        ) {

            return null;
        }


        if (
            $currentPlayerId
            ===
            $replacementPlayerId
        ) {

            return null;
        }


        /*
         * ========================================================
         * CURRENT PLAYER
         * ========================================================
         */

        $currentProfile =
            $this->getPlayerProfile(
                $currentPlayerId
            );


        if ($currentProfile === null) {
            return null;
        }


        /*
         * ========================================================
         * REPLACEMENT PLAYER
         * ========================================================
         */

        $replacementProfile =
            $this->getPlayerProfile(
                $replacementPlayerId
            );


        if ($replacementProfile === null) {
            return null;
        }


        /*
         * ========================================================
         * BUILD TRANSFER PLAYER DATA
         * ========================================================
         */

        $currentPlayer =
            $this->buildTransferDecisionPlayer(
                $currentProfile
            );


        $replacementPlayer =
            $this->buildTransferDecisionPlayer(
                $replacementProfile
            );


        /*
         * ========================================================
         * TRANSFER DECISION
         * ========================================================
         */

        return $this->transferDecision
            ->evaluateTransfer(
                $currentPlayer,
                $replacementPlayer
            );
    }
    
    /**
     * Evaluate a linked two-transfer combination.
     */
    public function evaluateTransferCombination(
        int $currentPlayerIdA,
        int $replacementPlayerIdA,
        int $currentPlayerIdB,
        int $replacementPlayerIdB
    ): ?array {

        /*
         * ========================================================
         * BASIC VALIDATION
         * ========================================================
         */

        if (
            $currentPlayerIdA <= 0
            ||
            $replacementPlayerIdA <= 0
            ||
            $currentPlayerIdB <= 0
            ||
            $replacementPlayerIdB <= 0
        ) {

            return null;
        }


        /*
         * A player cannot replace themselves.
         */

        if (
            $currentPlayerIdA
            ===
            $replacementPlayerIdA
            ||
            $currentPlayerIdB
            ===
            $replacementPlayerIdB
        ) {

            return null;
        }


        /*
         * Do not allow the same outgoing player twice.
         */

        if (
            $currentPlayerIdA
            ===
            $currentPlayerIdB
        ) {

            return null;
        }


        /*
         * Do not allow the same incoming player twice.
         */

        if (
            $replacementPlayerIdA
            ===
            $replacementPlayerIdB
        ) {

            return null;
        }


        /*
         * ========================================================
         * LOAD PLAYER PROFILES
         * ========================================================
         */

        $currentProfileA =
            $this->getPlayerProfile(
                $currentPlayerIdA
            );


        $replacementProfileA =
            $this->getPlayerProfile(
                $replacementPlayerIdA
            );


        $currentProfileB =
            $this->getPlayerProfile(
                $currentPlayerIdB
            );


        $replacementProfileB =
            $this->getPlayerProfile(
                $replacementPlayerIdB
            );


        if (
            $currentProfileA === null
            ||
            $replacementProfileA === null
            ||
            $currentProfileB === null
            ||
            $replacementProfileB === null
        ) {

            return null;
        }


        /*
         * ========================================================
         * POSITION VALIDATION
         * ========================================================
         *
         * Each individual transfer must preserve the player's
         * position.
         */

        $currentPositionA =
            $currentProfileA[
                'player'
            ]['position']
            ?? null;


        $replacementPositionA =
            $replacementProfileA[
                'player'
            ]['position']
            ?? null;


        $currentPositionB =
            $currentProfileB[
                'player'
            ]['position']
            ?? null;


        $replacementPositionB =
            $replacementProfileB[
                'player'
            ]['position']
            ?? null;


        if (
            $currentPositionA === null
            ||
            $replacementPositionA === null
            ||
            $currentPositionB === null
            ||
            $replacementPositionB === null
        ) {

            return null;
        }


        if (
            $currentPositionA
            !==
            $replacementPositionA
            ||
            $currentPositionB
            !==
            $replacementPositionB
        ) {

            return null;
        }


        /*
         * ========================================================
         * BUILD TRANSFER PLAYER DATA
         * ========================================================
         */

        $currentPlayerA =
            $this->buildTransferDecisionPlayer(
                $currentProfileA
            );


        $replacementA =
            $this->buildTransferDecisionPlayer(
                $replacementProfileA
            );


        $currentPlayerB =
            $this->buildTransferDecisionPlayer(
                $currentProfileB
            );


        $replacementB =
            $this->buildTransferDecisionPlayer(
                $replacementProfileB
            );


        /*
         * ========================================================
         * COMBINATION EVALUATION
         * ========================================================
         */

        return $this->transferCombination
            ->evaluateCombination(
                $currentPlayerA,
                $replacementA,
                $currentPlayerB,
                $replacementB
            );
    }
    
    /**
     * Build the player data required by TransferDecision.
     */
    private function buildTransferDecisionPlayer(
        array $profile
    ): array {

        return [

            'player_id' =>
                (int) (
                    $profile[
                        'player'
                    ]['player_id']
                    ?? 0
                ),

            'name' =>
                $profile[
                    'player'
                ]['name']
                ?? null,

            'position' =>
                $profile[
                    'player'
                ]['position']
                ?? null,

            'team_name' =>
                $profile[
                    'team'
                ]['name']
                ?? null,

            'price' =>
                $profile[
                    'summary'
                ]['price']
                ?? null,

            'intelligence_score' =>
                $profile[
                    'summary'
                ]['intelligence_score']
                ?? null,

            'strength_rating' =>
                $profile[
                    'summary'
                ]['strength_rating']
                ?? null,

            'value_rating' =>
                $profile[
                    'summary'
                ]['value_rating']
                ?? null,

            'fixture_rating' =>
                $profile[
                    'summary'
                ]['fixture_rating']
                ?? null,

            'sample_confidence' =>
                $profile[
                    'performance'
                ]['sample_confidence']
                ?? null,

            'verdict' =>
                $profile[
                    'assessment'
                ]['verdict']
                ?? null
        ];
    }
    
    /**
     * Build the lightweight player data required by
     * TransferOptimizer from a player summary.
     */
    private function buildTransferOptimizerPlayer(
        array $summary
    ): ?array {

        $playerId =
            (int) (
                $summary[
                    'player_id'
                ]
                ?? 0
            );


        $position =
            $summary[
                'position'
            ]
            ?? null;


        if (
            $playerId <= 0
            ||
            !is_string(
                $position
            )
            ||
            trim(
                $position
            )
            === ''
        ) {

            return null;
        }


        $intelligence =
            $summary[
                'intelligence_score'
            ]
            ?? null;


        if (
            $intelligence === null
            ||
            !is_numeric(
                $intelligence
            )
        ) {

            return null;
        }


        return [

            'player_id' =>
                $playerId,

            'name' =>
                $summary[
                    'name'
                ]
                ?? null,

            'position' =>
                strtoupper(
                    trim(
                        $position
                    )
                ),

            'team_name' =>
                $summary[
                    'team_name'
                ]
                ?? null,

            'price' =>
                $summary[
                    'price'
                ]
                ?? null,

            'intelligence_score' =>
                $intelligence,

            'strength_rating' =>
                $summary[
                    'strength_rating'
                ]
                ?? null,

            'value_rating' =>
                $summary[
                    'value_rating'
                ]
                ?? null,

            'fixture_rating' =>
                $summary[
                    'fixture_rating'
                ]
                ?? null,

            'availability_rating' =>
                $summary[
                    'availability_rating'
                ]
                ?? null,

            'sample_confidence' =>
                $summary[
                    'sample_confidence'
                ]
                ?? null,

            'verdict' =>
                $summary[
                    'assessment_verdict'
                ]
                ?? null
        ];
    }
    
    /**
     * Build an optimizer candidate pool for one position.
     */
    private function buildTransferOptimizerPool(
        array $summaries,
        string $requiredPosition,
        array $excludedPlayerIds = []
    ): array {

        $pool =
            [];


        $requiredPosition =
            strtoupper(
                trim(
                    $requiredPosition
                )
            );


        foreach (
            $summaries
            as $summary
        ) {

            $playerId =
                (int) (
                    $summary[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
                ||
                in_array(
                    $playerId,
                    $excludedPlayerIds,
                    true
                )
            ) {

                continue;
            }


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $summary[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            if (
                $position
                !==
                $requiredPosition
            ) {

                continue;
            }


            /*
             * Ignore unavailable players at candidate-pool level.
             */
            $availability =
                $summary[
                    'availability_rating'
                ]
                ?? null;


            if (
                $availability !== null
                &&
                is_numeric(
                    $availability
                )
                &&
                (float) $availability < 60
            ) {

                continue;
            }


            $candidate =
                $this->buildTransferOptimizerPlayer(
                    $summary
                );


            if ($candidate === null) {
                continue;
            }


            $pool[] =
                $candidate;
        }


        return $pool;
    }
    
    /**
     * Automatically find the strongest affordable two-transfer
     * combinations for two outgoing players.
     */
    public function optimizeTransferCombination(
        int $currentPlayerIdA,
        int $currentPlayerIdB,
        float $bank = 0.0,
        int $limit = 10
    ): ?array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            $currentPlayerIdA <= 0
            ||
            $currentPlayerIdB <= 0
            ||
            $currentPlayerIdA === $currentPlayerIdB
            ||
            $bank < 0
            ||
            $limit <= 0
        ) {

            return null;
        }


        /*
         * ========================================================
         * LOAD OUTGOING PLAYER PROFILES
         * ========================================================
         *
         * Full profiles are only loaded for the two players being
         * sold. Candidate players use lightweight summaries.
         */

        $currentProfileA =
            $this->getPlayerProfile(
                $currentPlayerIdA
            );


        $currentProfileB =
            $this->getPlayerProfile(
                $currentPlayerIdB
            );


        if (
            $currentProfileA === null
            ||
            $currentProfileB === null
        ) {

            return null;
        }


        $currentPlayerA =
            $this->buildTransferDecisionPlayer(
                $currentProfileA
            );


        $currentPlayerB =
            $this->buildTransferDecisionPlayer(
                $currentProfileB
            );


        $positionA =
            $currentPlayerA[
                'position'
            ]
            ?? null;


        $positionB =
            $currentPlayerB[
                'position'
            ]
            ?? null;


        if (
            !is_string(
                $positionA
            )
            ||
            !is_string(
                $positionB
            )
        ) {

            return null;
        }


        /*
         * ========================================================
         * LIGHTWEIGHT CANDIDATE POOLS
         * ========================================================
         */

        $summaries =
            $this->getAllPlayerSummaries();


        $excludedIds = [

            $currentPlayerIdA,
            $currentPlayerIdB
        ];


        $candidatePoolA =
            $this->buildTransferOptimizerPool(
                $summaries,
                $positionA,
                $excludedIds
            );


        $candidatePoolB =
            $this->buildTransferOptimizerPool(
                $summaries,
                $positionB,
                $excludedIds
            );


        /*
         * ========================================================
         * OPTIMIZE
         * ========================================================
         */

        return $this->transferOptimizer
            ->optimize(
                $currentPlayerA,
                $currentPlayerB,
                $candidatePoolA,
                $candidatePoolB,
                $bank,
                $limit
            );
    }
    
    /**
     * Build the squad-ready player structure used by
     * SquadTransferIntelligence.
     */
    private function buildSquadPlayer(
        array $profile,
        array $pickMetadata
    ): array {

        return [

            'player_id' =>
                (int) (
                    $profile[
                        'player'
                    ]['player_id']
                    ?? 0
                ),

            'fpl_player_id' =>
                (int) (
                    $profile[
                        'player'
                    ]['fpl_player_id']
                    ?? 0
                ),

            'name' =>
                $profile[
                    'player'
                ]['name']
                ?? null,

            'position' =>
                $profile[
                    'player'
                ]['position']
                ?? null,

            'team_id' =>
                isset(
                    $profile[
                        'team'
                    ]['team_id']
                )
                    ? (int) $profile[
                        'team'
                    ]['team_id']
                    : null,

            'team_name' =>
                $profile[
                    'team'
                ]['name']
                ?? null,

            'price' =>
                $profile[
                    'summary'
                ]['price']
                ?? null,

            'strength_rating' =>
                $profile[
                    'summary'
                ]['strength_rating']
                ?? null,

            'value_rating' =>
                $profile[
                    'summary'
                ]['value_rating']
                ?? null,

            'fixture_rating' =>
                $profile[
                    'summary'
                ]['fixture_rating']
                ?? null,

            'availability_rating' =>
                $profile[
                    'summary'
                ]['availability_rating']
                ?? null,

            'intelligence_score' =>
                $profile[
                    'summary'
                ]['intelligence_score']
                ?? null,

            'sample_confidence' =>
                $profile[
                    'performance'
                ]['sample_confidence']
                ?? null,

            'verdict' =>
                $profile[
                    'assessment'
                ]['verdict']
                ?? null,

            'squad_position' =>
                isset(
                    $pickMetadata[
                        'squad_position'
                    ]
                )
                    ? (int) $pickMetadata[
                        'squad_position'
                    ]
                    : null,

            'multiplier' =>
                isset(
                    $pickMetadata[
                        'multiplier'
                    ]
                )
                    ? (int) $pickMetadata[
                        'multiplier'
                    ]
                    : null,

            'is_captain' =>
                (bool) (
                    $pickMetadata[
                        'is_captain'
                    ]
                    ?? false
                ),

            'is_vice_captain' =>
                (bool) (
                    $pickMetadata[
                        'is_vice_captain'
                    ]
                    ?? false
                )
        ];
    }
    
    /**
     * Convert an FPLSquadImporter result into the local
     * squad structure used by squad intelligence.
     */
    public function buildSquadFromFPLImport(
        array $importedSquad
    ): ?array {

        /*
         * ========================================================
         * VALIDATE IMPORT
         * ========================================================
         */

        if (
            (
                $importedSquad[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return null;
        }


        $importedPlayers =
            $importedSquad[
                'players'
            ]
            ?? null;


        if (
            !is_array(
                $importedPlayers
            )
            ||
            empty(
                $importedPlayers
            )
        ) {

            return null;
        }


        /*
         * ========================================================
         * MAP PLAYERS
         * ========================================================
         */

        $mappedPlayers =
            [];


        $unmapped =
            [];


        foreach (
            $importedPlayers
            as $pick
        ) {

            $fplPlayerId =
                (int) (
                    $pick[
                        'fpl_player_id'
                    ]
                    ?? 0
                );


            if ($fplPlayerId <= 0) {

                $unmapped[] = [

                    'fpl_player_id' =>
                        $fplPlayerId,

                    'reason' =>
                        'Invalid FPL player ID'
                ];

                continue;
            }


            $localPlayer =
                $this->playerRepository
                    ->getByFplPlayerId(
                        $fplPlayerId
                    );


            if ($localPlayer === null) {

                $unmapped[] = [

                    'fpl_player_id' =>
                        $fplPlayerId,

                    'reason' =>
                        'No matching local player'
                ];

                continue;
            }


            $localPlayerId =
                (int) (
                    $localPlayer[
                        'id'
                    ]
                    ?? 0
                );


            if ($localPlayerId <= 0) {

                $unmapped[] = [

                    'fpl_player_id' =>
                        $fplPlayerId,

                    'reason' =>
                        'Local player has invalid ID'
                ];

                continue;
            }


            $profile =
                $this->getPlayerProfile(
                    $localPlayerId
                );


            if ($profile === null) {

                $unmapped[] = [

                    'fpl_player_id' =>
                        $fplPlayerId,

                    'player_id' =>
                        $localPlayerId,

                    'reason' =>
                        'Local player profile could not be built'
                ];

                continue;
            }


            $mappedPlayers[] =
                $this->buildSquadPlayer(
                    $profile,
                    $pick
                );
        }


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'entry' =>
                $importedSquad[
                    'entry'
                ]
                ?? [],

            'gameweek' =>
                $importedSquad[
                    'gameweek'
                ]
                ?? null,

            'bank' =>
                $importedSquad[
                    'bank'
                ]
                ?? null,

            'team_value' =>
                $importedSquad[
                    'team_value'
                ]
                ?? null,

            'imported_count' =>
                count(
                    $importedPlayers
                ),

            'mapped_count' =>
                count(
                    $mappedPlayers
                ),

            'unmapped_count' =>
                count(
                    $unmapped
                ),

            'is_complete' =>
                (
                    count(
                        $importedPlayers
                    )
                    ===
                    count(
                        $mappedPlayers
                    )
                ),

            'unmapped' =>
                $unmapped,

            'players' =>
                $mappedPlayers
        ];
    }
    
    /**
     * Build the strongest legal Starting XI for the immediate
     * gameweek from a complete 15-player FPL squad.
     *
     * The squad itself provides identity / imported FPL metadata.
     * Current Player Intelligence summaries provide the latest
     * gameweek-specific scoring inputs.
     */
    public function getGameweekStartingXI(
        array $squad
    ): array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            count(
                $squad
            )
            !== 15
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Gameweek Starting XI requires a complete 15-player FPL squad.',

                'formation' =>
                    null,

                'starting_xi_score' =>
                    null,

                'bench_score' =>
                    null,

                'starting_xi' =>
                    [],

                'bench' =>
                    [],

                'formations' =>
                    [],

                'formation_count' =>
                    0,

                'squad_count' =>
                    count(
                        $squad
                    )
            ];
        }


        /*
         * --------------------------------------------------------
         * VALIDATE SQUAD IDENTITIES
         * --------------------------------------------------------
         */

        $squadPlayerIds =
            [];


        foreach (
            $squad
            as $squadPlayer
        ) {

            if (
                !is_array(
                    $squadPlayer
                )
            ) {

                return [

                    'status' =>
                        'invalid',

                    'message' =>
                        'Gameweek squad contains invalid player data.',

                    'formation' =>
                        null,

                    'starting_xi_score' =>
                        null,

                    'bench_score' =>
                        null,

                    'starting_xi' =>
                        [],

                    'bench' =>
                        [],

                    'formations' =>
                        [],

                    'formation_count' =>
                        0,

                    'squad_count' =>
                        count(
                            $squad
                        )
                ];
            }


            $playerId =
                (int) (
                    $squadPlayer[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
            ) {

                return [

                    'status' =>
                        'invalid',

                    'message' =>
                        'Gameweek squad contains an invalid player ID.',

                    'formation' =>
                        null,

                    'starting_xi_score' =>
                        null,

                    'bench_score' =>
                        null,

                    'starting_xi' =>
                        [],

                    'bench' =>
                        [],

                    'formations' =>
                        [],

                    'formation_count' =>
                        0,

                    'squad_count' =>
                        count(
                            $squad
                        )
                ];
            }


            if (
                in_array(
                    $playerId,
                    $squadPlayerIds,
                    true
                )
            ) {

                return [

                    'status' =>
                        'invalid',

                    'message' =>
                        'Gameweek squad contains duplicate players.',

                    'formation' =>
                        null,

                    'starting_xi_score' =>
                        null,

                    'bench_score' =>
                        null,

                    'starting_xi' =>
                        [],

                    'bench' =>
                        [],

                    'formations' =>
                        [],

                    'formation_count' =>
                        0,

                    'squad_count' =>
                        count(
                            $squad
                        )
                ];
            }


            $squadPlayerIds[] =
                $playerId;
        }


        /*
         * ========================================================
         * CURRENT PLAYER INTELLIGENCE
         * ========================================================
         *
         * Do not rely only on the intelligence values stored in the
         * mapped squad. Reload the lightweight summaries so the
         * recommendation uses the latest:
         *
         * - Player Intelligence
         * - strength
         * - immediate next fixture
         * - availability
         * - sample confidence
         */

        $summaries =
            $this->getAllPlayerSummaries();


        $summaryLookup =
            [];


        foreach (
            $summaries
            as $summary
        ) {

            $playerId =
                (int) (
                    $summary[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
            ) {

                continue;
            }


            $summaryLookup[
                $playerId
            ] =
                $summary;
        }


        /*
         * ========================================================
         * BUILD GAMEWEEK PLAYER INPUT
         * ========================================================
         */

        $gameweekSquad =
            [];


        $summaryMatches =
            0;


        $summaryFallbacks =
            0;


        foreach (
            $squad
            as $squadPlayer
        ) {

            $playerId =
                (int) $squadPlayer[
                    'player_id'
                ];


            $summary =
                $summaryLookup[
                    $playerId
                ]
                ?? null;


            if (
                $summary !== null
            ) {

                $summaryMatches++;

            } else {

                /*
                 * A missing summary must not silently remove a genuine
                 * member of the manager's 15-player squad.
                 *
                 * GameweekStartingXI already owns conservative fallback
                 * behaviour for missing intelligence inputs.
                 */

                $summaryFallbacks++;
            }


            $gameweekPlayer =
                $squadPlayer;


            /*
             * ----------------------------------------------------
             * IDENTITY / DISPLAY DATA
             * ----------------------------------------------------
             */

            $gameweekPlayer[
                'player_id'
            ] =
                $playerId;


            $gameweekPlayer[
                'name'
            ] =
                $summary[
                    'name'
                ]
                ?? $squadPlayer[
                    'name'
                ]
                ?? null;


            $gameweekPlayer[
                'position'
            ] =
                strtoupper(
                    trim(
                        (string) (
                            $summary[
                                'position'
                            ]
                            ?? $squadPlayer[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $gameweekPlayer[
                'team_id'
            ] =
                isset(
                    $squadPlayer[
                        'team_id'
                    ]
                )
                    ? (int) $squadPlayer[
                        'team_id'
                    ]
                    : (
                        isset(
                            $summary[
                                'team_id'
                            ]
                        )
                            ? (int) $summary[
                                'team_id'
                            ]
                            : null
                    );


            $gameweekPlayer[
                'team_name'
            ] =
                $summary[
                    'team_name'
                ]
                ?? $squadPlayer[
                    'team_name'
                ]
                ?? null;


            $gameweekPlayer[
                'price'
            ] =
                $summary[
                    'price'
                ]
                ?? $squadPlayer[
                    'price'
                ]
                ?? null;


            /*
             * ----------------------------------------------------
             * GAMEWEEK INTELLIGENCE INPUTS
             * ----------------------------------------------------
             */

            $gameweekPlayer[
                'intelligence_score'
            ] =
                $summary[
                    'intelligence_score'
                ]
                ?? $squadPlayer[
                    'intelligence_score'
                ]
                ?? null;


            $gameweekPlayer[
                'strength_rating'
            ] =
                $summary[
                    'strength_rating'
                ]
                ?? $squadPlayer[
                    'strength_rating'
                ]
                ?? null;


            /*
             * Immediate fixture only.
             *
             * Deliberately do not fall back to fixture_rating here
             * because that represents the broader fixture horizon.
             * GameweekStartingXI will use its neutral fixture fallback
             * if next_fixture_rating is unavailable.
             */

            $gameweekPlayer[
                'next_fixture_rating'
            ] =
                $summary[
                    'next_fixture_rating'
                ]
                ?? null;


            $gameweekPlayer[
                'availability_rating'
            ] =
                $summary[
                    'availability_rating'
                ]
                ?? $squadPlayer[
                    'availability_rating'
                ]
                ?? null;


            $gameweekPlayer[
                'sample_confidence'
            ] =
                $summary[
                    'sample_confidence'
                ]
                ?? $squadPlayer[
                    'sample_confidence'
                ]
                ?? null;


            /*
             * ----------------------------------------------------
             * PRESERVE CURRENT FPL SELECTION METADATA
             * ----------------------------------------------------
             *
             * This will later let the Gameweek UI compare the
             * recommended XI with the manager's current XI.
             */

            $gameweekPlayer[
                'squad_position'
            ] =
                isset(
                    $squadPlayer[
                        'squad_position'
                    ]
                )
                    ? (int) $squadPlayer[
                        'squad_position'
                    ]
                    : null;


            $gameweekPlayer[
                'multiplier'
            ] =
                isset(
                    $squadPlayer[
                        'multiplier'
                    ]
                )
                    ? (int) $squadPlayer[
                        'multiplier'
                    ]
                    : null;


            $gameweekPlayer[
                'is_captain'
            ] =
                (bool) (
                    $squadPlayer[
                        'is_captain'
                    ]
                    ?? false
                );


            $gameweekPlayer[
                'is_vice_captain'
            ] =
                (bool) (
                    $squadPlayer[
                        'is_vice_captain'
                    ]
                    ?? false
                );


            $gameweekSquad[] =
                $gameweekPlayer;
        }


        /*
         * ========================================================
         * STARTING XI OPTIMISATION
         * ========================================================
         */

        $optimizer =
            new GameweekStartingXI();


        $result =
            $optimizer
                ->optimize(
                    $gameweekSquad
                );


        /*
         * ========================================================
         * SERVICE METADATA
         * ========================================================
         */

        $result[
            'squad_count'
        ] =
            count(
                $squad
            );


        $result[
            'summary_matches'
        ] =
            $summaryMatches;


        $result[
            'summary_fallbacks'
        ] =
            $summaryFallbacks;


        return $result;
    }
    
    /**
     * Analyse a complete FPL squad and return ranked
     * Captain Intelligence recommendations.
     *
     * Captain Intelligence deliberately uses the lightweight
     * application-level player summaries because those contain
     * the immediate next-fixture rating and the normalised
     * attacking-performance ratings required by the captain model.
     */
    public function getCaptainRecommendations(
        array $squad,
        int $limit = 5
    ): array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            $limit < 2
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Captain recommendation limit must be at least two.',

                'captain' =>
                    null,

                'vice_captain' =>
                    null,

                'alternatives' =>
                    [],

                'rankings' =>
                    []
            ];
        }


        if (
            count(
                $squad
            )
            !== 15
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Captain Intelligence requires a complete 15-player FPL squad.',

                'captain' =>
                    null,

                'vice_captain' =>
                    null,

                'alternatives' =>
                    [],

                'rankings' =>
                    []
            ];
        }


        /*
         * --------------------------------------------------------
         * VALIDATE SQUAD PLAYER IDS
         * --------------------------------------------------------
         */

        $squadPlayerIds =
            [];


        foreach (
            $squad
            as $squadPlayer
        ) {

            $playerId =
                (int) (
                    $squadPlayer[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
            ) {

                return [

                    'status' =>
                        'invalid',

                    'message' =>
                        'Captain Intelligence squad contains an invalid player.',

                    'captain' =>
                        null,

                    'vice_captain' =>
                        null,

                    'alternatives' =>
                        [],

                    'rankings' =>
                        []
                ];
            }


            if (
                in_array(
                    $playerId,
                    $squadPlayerIds,
                    true
                )
            ) {

                return [

                    'status' =>
                        'invalid',

                    'message' =>
                        'Captain Intelligence squad contains duplicate players.',

                    'captain' =>
                        null,

                    'vice_captain' =>
                        null,

                    'alternatives' =>
                        [],

                    'rankings' =>
                        []
                ];
            }


            $squadPlayerIds[] =
                $playerId;
        }


        /*
         * ========================================================
         * PLAYER SUMMARY LOOKUP
         * ========================================================
         */

        $summaries =
            $this->getAllPlayerSummaries();


        $summaryLookup =
            [];


        foreach (
            $summaries
            as $summary
        ) {

            $playerId =
                (int) (
                    $summary[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
            ) {

                continue;
            }


            $summaryLookup[
                $playerId
            ] =
                $summary;
        }


        /*
         * ========================================================
         * CAPTAIN EVALUATION
         * ========================================================
         */

        $captainIntelligence =
            new CaptainIntelligence();


        $rankings =
            [];


        $rejectedPlayers =
            [];


        foreach (
            $squad
            as $squadPlayer
        ) {

            $playerId =
                (int) $squadPlayer[
                    'player_id'
                ];


            $summary =
                $summaryLookup[
                    $playerId
                ]
                ?? null;


            if (
                $summary === null
            ) {

                $rejectedPlayers[] = [

                    'player_id' =>
                        $playerId,

                    'reason' =>
                        'Player summary could not be found.'
                ];

                continue;
            }


            $captainInput = [

                'player_id' =>
                    $playerId,

                'name' =>
                    (string) (
                        $summary[
                            'name'
                        ]
                        ?? $squadPlayer[
                            'name'
                        ]
                        ?? ''
                    ),

                'position' =>
                    strtoupper(
                        trim(
                            (string) (
                                $summary[
                                    'position'
                                ]
                                ?? $squadPlayer[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    ),

                'strength_score' =>
                    $summary[
                        'strength_rating'
                    ]
                    ?? null,

                /*
                 * Captain Intelligence uses the immediate fixture,
                 * not the general next-five fixture rating.
                 */
                'fixture_score' =>
                    $summary[
                        'next_fixture_rating'
                    ]
                    ?? null,

                'sample_confidence' =>
                    $summary[
                        'sample_confidence'
                    ]
                    ?? null,

                'availability' =>
                    $summary[
                        'availability_rating'
                    ]
                    ?? null,

                'goals_rating' =>
                    $summary[
                        'goals_rating'
                    ]
                    ?? null,

                'assists_rating' =>
                    $summary[
                        'assists_rating'
                    ]
                    ?? null,

                'expected_goals_rating' =>
                    $summary[
                        'expected_goals_rating'
                    ]
                    ?? null,

                'expected_assists_rating' =>
                    $summary[
                        'expected_assists_rating'
                    ]
                    ?? null
            ];


            $result =
                $captainIntelligence
                    ->evaluate(
                        $captainInput
                    );


            if (
                (
                    $result[
                        'status'
                    ]
                    ?? null
                )
                !==
                'success'
            ) {

                $rejectedPlayers[] = [

                    'player_id' =>
                        $playerId,

                    'name' =>
                        $captainInput[
                            'name'
                        ],

                    'reason' =>
                        $result[
                            'message'
                        ]
                        ?? 'Captain evaluation failed.'
                ];

                continue;
            }


            /*
             * ----------------------------------------------------
             * SQUAD / DISPLAY METADATA
             * ----------------------------------------------------
             */

            $result[
                'team_id'
            ] =
                isset(
                    $squadPlayer[
                        'team_id'
                    ]
                )
                    ? (int) $squadPlayer[
                        'team_id'
                    ]
                    : null;


            $result[
                'team_name'
            ] =
                $summary[
                    'team_name'
                ]
                ?? $squadPlayer[
                    'team_name'
                ]
                ?? null;


            $result[
                'price'
            ] =
                $summary[
                    'price'
                ]
                ?? $squadPlayer[
                    'price'
                ]
                ?? null;


            $result[
                'squad_position'
            ] =
                isset(
                    $squadPlayer[
                        'squad_position'
                    ]
                )
                    ? (int) $squadPlayer[
                        'squad_position'
                    ]
                    : null;


            /*
             * Preserve the captaincy imported from FPL so the UI
             * can later compare the user's current selection with
             * the intelligence recommendation.
             */

            $result[
                'current_is_captain'
            ] =
                (bool) (
                    $squadPlayer[
                        'is_captain'
                    ]
                    ?? false
                );


            $result[
                'current_is_vice_captain'
            ] =
                (bool) (
                    $squadPlayer[
                        'is_vice_captain'
                    ]
                    ?? false
                );


            $rankings[] =
                $result;
        }


        /*
         * ========================================================
         * MINIMUM USABLE RESULT
         * ========================================================
         */

        if (
            count(
                $rankings
            )
            < 2
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Not enough squad players could be evaluated for captaincy.',

                'captain' =>
                    null,

                'vice_captain' =>
                    null,

                'alternatives' =>
                    [],

                'rankings' =>
                    $rankings,

                'rejected_players' =>
                    $rejectedPlayers
            ];
        }


        /*
         * ========================================================
         * RANKING
         * ========================================================
         */

        usort(
            $rankings,
            function (
                array $a,
                array $b
            ): int {

                /*
                 * Primary ordering:
                 * Captain Score.
                 */

                $scoreComparison =
                    (
                        $b[
                            'captain_score'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'captain_score'
                        ]
                        ?? 0
                    );


                if (
                    $scoreComparison !== 0
                ) {

                    return $scoreComparison;
                }


                /*
                 * Tie-breaker 1:
                 * attacking upside.
                 */

                $threatComparison =
                    (
                        $b[
                            'components'
                        ][
                            'attacking_threat'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'components'
                        ][
                            'attacking_threat'
                        ]
                        ?? 0
                    );


                if (
                    $threatComparison !== 0
                ) {

                    return $threatComparison;
                }


                /*
                 * Tie-breaker 2:
                 * underlying player strength.
                 */

                return (
                    $b[
                        'components'
                    ][
                        'strength'
                    ]
                    ?? 0
                )
                <=>
                (
                    $a[
                        'components'
                    ][
                        'strength'
                    ]
                    ?? 0
                );
            }
        );


        /*
         * Add explicit ranking numbers after sorting.
         */

        foreach (
            $rankings
            as $index => &$ranking
        ) {

            $ranking[
                'rank'
            ] =
                $index + 1;
        }


        unset(
            $ranking
        );


        /*
         * ========================================================
         * RECOMMENDATIONS
         * ========================================================
         */

        $recommendationLimit =
            min(
                $limit,
                count(
                    $rankings
                )
            );


        $recommendations =
            array_slice(
                $rankings,
                0,
                $recommendationLimit
            );


        $captain =
            $recommendations[
                0
            ];


        $viceCaptain =
            $recommendations[
                1
            ];


        $alternatives =
            array_slice(
                $recommendations,
                2
            );


        return [

            'status' =>
                'success',

            'message' =>
                'Captain Intelligence recommendations generated successfully.',

            'captain' =>
                $captain,

            'vice_captain' =>
                $viceCaptain,

            'alternatives' =>
                $alternatives,

            /*
             * Complete 15-player ordering is retained so future UI
             * and diagnostics can inspect the entire squad rather
             * than only the displayed recommendations.
             */
            'rankings' =>
                $rankings,

            'squad_count' =>
                count(
                    $squad
                ),

            'evaluated_count' =>
                count(
                    $rankings
                ),

            'rejected_count' =>
                count(
                    $rejectedPlayers
                ),

            'rejected_players' =>
                $rejectedPlayers,

            'recommendation_limit' =>
                $recommendationLimit
        ];
    }
    
    
    /**
     * Build the complete manager-level Gameweek Intelligence decision
     * from the current 15-player FPL squad.
     *
     * This orchestration method deliberately reuses the existing
     * production intelligence layers rather than duplicating any
     * scoring logic.
     */
    public function getGameweekDecision(
        array $squad,
        float $bank = 0.0
    ): array {

        /*
         * ========================================================
         * BASIC VALIDATION
         * ========================================================
         */

        if (
            count(
                $squad
            )
            !== 15
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Gameweek Decision Intelligence requires a complete 15-player FPL squad.',

                'overall_action' =>
                    null,

                'gameweek' =>
                    null,

                'captaincy' =>
                    null,

                'transfers' =>
                    null,

                'decision' =>
                    null
            ];
        }


        if (
            $bank < 0
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    'Gameweek Decision Intelligence cannot use a negative bank value.',

                'overall_action' =>
                    null,

                'gameweek' =>
                    null,

                'captaincy' =>
                    null,

                'transfers' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * GAMEWEEK STARTING XI
         * ========================================================
         */

        $gameweekResult =
            $this->getGameweekStartingXI(
                $squad
            );


        if (
            (
                $gameweekResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    $gameweekResult[
                        'message'
                    ]
                    ?? 'Gameweek Starting XI intelligence could not be generated.',

                'overall_action' =>
                    null,

                'gameweek' =>
                    $gameweekResult,

                'captaincy' =>
                    null,

                'transfers' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * CAPTAIN INTELLIGENCE
         * ========================================================
         */

        $captainResult =
            $this->getCaptainRecommendations(
                $squad,
                5
            );


        if (
            (
                $captainResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    $captainResult[
                        'message'
                    ]
                    ?? 'Captain Intelligence could not be generated.',

                'overall_action' =>
                    null,

                'gameweek' =>
                    $gameweekResult,

                'captaincy' =>
                    $captainResult,

                'transfers' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * TRANSFER INTELLIGENCE
         * ========================================================
         *
         * We deliberately reuse the squad-aware single-transfer
         * recommendation service here.
         *
         * This gives the decision engine one clear transfer signal
         * without immediately complicating v1 with double-transfer
         * combinations.
         */

        $transferResult =
            $this->getSquadTransferRecommendations(
                $squad,
                $bank,
                5,
                5
            );


        /*
         * ========================================================
         * DECISION ENGINE
         * ========================================================
         */

        $decisionEngine =
            new GameweekDecisionEngine();


        $decision =
            $decisionEngine
                ->evaluate(
                    $gameweekResult,
                    $captainResult,
                    $transferResult
                );


        if (
            (
                $decision[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    $decision[
                        'message'
                    ]
                    ?? 'Gameweek Decision Intelligence could not be generated.',

                'overall_action' =>
                    null,

                'gameweek' =>
                    $gameweekResult,

                'captaincy' =>
                    $captainResult,

                'transfers' =>
                    $transferResult,

                'decision' =>
                    $decision
            ];
        }


        /*
         * ========================================================
         * COMPLETE RESULT
         * ========================================================
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Complete Gameweek Intelligence decision generated successfully.',

            'overall_action' =>
                $decision[
                    'overall_action'
                ]
                ?? null,

            'gameweek' =>
                $gameweekResult,

            'captaincy' =>
                $captainResult,

            'transfers' =>
                $transferResult,

            'decision' =>
                $decision
        ];
    }
    
    /**
     * Build the lightweight player structure required by
     * SquadTransferOptimizer / TransferDecision.
     */
    private function buildSquadTransferCandidate(
        array $summary
    ): ?array {

        $playerId =
            (int) (
                $summary[
                    'player_id'
                ]
                ?? 0
            );


        $position =
            strtoupper(
                trim(
                    (string) (
                        $summary[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        if (
            $playerId <= 0
            ||
            $position === ''
        ) {

            return null;
        }


        $teamId =
            isset(
                $summary[
                    'team_id'
                ]
            )
                ? (int) $summary[
                    'team_id'
                ]
                : 0;


        /*
         * Some summary structures may not expose team_id.
         * Fall back to the repository if required.
         */
        if ($teamId <= 0) {

            $player =
                $this->playerRepository
                    ->getById(
                        $playerId
                    );


            if ($player === null) {
                return null;
            }


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );
        }


        if ($teamId <= 0) {
            return null;
        }


        /*
         * TransferDecision expects sample confidence in the
         * 0-1 range.
         */
        $confidence =
            $summary[
                'sample_confidence'
            ]
            ?? null;


        if (
            $confidence !== null
            &&
            is_numeric(
                $confidence
            )
        ) {

            $confidence =
                (float) $confidence;


            if ($confidence > 1) {

                $confidence /=
                    100;
            }


            $confidence =
                max(
                    0,
                    min(
                        1,
                        $confidence
                    )
                );
        }


        return [

            'player_id' =>
                $playerId,

            'name' =>
                $summary[
                    'name'
                ]
                ?? null,

            'team_id' =>
                $teamId,

            'team_name' =>
                $summary[
                    'team_name'
                ]
                ?? null,

            'position' =>
                $position,

            'price' =>
                $summary[
                    'price'
                ]
                ?? null,

            'intelligence_score' =>
                $summary[
                    'intelligence_score'
                ]
                ?? null,

            'strength_rating' =>
                $summary[
                    'strength_rating'
                ]
                ?? null,

            'value_rating' =>
                $summary[
                    'value_rating'
                ]
                ?? null,

            'fixture_rating' =>
                $summary[
                    'fixture_rating'
                ]
                ?? null,

            'availability_rating' =>
                $summary[
                    'availability_rating'
                ]
                ?? null,

            'sample_confidence' =>
                $confidence,

            'verdict' =>
                $summary[
                    'assessment_verdict'
                ]
                ?? null
        ];
    }
    
    /**
     * Build the complete lightweight candidate pool used by
     * squad transfer optimisation.
     */
    private function buildSquadTransferCandidatePool(): array
    {

        $summaries =
            $this->getAllPlayerSummaries();


        $candidates =
            [];


        foreach (
            $summaries
            as $summary
        ) {

            $candidate =
                $this->buildSquadTransferCandidate(
                    $summary
                );


            if ($candidate === null) {
                continue;
            }


            $candidates[] =
                $candidate;
        }


        return $candidates;
    }
    
    /**
     * Analyse a squad and return squad-aware single-transfer
     * recommendations.
     */
    public function getSquadTransferRecommendations(
        array $squad,
        float $bank = 0.0,
        int $priorityLimit = 5,
        int $replacementLimit = 5
    ): array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            $bank < 0
            ||
            $priorityLimit <= 0
            ||
            $replacementLimit <= 0
        ) {

            return [

                'analysis' =>
                    null,

                'recommendations' =>
                    null
            ];
        }


        /*
         * ========================================================
         * SQUAD INTELLIGENCE
         * ========================================================
         */

        $squadIntelligence =
            new SquadTransferIntelligence();


        $analysis =
            $squadIntelligence
                ->analyzeSquad(
                    $squad,
                    $bank
                );


        /*
         * Return the analysis even when invalid so callers can
         * inspect the validation issues.
         */
        if (
            (
                $analysis[
                    'validation'
                ]['is_valid']
                ?? false
            )
            !== true
        ) {

            return [

                'analysis' =>
                    $analysis,

                'recommendations' =>
                    null
            ];
        }


        /*
         * ========================================================
         * CANDIDATE POOL
         * ========================================================
         */

        $candidatePool =
            $this->buildSquadTransferCandidatePool();


        /*
         * ========================================================
         * SQUAD TRANSFER OPTIMIZER
         * ========================================================
         */

        $optimizer =
            new SquadTransferOptimizer();


        $recommendations =
            $optimizer
                ->findBestSingleTransfers(
                    $analysis,
                    $candidatePool,
                    $priorityLimit,
                    $replacementLimit
                );


        return [

            'analysis' =>
                $analysis,

            'recommendations' =>
                $recommendations
        ];
    }
    
    /**
     * Analyse a squad and return squad-aware
     * two-transfer restructuring recommendations.
     */
    public function getSquadDoubleTransferRecommendations(
        array $squad,
        float $bank = 0.0,
        int $outgoingLimit = 5,
        int $resultLimit = 10
    ): array {

        /*
         * ========================================================
         * VALIDATION
         * ========================================================
         */

        if (
            $bank < 0
            ||
            $outgoingLimit < 2
            ||
            $resultLimit <= 0
        ) {

            return [

                'analysis' =>
                    null,

                'recommendations' =>
                    null
            ];
        }


        /*
         * ========================================================
         * SQUAD INTELLIGENCE
         * ========================================================
         */

        $squadIntelligence =
            new SquadTransferIntelligence();


        $analysis =
            $squadIntelligence
                ->analyzeSquad(
                    $squad,
                    $bank
                );


        /*
         * Return the squad analysis even when invalid so
         * callers can inspect the validation issues.
         */
        if (
            (
                $analysis[
                    'validation'
                ]['is_valid']
                ?? false
            )
            !== true
        ) {

            return [

                'analysis' =>
                    $analysis,

                'recommendations' =>
                    null
            ];
        }


        /*
         * ========================================================
         * CANDIDATE POOL
         * ========================================================
         */

        $candidatePool =
            $this->buildSquadTransferCandidatePool();


        /*
         * ========================================================
         * DOUBLE TRANSFER OPTIMIZER
         * ========================================================
         */

        $optimizer =
            new SquadTransferOptimizer();


        $recommendations =
            $optimizer
                ->findBestDoubleTransfers(
                    $analysis,
                    $candidatePool,
                    $outgoingLimit,
                    $resultLimit
                );


        return [

            'analysis' =>
                $analysis,

            'recommendations' =>
                $recommendations
        ];
    }
}