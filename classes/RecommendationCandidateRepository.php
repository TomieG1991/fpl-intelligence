<?php

/**
 * RecommendationCandidateRepository
 *
 * Persists the latest pre-deadline recommendation candidate
 * for an FPL entry/gameweek.
 *
 * Recommendation candidates are mutable staging evidence.
 *
 * For one entry/gameweek:
 *
 * - the first candidate is inserted
 * - a strictly newer candidate replaces the existing candidate
 * - an older candidate cannot replace newer evidence
 * - a candidate generated at the same timestamp does not replace
 *   the existing candidate
 *
 * This is deliberately different from RecommendationSnapshot,
 * which is immutable once captured.
 *
 * This repository does not calculate or interpret any
 * recommendation intelligence.
 */
class RecommendationCandidateRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /**
     * Return the current recommendation candidate for one
     * entry/gameweek.
     */
    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): ?array {

        $statement =
            $this->db->prepare(
                "
                SELECT
                    id,
                    gameweek_id,
                    entry_id,
                    generated_at,
                    deadline_time,
                    player_projections,
                    starting_xi,
                    captain_recommendation,
                    transfer_recommendations,
                    gameweek_decision,
                    chip_recommendations,
                    created_at,
                    updated_at
                FROM recommendation_candidates
                WHERE entry_id = :entry_id
                  AND gameweek_id = :gameweek_id
                LIMIT 1
                "
            );


        $statement->execute(
            [
                'entry_id' =>
                    $entryId,

                'gameweek_id' =>
                    $gameweekId
            ]
        );


        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            $row === false
        ) {

            return null;
        }


        return
            $this->hydrateRow(
                $row
            );
    }


    /**
     * Return all current recommendation candidates for an entry,
     * ordered by local gameweek ID.
     */
    public function getByEntryId(
        int $entryId
    ): array {

        $statement =
            $this->db->prepare(
                "
                SELECT
                    id,
                    gameweek_id,
                    entry_id,
                    generated_at,
                    deadline_time,
                    player_projections,
                    starting_xi,
                    captain_recommendation,
                    transfer_recommendations,
                    gameweek_decision,
                    chip_recommendations,
                    created_at,
                    updated_at
                FROM recommendation_candidates
                WHERE entry_id = :entry_id
                ORDER BY gameweek_id ASC
                "
            );


        $statement->execute(
            [
                'entry_id' =>
                    $entryId
            ]
        );


        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        $candidates =
            [];


        foreach (
            $rows
            as $row
        ) {

            $candidates[] =
                $this->hydrateRow(
                    $row
                );
        }


        return $candidates;
    }
    
    
    /*
     * ============================================================
     * GET CANDIDATES READY FOR PROMOTION
     * ============================================================
     *
     * Returns recommendation candidates whose preserved deadline
     * has been reached.
     *
     * Promotion readiness is based on the deadline stored with the
     * candidate itself. It is not reconstructed from current
     * gameweek state.
     *
     * This method only discovers eligible candidates. It does not
     * promote, update or delete them.
     */

    public function getReadyForPromotion(
        string $timestamp
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE TIMESTAMP
         * --------------------------------------------------------
         */

        try {

            $readyAt =
                new DateTimeImmutable(
                    $timestamp
                );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'Promotion readiness timestamp must be a valid date/time.',
                0,
                $exception
            );
        }


        $normalisedTimestamp =
            $readyAt
                ->format(
                    'Y-m-d H:i:s'
                );


        /*
         * --------------------------------------------------------
         * FIND READY CANDIDATES
         * --------------------------------------------------------
         *
         * A candidate becomes eligible exactly when:
         *
         * deadline_time <= supplied timestamp
         *
         * Ordering is deterministic so a future promotion runner
         * processes older deadlines first.
         */

        $statement =
            $this->db
                ->prepare(
                    '
                        SELECT
                            *
                        FROM
                            recommendation_candidates
                        WHERE
                            deadline_time <= :ready_at
                        ORDER BY
                            deadline_time ASC,
                            gameweek_id ASC,
                            entry_id ASC
                    '
                );


        $statement
            ->bindValue(
                ':ready_at',
                $normalisedTimestamp,
                PDO::PARAM_STR
            );


        $statement
            ->execute();


        $rows =
            $statement
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


        /*
         * --------------------------------------------------------
         * DECODE HISTORICAL EVIDENCE
         * --------------------------------------------------------
         *
         * Use the repository's existing row decoder so promotion
         * receives the same PHP representation as normal candidate
         * retrieval.
         */

        return
            array_map(
                function (
                    array $row
                ): array {

                    return
                        $this->hydrateRow(
                            $row
                        );
                },
                $rows
            );
    }


    /**
     * Store the candidate only when it is the latest known
     * recommendation for this entry/gameweek.
     *
     * Returns true when:
     *
     * - no candidate exists and the candidate is inserted
     * - an existing candidate is replaced by a strictly newer one
     *
     * Returns false when:
     *
     * - the existing candidate has the same generated timestamp
     * - the existing candidate is newer
     */
    public function saveLatest(
        int $gameweekId,
        RecommendationCandidate $candidate
    ): bool {

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Gameweek ID must be positive.'
            );
        }


        if (
            $candidate->getGameweek()
            !==
            $gameweekId
        ) {

            throw new InvalidArgumentException(
                'Candidate gameweek does not match repository gameweek ID.'
            );
        }


        $existing =
            $this->getByEntryAndGameweek(
                $candidate->getEntryId(),
                $gameweekId
            );


        if (
            $existing === null
        ) {

            return
                $this->insert(
                    $gameweekId,
                    $candidate
                );
        }


        $existingGeneratedTimestamp =
            strtotime(
                (string) $existing[
                    'generated_at'
                ]
            );


        $candidateGeneratedTimestamp =
            strtotime(
                $candidate->getGeneratedAt()
            );


        if (
            $existingGeneratedTimestamp === false
            ||
            $candidateGeneratedTimestamp === false
        ) {

            throw new RuntimeException(
                'Recommendation candidate generated timestamp could not be compared.'
            );
        }


        /*
         * Only a strictly newer recommendation may replace the
         * existing candidate.
         *
         * Equal timestamps are deliberately treated as unchanged.
         */
        if (
            $candidateGeneratedTimestamp
            <=
            $existingGeneratedTimestamp
        ) {

            return false;
        }


        return
            $this->replace(
                $gameweekId,
                $candidate
            );
    }


    /**
     * Insert the first candidate for an entry/gameweek.
     */
    private function insert(
        int $gameweekId,
        RecommendationCandidate $candidate
    ): bool {

        $statement =
            $this->db->prepare(
                "
                INSERT INTO recommendation_candidates (
                    gameweek_id,
                    entry_id,
                    generated_at,
                    deadline_time,
                    player_projections,
                    starting_xi,
                    captain_recommendation,
                    transfer_recommendations,
                    gameweek_decision,
                    chip_recommendations
                ) VALUES (
                    :gameweek_id,
                    :entry_id,
                    :generated_at,
                    :deadline_time,
                    :player_projections,
                    :starting_xi,
                    :captain_recommendation,
                    :transfer_recommendations,
                    :gameweek_decision,
                    :chip_recommendations
                )
                "
            );


        $statement->execute(
            $this->buildPersistenceParameters(
                $gameweekId,
                $candidate
            )
        );


        return
            $statement->rowCount()
            ===
            1;
    }


    /**
     * Replace an existing candidate with strictly newer
     * recommendation evidence.
     */
    private function replace(
        int $gameweekId,
        RecommendationCandidate $candidate
    ): bool {

        $parameters =
            $this->buildPersistenceParameters(
                $gameweekId,
                $candidate
            );


        $parameters[
            'existing_entry_id'
        ] =
            $candidate->getEntryId();


        $parameters[
            'existing_gameweek_id'
        ] =
            $gameweekId;


        $statement =
            $this->db->prepare(
                "
                UPDATE recommendation_candidates
                SET
                    generated_at = :generated_at,
                    deadline_time = :deadline_time,
                    player_projections = :player_projections,
                    starting_xi = :starting_xi,
                    captain_recommendation = :captain_recommendation,
                    transfer_recommendations = :transfer_recommendations,
                    gameweek_decision = :gameweek_decision,
                    chip_recommendations = :chip_recommendations
                WHERE entry_id = :existing_entry_id
                  AND gameweek_id = :existing_gameweek_id
                "
            );


        /*
         * The UPDATE query does not use these INSERT-only
         * placeholders.
         */
        unset(
            $parameters[
                'gameweek_id'
            ],
            $parameters[
                'entry_id'
            ]
        );


        $statement->execute(
            $parameters
        );


        return
            $statement->rowCount()
            ===
            1;
    }


    /**
     * Build the persistence values for a candidate.
     *
     * Recommendation evidence is JSON encoded into LONGTEXT.
     *
     * JSON_PRESERVE_ZERO_FRACTION is important because historical
     * evidence must preserve values such as 82.0 rather than
     * silently converting them to 82.
     */
    private function buildPersistenceParameters(
        int $gameweekId,
        RecommendationCandidate $candidate
    ): array {

        return [

            'gameweek_id' =>
                $gameweekId,

            'entry_id' =>
                $candidate->getEntryId(),

            'generated_at' =>
                $candidate->getGeneratedAt(),

            'deadline_time' =>
                $candidate->getDeadlineTime(),

            'player_projections' =>
                $this->encodeEvidence(
                    $candidate
                        ->getPlayerProjections()
                ),

            'starting_xi' =>
                $this->encodeEvidence(
                    $candidate
                        ->getStartingXI()
                ),

            'captain_recommendation' =>
                $this->encodeEvidence(
                    $candidate
                        ->getCaptainRecommendation()
                ),

            'transfer_recommendations' =>
                $this->encodeEvidence(
                    $candidate
                        ->getTransferRecommendations()
                ),

            'gameweek_decision' =>
                $this->encodeEvidence(
                    $candidate
                        ->getGameweekDecision()
                ),

            'chip_recommendations' =>
                $this->encodeEvidence(
                    $candidate
                        ->getChipRecommendations()
                )
        ];
    }


    /**
     * Encode recommendation evidence without allowing the
     * database to normalise the JSON representation.
     */
    private function encodeEvidence(
        array $evidence
    ): string {

        return
            json_encode(
                $evidence,
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
                |
                JSON_PRESERVE_ZERO_FRACTION
                |
                JSON_THROW_ON_ERROR
            );
    }


    /**
     * Decode one database row into the repository's public
     * persistence contract.
     */
    private function hydrateRow(
        array $row
    ): array {

        return [

            'id' =>
                (int) $row[
                    'id'
                ],

            'gameweek_id' =>
                (int) $row[
                    'gameweek_id'
                ],

            'entry_id' =>
                (int) $row[
                    'entry_id'
                ],

            'generated_at' =>
                (string) $row[
                    'generated_at'
                ],

            'deadline_time' =>
                (string) $row[
                    'deadline_time'
                ],

            'player_projections' =>
                $this->decodeEvidence(
                    (string) $row[
                        'player_projections'
                    ]
                ),

            'starting_xi' =>
                $this->decodeEvidence(
                    (string) $row[
                        'starting_xi'
                    ]
                ),

            'captain_recommendation' =>
                $this->decodeEvidence(
                    (string) $row[
                        'captain_recommendation'
                    ]
                ),

            'transfer_recommendations' =>
                $this->decodeEvidence(
                    (string) $row[
                        'transfer_recommendations'
                    ]
                ),

            'gameweek_decision' =>
                $this->decodeEvidence(
                    (string) $row[
                        'gameweek_decision'
                    ]
                ),

            'chip_recommendations' =>
                $this->decodeEvidence(
                    (string) $row[
                        'chip_recommendations'
                    ]
                ),

            'created_at' =>
                (string) $row[
                    'created_at'
                ],

            'updated_at' =>
                (string) $row[
                    'updated_at'
                ]
        ];
    }


    /**
     * Decode one JSON-encoded evidence section.
     */
    private function decodeEvidence(
        string $evidence
    ): array {

        $decoded =
            json_decode(
                $evidence,
                true,
                512,
                JSON_THROW_ON_ERROR
            );


        if (
            !is_array(
                $decoded
            )
        ) {

            throw new RuntimeException(
                'Stored recommendation candidate evidence must decode to an array.'
            );
        }


        return $decoded;
    }
}