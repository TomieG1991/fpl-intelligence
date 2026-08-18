<?php

class PlayerReplacement
{
    /**
     * Find replacement candidates for a player.
     *
     * Rules:
     * - Same position only
     * - Candidate must be within max price
     * - Candidate must have a usable intelligence score
     * - Candidate must be sufficiently available
     * - Candidate cannot be the current player
     *
     * Candidates are ranked by:
     * 1. Intelligence Score
     * 2. Strength Rating
     * 3. Value Rating
     */
    public function findReplacements(
        array $currentPlayer,
        array $candidates,
        float $maxPrice,
        int $limit = 10
    ): array {

        if ($maxPrice < 0) {
            return [];
        }


        if ($limit <= 0) {
            return [];
        }


        $currentPlayerId =
            (int) (
                $currentPlayer['player_id']
                ?? 0
            );


        $currentPosition =
            strtoupper(
                trim(
                    (string) (
                        $currentPlayer['position']
                        ?? ''
                    )
                )
            );


        $currentIntelligence =
            $this->normaliseRating(
                $currentPlayer[
                    'intelligence_score'
                ]
                ?? null
            );


        if ($currentPosition === '') {
            return [];
        }


        $replacements =
            [];


        foreach ($candidates as $candidate) {

            $candidateId =
                (int) (
                    $candidate['player_id']
                    ?? 0
                );


            if (
                $candidateId <= 0
                ||
                $candidateId === $currentPlayerId
            ) {
                continue;
            }


            $candidatePosition =
                strtoupper(
                    trim(
                        (string) (
                            $candidate['position']
                            ?? ''
                        )
                    )
                );


            if (
                $candidatePosition
                !==
                $currentPosition
            ) {
                continue;
            }


            $price =
                $this->normaliseNumber(
                    $candidate['price']
                    ?? null
                );


            if (
                $price === null
                ||
                $price > $maxPrice
            ) {
                continue;
            }


            $intelligenceScore =
                $this->normaliseRating(
                    $candidate[
                        'intelligence_score'
                    ]
                    ?? null
                );


            if ($intelligenceScore === null) {
                continue;
            }


            $availabilityRating =
                $this->normaliseRating(
                    $candidate[
                        'availability_rating'
                    ]
                    ?? null
                );


            /*
             * For the first version of replacement intelligence,
             * candidates below 60 availability are excluded.
             *
             * 60+ allows:
             * - Available
             * - Likely Available
             *
             * while excluding genuinely doubtful/unavailable
             * options from normal recommendations.
             */
            if (
                $availabilityRating !== null
                &&
                $availabilityRating < 60
            ) {
                continue;
            }


            $strengthRating =
                $this->normaliseRating(
                    $candidate[
                        'strength_rating'
                    ]
                    ?? null
                );


            $valueRating =
                $this->normaliseRating(
                    $candidate[
                        'value_rating'
                    ]
                    ?? null
                );


            $fixtureRating =
                $this->normaliseRating(
                    $candidate[
                        'fixture_rating'
                    ]
                    ?? null
                );


            $sampleConfidence =
                $this->normaliseConfidence(
                    $candidate[
                        'sample_confidence'
                    ]
                    ?? null
                );


            $intelligenceGain =
                $currentIntelligence !== null
                    ? round(
                        $intelligenceScore
                        -
                        $currentIntelligence,
                        2
                    )
                    : null;


            $currentPrice =
                $this->normaliseNumber(
                    $currentPlayer[
                        'price'
                    ]
                    ?? null
                );


            $priceDifference =
                $currentPrice !== null
                    ? round(
                        $price
                        -
                        $currentPrice,
                        2
                    )
                    : null;


            $replacements[] = [

                'player_id' =>
                    $candidateId,

                'name' =>
                    $candidate['name']
                    ?? null,

                'team_name' =>
                    $candidate['team_name']
                    ?? null,

                'team_short_name' =>
                    $candidate['team_short_name']
                    ?? null,

                'position' =>
                    $candidatePosition,

                'price' =>
                    $price,

                'intelligence_score' =>
                    $intelligenceScore,

                'intelligence_gain' =>
                    $intelligenceGain,

                'price_difference' =>
                    $priceDifference,

                'strength_rating' =>
                    $strengthRating,

                'value_rating' =>
                    $valueRating,

                'fixture_rating' =>
                    $fixtureRating,

                'availability_rating' =>
                    $availabilityRating,

                'sample_confidence' =>
                    $sampleConfidence,

                'verdict' =>
                    $candidate['verdict']
                    ??
                    $candidate[
                        'assessment_verdict'
                    ]
                    ??
                    null
            ];
        }


        usort(
            $replacements,
            function (
                array $a,
                array $b
            ): int {

                /*
                 * ------------------------------------------------
                 * 1. INTELLIGENCE SCORE
                 * ------------------------------------------------
                 */

                $comparison =
                    $this->compareDescending(
                        $a[
                            'intelligence_score'
                        ],
                        $b[
                            'intelligence_score'
                        ]
                    );


                if ($comparison !== 0) {
                    return $comparison;
                }


                /*
                 * ------------------------------------------------
                 * 2. STRENGTH
                 * ------------------------------------------------
                 */

                $comparison =
                    $this->compareDescending(
                        $a[
                            'strength_rating'
                        ],
                        $b[
                            'strength_rating'
                        ]
                    );


                if ($comparison !== 0) {
                    return $comparison;
                }


                /*
                 * ------------------------------------------------
                 * 3. VALUE
                 * ------------------------------------------------
                 */

                $comparison =
                    $this->compareDescending(
                        $a[
                            'value_rating'
                        ],
                        $b[
                            'value_rating'
                        ]
                    );


                if ($comparison !== 0) {
                    return $comparison;
                }


                /*
                 * Stable final fallback.
                 */
                return strcasecmp(
                    (string) (
                        $a['name']
                        ?? ''
                    ),
                    (string) (
                        $b['name']
                        ?? ''
                    )
                );
            }
        );


        return array_slice(
            $replacements,
            0,
            $limit
        );
    }


