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

        if (
            !isset($player['team_id'])
            ||
            !is_numeric($player['team_id'])
        ) {

            throw new InvalidArgumentException(
                'Player team_id is required'
            );
        }


        $teamId =
            (int) $player['team_id'];


        $fixtureRun =
            $fixtureIntelligence
                ->analyseFixtureRun(
                    $fixtures,
                    $teamStrengths,
                    $teamId
                );


        $playerName =
            $this->resolvePlayerName(
                $player
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
                $playerName,

            'team_id' =>
                $teamId,

            'fixtures' =>
                $fixtureRun,

            'rolling_averages' =>
                $fixtureIntelligence
                    ->calculateRollingAverages(
                        $fixtureRun
                    ),

            'best_run' =>
                $fixtureIntelligence
                    ->findBestRun(
                        $fixtureRun,
                        5
                    ),

            'worst_run' =>
                $fixtureIntelligence
                    ->findWorstRun(
                        $fixtureRun,
                        5
                    ),

            'trend' =>
                $fixtureIntelligence
                    ->calculateTrend(
                        $fixtureRun
                    )
        ];
    }


    /**
     * Calculate a player's strength rating.
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
     * Calculate player fixture rating using
     * the next five fixture average.
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
            ||
            !is_numeric(
                $rollingAverages['next_5']
            )
        ) {

            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    (float)
                        $rollingAverages['next_5']
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


        $fixtureRating =
            max(
                0,
                min(
                    100,
                    $fixtureRating
                )
            );


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
     * Calculate the legacy fixture-focused
     * player intelligence score.
     *
     * Strength = 60%
     * Fixtures = 40%
     *
     * Modern overall player intelligence is handled
     * by PlayerIntelligenceScore.
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


        if ($strengthRating !== null) {

            $strengthRating =
                max(
                    0,
                    min(
                        100,
                        $strengthRating
                    )
                );
        }


        if ($fixtureRating !== null) {

            $fixtureRating =
                max(
                    0,
                    min(
                        100,
                        $fixtureRating
                    )
                );
        }


        if ($strengthRating === null) {

            return round(
                $fixtureRating,
                2
            );
        }


        if ($fixtureRating === null) {

            return round(
                $strengthRating,
                2
            );
        }


        $score =
            ($strengthRating * 0.60)
            +
            ($fixtureRating * 0.40);


        return round(
            max(
                0,
                min(
                    100,
                    $score
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


        $intelligenceScore =
            max(
                0,
                min(
                    100,
                    $intelligenceScore
                )
            );


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
     * Build a complete player fixture-intelligence profile.
     */
    public function buildProfile(
        array $player,
        array $fixtureProfile
    ): array {

        $strength =
            $this->getNullableNumericValue(
                $player,
                'strength_rating'
            );


        if ($strength === null) {

            $strength =
                $this->getNullableNumericValue(
                    $player,
                    'strength'
                );
        }


        $strengthRating =
            $this->calculateStrengthRating(
                $strength
            );


        $rollingAverages =
            isset(
                $fixtureProfile['rolling_averages']
            )
            &&
            is_array(
                $fixtureProfile['rolling_averages']
            )
                ? $fixtureProfile['rolling_averages']
                : [
                    'next_5' => null,
                    'next_6' => null,
                    'next_8' => null,
                    'next_10' => null
                ];


        $fixtureRating =
            $this->calculateFixtureRating(
                $rollingAverages
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


        $fixtures =
            isset(
                $fixtureProfile['fixtures']
            )
            &&
            is_array(
                $fixtureProfile['fixtures']
            )
                ? $fixtureProfile['fixtures']
                : [];


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
                $this->resolvePlayerName(
                    $player
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
                $rollingAverages,

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
                    $fixtures
                ),

            'fixtures' =>
                $fixtures
        ];
    }


    /**
     * Resolve a player name from the modern
     * or legacy player structures.
     */
    private function resolvePlayerName(
        array $player
    ): ?string {

        if (
            isset($player['name'])
            &&
            trim(
                (string) $player['name']
            ) !== ''
        ) {

            return trim(
                (string) $player['name']
            );
        }


        if (
            isset($player['web_name'])
            &&
            trim(
                (string) $player['web_name']
            ) !== ''
        ) {

            return trim(
                (string) $player['web_name']
            );
        }


        $fullName =
            trim(
                (
                    $player['first_name']
                    ?? ''
                )
                . ' '
                . (
                    $player['second_name']
                    ?? ''
                )
            );


        return $fullName !== ''
            ? $fullName
            : null;
    }


    /**
     * Safely read a nullable numeric field.
     */
    private function getNullableNumericValue(
        array $data,
        string $field
    ): ?float {

        if (
            !isset($data[$field])
            ||
            !is_numeric(
                $data[$field]
            )
        ) {

            return null;
        }


        return (float)
            $data[$field];
    }
}