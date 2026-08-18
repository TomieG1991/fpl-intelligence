<?php

class ReplacementRecommendation
{
    /**
     * Build recommendation categories from an already-ranked
     * replacement candidate list.
     */
    public function buildRecommendations(
        array $replacements
    ): array {

        if (empty($replacements)) {

            return [
                'best_overall' => null,
                'best_value' => null,
                'best_fixtures' => null,
                'safest_pick' => null,
                'high_upside' => null
            ];
        }


        return [

            'best_overall' =>
                $this->findBestOverall(
                    $replacements
                ),

            'best_value' =>
                $this->findBestValue(
                    $replacements
                ),

            'best_fixtures' =>
                $this->findHighestMetric(
                    $replacements,
                    'fixture_rating'
                ),

            'safest_pick' =>
                $this->findSafestPick(
                    $replacements
                ),

            'high_upside' =>
                $this->findHighUpsidePick(
                    $replacements
                )
        ];
    }


    /**
     * Best Overall follows the existing replacement ordering:
     * highest Intelligence Score.
     */
    private function findBestOverall(
        array $replacements
    ): ?array {

        $best =
            null;


        foreach ($replacements as $replacement) {

            $score =
                $this->normaliseRating(
                    $replacement[
                        'intelligence_score'
                    ]
                    ?? null
                );


            if ($score === null) {
                continue;
            }


            if (
                $best === null
                ||
                $score
                >
                $best['_recommendation_score']
            ) {

                $best =
                    $this->prepareRecommendation(
                        $replacement,
                        $score
                    );
            }
        }


        return $this->removeInternalScore(
            $best
        );
    }


    /**
     * Generic highest-metric selector.
     *
     * If two players have the same metric score,
     * Intelligence Score acts as the tiebreaker.
     */
    private function findHighestMetric(
        array $replacements,
        string $metric
    ): ?array {

        $best =
            null;


        $bestMetricScore =
            null;


        $bestIntelligence =
            null;


        foreach ($replacements as $replacement) {

            $score =
                $this->normaliseRating(
                    $replacement[
                        $metric
                    ]
                    ?? null
                );


            if ($score === null) {
                continue;
            }


            $intelligence =
                $this->normaliseRating(
                    $replacement[
                        'intelligence_score'
                    ]
                    ?? null
                );


            /*
             * First valid candidate.
             */
            if ($best === null) {

                $best =
                    $replacement;

                $bestMetricScore =
                    $score;

                $bestIntelligence =
                    $intelligence;

                continue;
            }


            /*
             * Higher category metric always wins.
             */
            if ($score > $bestMetricScore) {

                $best =
                    $replacement;

                $bestMetricScore =
                    $score;

                $bestIntelligence =
                    $intelligence;

                continue;
            }


            /*
             * If category metric is tied,
             * higher Intelligence wins.
             */
            if ($score === $bestMetricScore) {

                if (
                    $intelligence !== null
                    &&
                    (
                        $bestIntelligence === null
                        ||
                        $intelligence
                        >
                        $bestIntelligence
                    )
                ) {

                    $best =
                        $replacement;

                    $bestMetricScore =
                        $score;

                    $bestIntelligence =
                        $intelligence;
                }
            }
        }


        return $best;
    }


    /**
     * Safest Pick favours:
     * - high sample confidence
     * - high availability
     * - strong Intelligence
     *
     * Weighting:
     * 40% sample confidence
     * 30% availability
     * 30% Intelligence
     */
    private function findSafestPick(
        array $replacements
    ): ?array {

        $best =
            null;


        foreach ($replacements as $replacement) {

            $confidence =
                $this->normaliseConfidence(
                    $replacement[
                        'sample_confidence'
                    ]
                    ?? null
                );


            $availability =
                $this->normaliseRating(
                    $replacement[
                        'availability_rating'
                    ]
                    ?? null
                );


            $intelligence =
                $this->normaliseRating(
                    $replacement[
                        'intelligence_score'
                    ]
                    ?? null
                );


            if (
                $confidence === null
                ||
                $availability === null
                ||
                $intelligence === null
            ) {
                continue;
            }


            $score =
                round(
                    (
                        ($confidence * 100)
                        * 0.40
                    )
                    +
                    (
                        $availability
                        * 0.30
                    )
                    +
                    (
                        $intelligence
                        * 0.30
                    ),
                    2
                );


            if (
                $best === null
                ||
                $score
                >
                $best['_recommendation_score']
            ) {

                $best =
                    $this->prepareRecommendation(
                        $replacement,
                        $score
                    );
            }
        }


        return $this->removeInternalScore(
            $best
        );
    }


