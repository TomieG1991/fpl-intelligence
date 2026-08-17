<?php

class PlayerAssessment
{
    /**
     * Build a deterministic FPL assessment from an
     * already-calculated player intelligence profile.
     *
     * This class does not recalculate player intelligence.
     * It only interprets existing ratings and profile data.
     */
    public function buildAssessment(
        array $profile
    ): array {

        $summary =
            $profile['summary']
            ?? [];


        $performance =
            $profile['performance']
            ?? [];


        $fixtures =
            $profile['fixtures']
            ?? [];


        $strengthRating =
            $this->getNullableRating(
                $summary[
                    'strength_rating'
                ]
                ?? null
            );


        $valueRating =
            $this->getNullableRating(
                $summary[
                    'value_rating'
                ]
                ?? null
            );


        $fixtureRating =
            $this->getNullableRating(
                $summary[
                    'fixture_rating'
                ]
                ?? null
            );


        $availabilityRating =
            $this->getNullableRating(
                $summary[
                    'availability_rating'
                ]
                ?? null
            );


        $intelligenceScore =
            $this->getNullableRating(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            );


        $sampleConfidence =
            isset(
                $performance[
                    'sample_confidence'
                ]
            )
            &&
            is_numeric(
                $performance[
                    'sample_confidence'
                ]
            )
                ? max(
                    0,
                    min(
                        1,
                        (float)
                            $performance[
                                'sample_confidence'
                            ]
                    )
                )
                : null;


        $fixtureTrend =
            $fixtures['trend']
            ?? 'Insufficient Data';


        $strengths =
            $this->buildStrengths(
                $strengthRating,
                $valueRating,
                $fixtureRating,
                $availabilityRating,
                $sampleConfidence,
                $fixtureTrend
            );


        $concerns =
            $this->buildConcerns(
                $strengthRating,
                $valueRating,
                $fixtureRating,
                $availabilityRating,
                $sampleConfidence,
                $fixtureTrend
            );


        $verdict =
            $this->getVerdict(
                $intelligenceScore,
                $availabilityRating,
                $sampleConfidence
            );


        return [

            'intelligence_score' =>
                $intelligenceScore,

            'verdict' =>
                $verdict['label'],

            'verdict_key' =>
                $verdict['key'],

            'summary' =>
                $this->buildSummary(
                    $verdict['label'],
                    $strengths,
                    $concerns
                ),

            'strengths' =>
                $strengths,

            'concerns' =>
                $concerns,

            'components' => [

                'strength' =>
                    $this->getRatingLabel(
                        $strengthRating
                    ),

                'value' =>
                    $this->getRatingLabel(
                        $valueRating
                    ),

                'fixtures' =>
                    $this->getRatingLabel(
                        $fixtureRating
                    ),

                'availability' =>
                    $this->getAvailabilityLabel(
                        $availabilityRating
                    ),

                'sample_confidence' =>
                    $this->getConfidenceLabel(
                        $sampleConfidence
                    ),

                'fixture_trend' =>
                    $fixtureTrend
            ]
        ];
    }


    /**
     * Identify positive profile characteristics.
     */
    private function buildStrengths(
        ?float $strengthRating,
        ?float $valueRating,
        ?float $fixtureRating,
        ?float $availabilityRating,
        ?float $sampleConfidence,
        string $fixtureTrend
    ): array {

        $strengths =
            [];


        if (
            $strengthRating !== null
            &&
            $strengthRating >= 70
        ) {

            $strengths[] =
                'High underlying player strength.';

        } elseif (
            $strengthRating !== null
            &&
            $strengthRating >= 60
        ) {

            $strengths[] =
                'Solid underlying player strength.';
        }


        if (
            $valueRating !== null
            &&
            $valueRating >= 75
        ) {

            $strengths[] =
                'Strong value at the current FPL price.';

        } elseif (
            $valueRating !== null
            &&
            $valueRating >= 60
        ) {

            $strengths[] =
                'Good value relative to player strength.';
        }


        if (
            $fixtureRating !== null
            &&
            $fixtureRating >= 70
        ) {

            $strengths[] =
                'Favourable upcoming fixture opportunity.';

        } elseif (
            $fixtureRating !== null
            &&
            $fixtureRating >= 60
        ) {

            $strengths[] =
                'Reasonably favourable short-term fixtures.';
        }


        if (
            $availabilityRating !== null
            &&
            $availabilityRating >= 90
        ) {

            $strengths[] =
                'Currently fully available.';
        }


        if (
            $sampleConfidence !== null
            &&
            $sampleConfidence >= 1
        ) {

            $strengths[] =
                'Backed by a full performance sample.';

        } elseif (
            $sampleConfidence !== null
            &&
            $sampleConfidence >= 0.75
        ) {

            $strengths[] =
                'Backed by a strong performance sample.';
        }


        if ($fixtureTrend === 'Improving') {

            $strengths[] =
                'Fixture opportunity improves across the upcoming run.';
        }


        return $strengths;
    }


