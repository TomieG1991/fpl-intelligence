<?php

class TeamIntelligence
{
    /**
     * Calculate a normalised team strength score.
     */
    public function calculateStrengthScore(
        float $strength
    ): float {

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
     * Calculate the overall team intelligence score.
     *
     * Strength contributes 60%.
     * Fixture rating contributes 40%.
     *
     * Missing data results in a null score.
     */
    public function calculateIntelligenceScore(
        ?float $strength,
        ?float $fixtureRating
    ): ?float {

        if (
            $strength === null
            ||
            $fixtureRating === null
        ) {

            return null;
        }


        $strength =
            $this->calculateStrengthScore(
                $strength
            );


        $fixtureRating =
            $this->calculateStrengthScore(
                $fixtureRating
            );


        $score =
            ($strength * 0.60)
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
     * Convert an intelligence score into
     * a human-readable label.
     */
    public function getIntelligenceLabel(
        ?float $intelligenceScore
    ): ?string {

        if ($intelligenceScore === null) {
            return null;
        }


        if ($intelligenceScore >= 85) {
            return 'Elite';
        }


        if ($intelligenceScore >= 70) {
            return 'Strong';
        }


        if ($intelligenceScore >= 55) {
            return 'Average';
        }


        if ($intelligenceScore >= 40) {
            return 'Weak';
        }


        return 'Poor';
    }


    /**
     * Legacy home-strength fallback.
     *
     * Modern team models should provide an explicit
     * home strength value instead.
     */
    public function calculateHomeStrength(
        float $strength
    ): float {

        $strength =
            $this->calculateStrengthScore(
                $strength
            );


        return round(
            min(
                100,
                $strength * 1.05
            ),
            2
        );
    }


    /**
     * Legacy away-strength fallback.
     *
     * Modern team models should provide an explicit
     * away strength value instead.
     */
    public function calculateAwayStrength(
        float $strength
    ): float {

        $strength =
            $this->calculateStrengthScore(
                $strength
            );


        return round(
            max(
                0,
                $strength * 0.95
            ),
            2
        );
    }


    /**
     * Resolve team identity from either the newer
     * canonical team model or the older flat structure.
     */
    private function getTeamId(
        array $team
    ): int {

        return (int) (
            $team['id']
            ??
            $team['team_id']
            ??
            0
        );
    }


    /**
     * Resolve the team's overall strength.
     *
     * Modern TeamStrengthModel output is preferred.
     * The older "strength" field remains supported
     * for backward compatibility.
     */
    private function getOverallStrength(
        array $team
    ): ?float {

        if (
            isset($team['overall'])
            &&
            is_numeric(
                $team['overall']
            )
        ) {

            return $this->calculateStrengthScore(
                (float) $team['overall']
            );
        }


        if (
            isset($team['strength'])
            &&
            is_numeric(
                $team['strength']
            )
        ) {

            return $this->calculateStrengthScore(
                (float) $team['strength']
            );
        }


        return null;
    }


    /**
     * Resolve home strength.
     *
     * Explicit venue-aware model data is preferred.
     */
    private function getHomeStrength(
        array $team,
        ?float $overallStrength
    ): ?float {

        if (
            isset($team['home'])
            &&
            is_numeric(
                $team['home']
            )
        ) {

            return $this->calculateStrengthScore(
                (float) $team['home']
            );
        }


        if ($overallStrength === null) {
            return null;
        }


        return $this->calculateHomeStrength(
            $overallStrength
        );
    }


    /**
     * Resolve away strength.
     *
     * Explicit venue-aware model data is preferred.
     */
    private function getAwayStrength(
        array $team,
        ?float $overallStrength
    ): ?float {

        if (
            isset($team['away'])
            &&
            is_numeric(
                $team['away']
            )
        ) {

            return $this->calculateStrengthScore(
                (float) $team['away']
            );
        }


        if ($overallStrength === null) {
            return null;
        }


        return $this->calculateAwayStrength(
            $overallStrength
        );
    }


    /**
     * Calculate the overall team intelligence profile.
     */
    public function buildTeamProfile(
        array $team
    ): array {

        $strengthScore =
            $this->getOverallStrength(
                $team
            );


        $homeStrength =
            $this->getHomeStrength(
                $team,
                $strengthScore
            );


        $awayStrength =
            $this->getAwayStrength(
                $team,
                $strengthScore
            );


        return [

            'team_id' =>
                $this->getTeamId(
                    $team
                ),

            'team_name' =>
                $team['name']
                ?? null,

            'strength_score' =>
                $strengthScore,

            'home_strength' =>
                $homeStrength,

            'away_strength' =>
                $awayStrength
        ];
    }


    /**
     * Build a complete team intelligence profile.
     *
     * Combines venue-aware team strength with the
     * team's complete fixture intelligence profile.
     */
    public function buildProfile(
        array $team,
        array $fixtureProfile
    ): array {

        $strengthScore =
            $this->getOverallStrength(
                $team
            );


        $homeStrength =
            $this->getHomeStrength(
                $team,
                $strengthScore
            );


        $awayStrength =
            $this->getAwayStrength(
                $team,
                $strengthScore
            );


        $fixtureRating =
            isset($fixtureProfile['fixture_rating'])
            &&
            is_numeric(
                $fixtureProfile['fixture_rating']
            )
                ? $this->calculateStrengthScore(
                    (float)
                        $fixtureProfile[
                            'fixture_rating'
                        ]
                )
                : null;


        $intelligenceScore =
            $this->calculateIntelligenceScore(
                $strengthScore,
                $fixtureRating
            );


        return [

            'team_id' =>
                $this->getTeamId(
                    $team
                ),

            'team_name' =>
                $team['name']
                ?? null,

            'strength_rating' =>
                $strengthScore,

            'home_strength' =>
                $homeStrength,

            'away_strength' =>
                $awayStrength,

            'fixture_rating' =>
                $fixtureRating,

            'fixture_label' =>
                $fixtureProfile[
                    'fixture_label'
                ]
                ?? null,

            'intelligence_score' =>
                $intelligenceScore,

            'intelligence_label' =>
                $this->getIntelligenceLabel(
                    $intelligenceScore
                ),

            'rolling_averages' =>
                $fixtureProfile[
                    'rolling_averages'
                ]
                ?? [],

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

            'trend' =>
                $fixtureProfile[
                    'trend'
                ]
                ?? 'Insufficient Data',

            'fixture_count' =>
                max(
                    0,
                    (int) (
                        $fixtureProfile[
                            'fixture_count'
                        ]
                        ?? 0
                    )
                ),

            'fixtures' =>
                isset(
                    $fixtureProfile['fixtures']
                )
                &&
                is_array(
                    $fixtureProfile['fixtures']
                )
                    ? $fixtureProfile['fixtures']
                    : []
        ];
    }
}