    /**
     * High-Upside Pick:
     *
     * We want promising but not completely unproven candidates.
     *
     * Eligible sample confidence:
     * 10% to below 75%
     *
     * Score:
     * 60% Intelligence
     * 25% Value
     * 15% Fixtures
     */
    private function findHighUpsidePick(
        array $replacements
    ): ?array {

        $best =
            null;


        foreach ($replacements as $replacement) {

            $confidence =
                $this->normaliseConfidence(
                    $replacement[
                        'sample_confidence'
                    ]
                    ?? null
                );


            if (
                $confidence === null
                ||
                $confidence < 0.10
                ||
                $confidence >= 0.75
            ) {
                continue;
            }


            $intelligence =
                $this->normaliseRating(
                    $replacement[
                        'intelligence_score'
                    ]
                    ?? null
                );


            $value =
                $this->normaliseRating(
                    $replacement[
                        'value_rating'
                    ]
                    ?? null
                );


            $fixtures =
                $this->normaliseRating(
                    $replacement[
                        'fixture_rating'
                    ]
                    ?? null
                );


            if (
                $intelligence === null
                ||
                $value === null
                ||
                $fixtures === null
            ) {
                continue;
            }


            $score =
                round(
                    (
                        $intelligence
                        * 0.60
                    )
                    +
                    (
                        $value
                        * 0.25
                    )
                    +
                    (
                        $fixtures
                        * 0.15
                    ),
                    2
                );


            if (
                $best === null
                ||
                $score
                >
                $best['_recommendation_score']
            ) {

                $best =
                    $this->prepareRecommendation(
                        $replacement,
                        $score
                    );
            }
        }


        return $this->removeInternalScore(
            $best
        );
    }


    /**
     * Attach recommendation score internally.
     */
    private function prepareRecommendation(
        array $replacement,
        float $score
    ): array {

        $replacement[
            '_recommendation_score'
        ] =
            round(
                $score,
                2
            );


        return $replacement;
    }


    /**
     * Strip internal recommendation score before returning.
     */
    private function removeInternalScore(
        ?array $replacement
    ): ?array {

        if ($replacement === null) {
            return null;
        }


        unset(
            $replacement[
                '_recommendation_score'
            ]
        );


        return $replacement;
    }


    /**
     * Safely normalise a 0-100 rating.
     */
    private function normaliseRating(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }


    /**
     * Sample confidence uses the internal 0-1 scale.
     */
    private function normaliseConfidence(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    1,
                    (float) $value
                )
            ),
            4
        );
    }
    
    /**
 * Best Value requires at least a meaningful performance sample.
 *
 * Very-low-sample players remain visible in the replacement
 * rankings but are not presented as the authoritative
 * Best Value recommendation.
 *
 * Minimum confidence:
 * 25%
 *
 * Highest Value wins.
 * Intelligence acts as the tiebreaker.
 */
private function findBestValue(
    array $replacements
): ?array {

    $eligible =
        [];


    foreach ($replacements as $replacement) {

        $confidence =
            $this->normaliseConfidence(
                $replacement[
                    'sample_confidence'
                ]
                ?? null
            );


        if (
            $confidence === null
            ||
            $confidence < 0.25
        ) {
            continue;
        }


        $eligible[] =
            $replacement;
    }


    return $this->findHighestMetric(
        $eligible,
        'value_rating'
    );
}
}