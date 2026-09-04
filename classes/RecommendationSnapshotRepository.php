<?php

class RecommendationSnapshotRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /*
     * ============================================================
     * GET BY ENTRY AND GAMEWEEK
     * ============================================================
     */

    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): ?array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM recommendation_snapshots
                WHERE
                    entry_id = :entry_id
                    AND gameweek_id = :gameweek_id
                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':entry_id',
            $entryId,
            PDO::PARAM_INT
        );


        $stmt->bindValue(
            ':gameweek_id',
            $gameweekId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $snapshot =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            $snapshot === false
        ) {

            return
                null;
        }


        return
            $this->decodeSnapshot(
                $snapshot
            );
    }


    /*
     * ============================================================
     * GET ENTRY HISTORY
     * ============================================================
     */

    public function getByEntryId(
        int $entryId
    ): array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM recommendation_snapshots
                WHERE entry_id = :entry_id
                ORDER BY gameweek_id ASC
                "
            );


        $stmt->bindValue(
            ':entry_id',
            $entryId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $snapshots =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        return
            array_map(
                fn (
                    array $snapshot
                ): array =>
                    $this->decodeSnapshot(
                        $snapshot
                    ),
                $snapshots
            );
    }


    /*
     * ============================================================
     * INSERT IMMUTABLE SNAPSHOT
     * ============================================================
     *
     * Insert one recommendation snapshot for one entry/gameweek.
     *
     * If a snapshot already exists for the same entry/gameweek,
     * leave the original historical record unchanged.
     *
     * Returns:
     *
     * true  = snapshot inserted
     * false = snapshot already existed
     */

    public function insertIfAbsent(
        int $gameweekId,
        RecommendationSnapshot $snapshot
    ): bool {

        $snapshotData =
            $snapshot->toArray();


        $stmt =
            $this->db->prepare(
                "
                INSERT IGNORE INTO recommendation_snapshots (
                    gameweek_id,
                    entry_id,
                    captured_at,
                    deadline_time,
                    player_projections,
                    starting_xi,
                    captain_recommendation,
                    transfer_recommendations,
                    gameweek_decision,
                    chip_recommendations
                )
                VALUES (
                    :gameweek_id,
                    :entry_id,
                    :captured_at,
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


        $stmt->execute([

            ':gameweek_id' =>
                $gameweekId,

            ':entry_id' =>
                $snapshot->getEntryId(),

            ':captured_at' =>
                $snapshot->getCapturedAt(),

            ':deadline_time' =>
                $snapshot->getDeadlineTime(),

            ':player_projections' =>
                $this->encodeJson(
                    $snapshotData[
                        'player_projections'
                    ]
                ),

            ':starting_xi' =>
                $this->encodeJson(
                    $snapshotData[
                        'starting_xi'
                    ]
                ),

            ':captain_recommendation' =>
                $this->encodeJson(
                    $snapshotData[
                        'captain_recommendation'
                    ]
                ),

            ':transfer_recommendations' =>
                $this->encodeJson(
                    $snapshotData[
                        'transfer_recommendations'
                    ]
                ),

            ':gameweek_decision' =>
                $this->encodeJson(
                    $snapshotData[
                        'gameweek_decision'
                    ]
                ),

            ':chip_recommendations' =>
                $this->encodeJson(
                    $snapshotData[
                        'chip_recommendations'
                    ]
                )
        ]);


        return
            $stmt->rowCount()
            ===
            1;
    }


    /*
     * ============================================================
     * JSON ENCODING
     * ============================================================
     */

    private function encodeJson(
        array $value
    ): string {

        $json =
            json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
                |
                JSON_PRESERVE_ZERO_FRACTION
                |
                JSON_THROW_ON_ERROR
            );


        return
            $json;
    }


    /*
     * ============================================================
     * SNAPSHOT DECODING
     * ============================================================
     */

    private function decodeSnapshot(
        array $snapshot
    ): array {

        $jsonFields = [

            'player_projections',

            'starting_xi',

            'captain_recommendation',

            'transfer_recommendations',

            'gameweek_decision',

            'chip_recommendations'
        ];


        foreach (
            $jsonFields
            as $field
        ) {

            $snapshot[
                $field
            ] =
                json_decode(
                    (string) (
                        $snapshot[
                            $field
                        ]
                        ?? '[]'
                    ),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
        }


        return
            $snapshot;
    }
}