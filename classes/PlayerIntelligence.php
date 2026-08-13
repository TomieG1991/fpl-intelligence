<?php

class PlayerIntelligence
{
    /**
     * Analyse a player's upcoming fixture run.
     */
    public function analyseFixtureRun(
        array $player,
        array $fixtures,
        array $teamStrengths,
        FixtureIntelligence $fixtureIntelligence
    ): array {

        if (!isset($player['team_id'])) {

            throw new InvalidArgumentException(
                'Player team_id is required'
            );
        }


        $fixtureRun =
            $fixtureIntelligence->analyseFixtureRun(
                $fixtures,
                $teamStrengths,
                (int) $player['team_id']
            );


        return [

            'player_id' =>
                (int) (
                    $player['fpl_player_id']
                    ?? $player['id']
                    ?? 0
                ),

            'player_name' =>
                trim(
                    ($player['first_name'] ?? '')
                    . ' '
                    . ($player['second_name'] ?? '')
                ),

            'team_id' =>
                (int) $player['team_id'],

            'fixtures' =>
                $fixtureRun,

            'rolling_averages' =>
                $fixtureIntelligence
                    ->calculateRollingAverages(
                        $fixtureRun
                    ),

            'best_run' =>
                $fixtureIntelligence->findBestRun(
                    $fixtureRun,
                    5
                ),

            'worst_run' =>
                $fixtureIntelligence->findWorstRun(
                    $fixtureRun,
                    5
                ),

            'trend' =>
                $fixtureIntelligence->calculateTrend(
                    $fixtureRun
                )
        ];
    }


    /**
     * Calculate a player's strength rating.
     *
     * The rating is expected to already represent
     * the player's calculated footballing strength.
     */
    public function calculateStrengthRating(
        ?float $strength
    ): ?float {

        if ($strength === null) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    $strength
                )
            ),
            2
        );
    }


    /**
     * Calculate player fixture rating.
     *
     * Uses the next 5 fixture average as the
     * player's immediate fixture rating.
     */
    public function calculateFixtureRating(
        ?array $rollingAverages
    ): ?float {

        if (
            $rollingAverages === null
            ||
            !array_key_exists(
                'next_5',
                $rollingAverages
            )
            ||
            $rollingAverages['next_5'] === null
        ) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    (float) $rollingAverages['next_5']
                )
            ),
            2
        );
    }


    /**
     * Convert fixture rating into a player-friendly label.
     */
    public function getFixtureLabel(
        ?float $fixtureRating
    ): ?string {

        if ($fixtureRating === null) {
            return null;
        }


        if ($fixtureRating >= 80) {
            return 'Excellent';
        }


        if ($fixtureRating >= 65) {
            return 'Good';
        }


        if ($fixtureRating >= 50) {
            return 'Average';
        }


        if ($fixtureRating >= 35) {
            return 'Difficult';
        }


        return 'Very Difficult';
    }


    /**
     * Calculate the player's overall intelligence score.
     *
     * Strength = 60%
     * Fixtures = 40%
     */
    public function calculateIntelligenceScore(
        ?float $strengthRating,
        ?float $fixtureRating
    ): ?float {

        if (
            $strengthRating === null
            &&
            $fixtureRating === null
        ) {
            return null;
        }


        if ($strengthRating === null) {
            return round(
                max(
                    0,
                    min(
                        100,
                        $fixtureRating
                    )
                ),
                2
            );
        }


        if ($fixtureRating === null) {
            return round(
                max(
                    0,
                    min(
                        100,
                        $strengthRating
                    )
                ),
                2
            );
        }


        return round(
            max(
                0,
                min(
                    100,
                    ($strengthRating * 0.60)
                    +
                    ($fixtureRating * 0.40)
                )
            ),
            2
        );
    }


    /**
     * Convert intelligence score into a label.
     */
    public function getIntelligenceLabel(
        ?float $intelligenceScore
    ): ?string {

        if ($intelligenceScore === null) {
            return null;
        }


        if ($intelligenceScore >= 80) {
            return 'Elite';
        }


        if ($intelligenceScore >= 65) {
            return 'Strong';
        }


        if ($intelligenceScore >= 50) {
            return 'Average';
        }


        if ($intelligenceScore >= 35) {
            return 'Weak';
        }


        return 'Poor';
    }


    /**
     * Build a complete player intelligence profile.
     */
    public function buildProfile(
        array $player,
        array $fixtureProfile
    ): array {

        $strengthRating =
            $this->calculateStrengthRating(
                isset($player['strength_rating'])
                    ? (float) $player['strength_rating']
                    : (
                        isset($player['strength'])
                            ? (float) $player['strength']
                            : null
                    )
            );


        $fixtureRating =
            $this->calculateFixtureRating(
                $fixtureProfile['rolling_averages']
                ?? null
            );


        $fixtureLabel =
            $this->getFixtureLabel(
                $fixtureRating
            );


        $intelligenceScore =
            $this->calculateIntelligenceScore(
                $strengthRating,
                $fixtureRating
            );


        return [

            'player_id' =>
                (int) (
                    $player['player_id']
                    ??
                    $player['fpl_player_id']
                    ??
                    $player['id']
                    ??
                    0
                ),

            'player_name' =>
                $player['name']
                ??
                (
                    trim(
                        ($player['first_name'] ?? '')
                        . ' '
                        . ($player['second_name'] ?? '')
                    )
                ),

            'team_id' =>
                (int) (
                    $player['team_id']
                    ?? 0
                ),

            'strength_rating' =>
                $strengthRating,

            'fixture_rating' =>
                $fixtureRating,

            'fixture_label' =>
                $fixtureLabel,

            'intelligence_score' =>
                $intelligenceScore,

            'intelligence_label' =>
                $this->getIntelligenceLabel(
                    $intelligenceScore
                ),

            'rolling_averages' =>
                $fixtureProfile['rolling_averages']
                ?? [

                    'next_5' => null,
                    'next_6' => null,
                    'next_8' => null,
                    'next_10' => null
                ],

            'best_run' =>
                $fixtureProfile['best_run']
                ?? null,

            'worst_run' =>
                $fixtureProfile['worst_run']
                ?? null,

            'trend' =>
                $fixtureProfile['trend']
                ?? 'Insufficient Data',

            'fixture_count' =>
                count(
                    $fixtureProfile['fixtures']
                    ?? []
                ),

            'fixtures' =>
                $fixtureProfile['fixtures']
                ?? []
        ];
    }
}