    /**
     * Identify profile risks and watchpoints.
     */
    private function buildConcerns(
        ?float $strengthRating,
        ?float $valueRating,
        ?float $fixtureRating,
        ?float $availabilityRating,
        ?float $sampleConfidence,
        string $fixtureTrend
    ): array {

        $concerns =
            [];


        if (
            $strengthRating !== null
            &&
            $strengthRating < 50
        ) {

            $concerns[] =
                'Underlying player strength is currently weak.';
        }


        if (
            $valueRating !== null
            &&
            $valueRating < 40
        ) {

            $concerns[] =
                'Current price represents relatively poor value.';
        }


        if (
            $fixtureRating !== null
            &&
            $fixtureRating < 50
        ) {

            $concerns[] =
                'Upcoming fixture opportunity is difficult.';
        }


        if (
            $availabilityRating !== null
            &&
            $availabilityRating < 90
        ) {

            if ($availabilityRating < 30) {

                $concerns[] =
                    'Major availability concern.';

            } elseif ($availabilityRating < 60) {

                $concerns[] =
                    'Player availability is doubtful.';

            } else {

                $concerns[] =
                    'Some availability risk remains.';
            }
        }


        if (
            $sampleConfidence !== null
            &&
            $sampleConfidence < 0.25
        ) {

            $concerns[] =
                'Very small performance sample.';

        } elseif (
            $sampleConfidence !== null
            &&
            $sampleConfidence < 0.50
        ) {

            $concerns[] =
                'Limited performance sample reduces confidence.';
        }


        if ($fixtureTrend === 'Declining') {

            $concerns[] =
                'Fixture opportunity becomes less favourable over the longer run.';
        }


        return $concerns;
    }


    /**
     * Convert overall intelligence into a decision-oriented verdict.
     *
     * Availability and very small samples can reduce confidence
     * in otherwise strong intelligence scores.
     */
    private function getVerdict(
        ?float $intelligenceScore,
        ?float $availabilityRating,
        ?float $sampleConfidence
    ): array {

        if ($intelligenceScore === null) {

            return [
                'key' => 'insufficient_data',
                'label' => 'Insufficient Data'
            ];
        }


        if (
            $availabilityRating !== null
            &&
            $availabilityRating < 30
        ) {

            return [
                'key' => 'avoid',
                'label' => 'Avoid for Now'
            ];
        }


        if (
            $sampleConfidence !== null
            &&
            $sampleConfidence < 0.25
            &&
            $intelligenceScore >= 60
        ) {

            return [
                'key' => 'watchlist',
                'label' => 'High-Upside Watchlist'
            ];
        }


        if ($intelligenceScore >= 75) {

            return [
                'key' => 'excellent',
                'label' => 'Excellent FPL Option'
            ];
        }


        if ($intelligenceScore >= 65) {

            return [
                'key' => 'strong',
                'label' => 'Strong FPL Option'
            ];
        }


        if ($intelligenceScore >= 55) {

            return [
                'key' => 'consider',
                'label' => 'Consider'
            ];
        }


        if ($intelligenceScore >= 45) {

            return [
                'key' => 'watchlist',
                'label' => 'Watchlist'
            ];
        }


        return [
            'key' => 'avoid',
            'label' => 'Avoid for Now'
        ];
    }


    /**
     * Produce a short deterministic summary.
     */
    private function buildSummary(
        string $verdict,
        array $strengths,
        array $concerns
    ): string {

        $summary =
            $verdict . '.';


        if (!empty($strengths)) {

            $summary .=
                ' '
                . $strengths[0];
        }


        if (!empty($concerns)) {

            $summary .=
                ' Watchpoint: '
                . $concerns[0];
        }


        return $summary;
    }


    /**
     * Convert general 0-100 ratings into labels.
     */
    public function getRatingLabel(
        ?float $rating
    ): string {

        if ($rating === null) {
            return 'Unknown';
        }


        if ($rating >= 75) {
            return 'Excellent';
        }


        if ($rating >= 65) {
            return 'Strong';
        }


        if ($rating >= 55) {
            return 'Average';
        }


        if ($rating >= 45) {
            return 'Below Average';
        }


        return 'Weak';
    }


    /**
     * Convert availability rating into assessment wording.
     */
    public function getAvailabilityLabel(
        ?float $rating
    ): string {

        if ($rating === null) {
            return 'Unknown';
        }


        if ($rating >= 90) {
            return 'Available';
        }


        if ($rating >= 60) {
            return 'Likely Available';
        }


        if ($rating >= 30) {
            return 'Doubtful';
        }


        return 'Unavailable';
    }


    /**
     * Convert sample confidence into a readable label.
     */
    public function getConfidenceLabel(
        ?float $confidence
    ): string {

        if ($confidence === null) {
            return 'Unknown';
        }


        if ($confidence >= 1) {
            return 'Full';
        }


        if ($confidence >= 0.75) {
            return 'High';
        }


        if ($confidence >= 0.50) {
            return 'Moderate';
        }


        if ($confidence >= 0.25) {
            return 'Low';
        }


        return 'Very Low';
    }


    /**
     * Safely constrain a rating to the standard 0-100 scale.
     */
    private function getNullableRating(
        mixed $rating
    ): ?float {

        if (
            $rating === null
            ||
            !is_numeric(
                $rating
            )
        ) {

            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    (float) $rating
                )
            ),
            2
        );
    }
}