<?php

class TeamIntelligence
{
    /**
     * Calculate the overall team strength score.
     *
     * The input is expected to be a raw team strength rating.
     *
     * The score is normalised to a 0-100 scale.
     */
    public function calculateStrengthScore(
        float $strength
    ): float {

        $strength = max(
            0,
            min(100, $strength)
        );

        return round(
            $strength,
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


        $strength = max(
            0,
            min(100, $strength)
        );


        $fixtureRating = max(
            0,
            min(100, $fixtureRating)
        );


        return round(
            ($strength * 0.60)
            +
            ($fixtureRating * 0.40),
            2
        );
    }
    
    /**
     * Convert an intelligence score into a human-readable label.
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
     * Calculate the home advantage score.
     *
     * Home strength receives a small advantage because
     * teams generally perform better at home.
     */
    public function calculateHomeStrength(
        float $strength
    ): float {

        $strength = $this->calculateStrengthScore(
            $strength
        );

        return round(
            min(100, $strength * 1.05),
            2
        );
    }


    /**
     * Calculate the away strength score.
     *
     * Away strength receives a small reduction to reflect
     * the disadvantage of playing away from home.
     */
    public function calculateAwayStrength(
        float $strength
    ): float {

        $strength = $this->calculateStrengthScore(
            $strength
        );

        return round(
            max(0, $strength * 0.95),
            2
        );
    }


    /**
     * Calculate the overall team intelligence profile.
     */
    public function buildTeamProfile(
        array $team
    ): array {

        $strength =
            isset($team['strength'])
                ? (float) $team['strength']
                : null;


        if ($strength === null) {

            return [

                'team_id' =>
                    (int) (
                        $team['team_id']
                        ?? 0
                    ),

                'team_name' =>
                    $team['name']
                    ?? null,

                'strength_score' =>
                    null,

                'home_strength' =>
                    null,

                'away_strength' =>
                    null
            ];
        }


        $strengthScore =
            $this->calculateStrengthScore(
                $strength
            );


        return [

            'team_id' =>
                (int) (
                    $team['team_id']
                    ?? 0
                ),

            'team_name' =>
                $team['name']
                ?? null,

            'strength_score' =>
                $strengthScore,

            'home_strength' =>
                $this->calculateHomeStrength(
                    $strength
                ),

            'away_strength' =>
                $this->calculateAwayStrength(
                    $strength
                )
        ];
    }
    
    /**
     * Build a complete team intelligence profile.
     *
     * Combines team strength with the team's complete
     * fixture intelligence profile.
     */
    public function buildProfile(
        array $team,
        array $fixtureProfile
    ): array {

        $strength =
            isset($team['strength'])
                ? (float) $team['strength']
                : null;


        $strengthScore =
            $strength !== null
                ? $this->calculateStrengthScore(
                    $strength
                )
                : null;


        $fixtureRating =
            isset($fixtureProfile['fixture_rating'])
                ? (float) $fixtureProfile['fixture_rating']
                : null;


        $intelligenceScore =
            $this->calculateIntelligenceScore(
                $strengthScore,
                $fixtureRating
            );


        return [

            'team_id' =>
                (int) (
                    $team['team_id']
                    ?? 0
                ),

            'team_name' =>
                $team['name']
                ?? null,

            'strength_rating' =>
                $strengthScore,

            'fixture_rating' =>
                $fixtureRating,

            'fixture_label' =>
                $fixtureProfile['fixture_label']
                ?? null,

            'intelligence_score' =>
                $intelligenceScore,

            'intelligence_label' =>
                $this->getIntelligenceLabel(
                    $intelligenceScore
                ),

            'rolling_averages' =>
                $fixtureProfile['rolling_averages']
                ?? [],

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
                (int) (
                    $fixtureProfile['fixture_count']
                    ?? 0
                ),

            'fixtures' =>
                $fixtureProfile['fixtures']
                ?? []
        ];
    }
}