    /**
     * Categorise the replacement's intelligence movement.
     */
    public function getReplacementType(
        mixed $intelligenceGain
    ): string {

        if (
            $intelligenceGain === null
            ||
            !is_numeric(
                $intelligenceGain
            )
        ) {
            return 'Unknown';
        }


        $gain =
            (float) $intelligenceGain;


        if ($gain >= 2) {
            return 'Upgrade';
        }


        if ($gain > -2) {
            return 'Sidegrade';
        }


        return 'Downgrade';
    }


    /**
     * Build a compact replacement summary.
     */
    public function buildReplacementSummary(
        array $replacement
    ): string {

        $name =
            (string) (
                $replacement['name']
                ?? 'Player'
            );


        $type =
            $this->getReplacementType(
                $replacement[
                    'intelligence_gain'
                ]
                ?? null
            );


        $summary =
            $name
            . ' is a '
            . strtolower(
                $type
            );


        $gain =
            $replacement[
                'intelligence_gain'
            ]
            ?? null;


        if (is_numeric($gain)) {

            $summary .=
                ' ('
                . (
                    (float) $gain >= 0
                        ? '+'
                        : ''
                )
                . number_format(
                    (float) $gain,
                    1
                )
                . ' Intelligence)';
        }


        $priceDifference =
            $replacement[
                'price_difference'
            ]
            ?? null;


        if (is_numeric($priceDifference)) {

            if ((float) $priceDifference < 0) {

                $summary .=
                    ' while saving £'
                    . number_format(
                        abs(
                            (float)
                            $priceDifference
                        ),
                        1
                    )
                    . 'm';

            } elseif (
                (float) $priceDifference > 0
            ) {

                $summary .=
                    ' for an additional £'
                    . number_format(
                        (float)
                        $priceDifference,
                        1
                    )
                    . 'm';

            } else {

                $summary .=
                    ' at the same price';
            }
        }


        return $summary . '.';
    }


    /**
     * Descending comparison that safely handles nulls.
     */
    private function compareDescending(
        ?float $a,
        ?float $b
    ): int {

        if (
            $a === null
            &&
            $b === null
        ) {
            return 0;
        }


        if ($a === null) {
            return 1;
        }


        if ($b === null) {
            return -1;
        }


        return $b <=> $a;
    }


    /**
     * Safely normalise standard ratings.
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
     * Safely normalise numeric values such as price.
     */
    private function normaliseNumber(
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
            (float) $value,
            2
        );
    }


    /**
     * Sample confidence is stored internally as 0-1.
     */
    private function normaliseConfidence(
        mixed $confidence
    ): ?float {

        if (
            $confidence === null
            ||
            !is_numeric(
                $confidence
            )
        ) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    1,
                    (float) $confidence
                )
            ),
            4
        );
    }
}