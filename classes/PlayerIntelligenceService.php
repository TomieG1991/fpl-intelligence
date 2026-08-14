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


                $summary['bps_per_90'] =
                    $profile[
                        'performance'
                    ]['bps_per_90']
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

        return [

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
    }
}