<?php

class ChipDecision
{
    private string $chip;

    private string $recommendation;

    private float $confidence;

    private string $explanation;


    public function __construct(
        string $chip,
        string $recommendation,
        float $confidence,
        string $explanation
    ) {

        if (
            trim(
                $chip
            )
            ===
            ''
        ) {

            throw new InvalidArgumentException(
                'Chip name cannot be empty.'
            );
        }


        if (
            !in_array(
                $recommendation,
                [
                    'Use',
                    'Consider',
                    'Hold'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Unsupported chip recommendation.'
            );
        }


        if (
            $confidence < 0.0
            ||
            $confidence > 1.0
        ) {

            throw new InvalidArgumentException(
                'Confidence must be between 0.0 and 1.0.'
            );
        }


        if (
            trim(
                $explanation
            )
            ===
            ''
        ) {

            throw new InvalidArgumentException(
                'Explanation cannot be empty.'
            );
        }


        $this->chip =
            $chip;

        $this->recommendation =
            $recommendation;

        $this->confidence =
            $confidence;

        $this->explanation =
            $explanation;
    }


    public function getChip(): string
    {
        return
            $this->chip;
    }


    public function getRecommendation(): string
    {
        return
            $this->recommendation;
    }


    public function getConfidence(): float
    {
        return
            $this->confidence;
    }


    public function getExplanation(): string
    {
        return
            $this->explanation;
    }


    public function toArray(): array
    {
        return [
            'chip' =>
                $this->chip,

            'recommendation' =>
                $this->recommendation,

            'confidence' =>
                $this->confidence,

            'explanation' =>
                $this->explanation
        ];
    }
}