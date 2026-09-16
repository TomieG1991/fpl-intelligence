<?php

/*
 * ============================================================
 * UPDATE HEALTH DISPLAY
 * ============================================================
 *
 * Expected input:
 *
 * $updateHealth = [
 *     'bootstrap' => [...],
 *     'fixtures' => [...],
 *     'player_fixture_history' => [...]
 * ];
 */


$healthLabels = [

    'bootstrap' =>
        'Bootstrap',

    'fixtures' =>
        'Fixtures',

    'player_fixture_history' =>
        'Player Fixture History'
];

?>

<section
    class="dashboard-card update-health-card"
    aria-labelledby="data-health-title"
>

    <div class="card-header">

        <div>

            <p class="card-kicker">
                Application Health
            </p>

            <h2 id="data-health-title">
                Data Health
            </h2>

        </div>

    </div>


    <div class="update-health-list">

        <?php foreach ($healthLabels as $updateType => $label): ?>

            <?php

            $health =
                $updateHealth[
                    $updateType
                ]
                ?? [];


            $status =
                (string) (
                    $health[
                        'status'
                    ]
                    ?? 'Unavailable'
                );


            $reason =
                (string) (
                    $health[
                        'reason'
                    ]
                    ?? 'No health information is available.'
                );


            $lastSuccessAt =
                $health[
                    'last_success_at'
                ]
                ?? null;


            $recordsReceived =
                $health[
                    'records_received'
                ]
                ?? null;


            $recordsUpdated =
                $health[
                    'records_updated'
                ]
                ?? null;


            $recordsSkipped =
                $health[
                    'records_skipped'
                ]
                ?? null;


            $recordsFailed =
                $health[
                    'records_failed'
                ]
                ?? null;


            $durationMs =
                $health[
                    'duration_ms'
                ]
                ?? null;


            $errorMessage =
                trim(
                    (string) (
                        $health[
                            'error_message'
                        ]
                        ?? ''
                    )
                );

            ?>

            <article class="update-health-item">

                <div class="update-health-header">

                    <div class="update-health-label">

                        <?= htmlspecialchars(
                            $label,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </div>


                    <div
                        class="update-health-status update-health-status-<?= htmlspecialchars(
                            strtolower(
                                $status
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >

                        <?= htmlspecialchars(
                            $status,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </div>

                </div>


                <div class="update-health-reason">

                    <?= htmlspecialchars(
                        $reason,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>


                <div class="update-health-meta">

                    <div>

                        <strong>
                            Last Success:
                        </strong>

                        <?= $lastSuccessAt !== null
                            ? htmlspecialchars(
                                (string) $lastSuccessAt,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'N/A'; ?>

                    </div>


                    <div>

                        <strong>
                            Received:
                        </strong>

                        <?= $recordsReceived !== null
                            ? number_format(
                                (int) $recordsReceived
                            )
                            : 'N/A'; ?>

                    </div>


                    <div>

                        <strong>
                            Updated:
                        </strong>

                        <?= $recordsUpdated !== null
                            ? number_format(
                                (int) $recordsUpdated
                            )
                            : 'N/A'; ?>

                    </div>


                    <div>

                        <strong>
                            Skipped:
                        </strong>

                        <?= $recordsSkipped !== null
                            ? number_format(
                                (int) $recordsSkipped
                            )
                            : 'N/A'; ?>

                    </div>


                    <div>

                        <strong>
                            Failed:
                        </strong>

                        <?= $recordsFailed !== null
                            ? number_format(
                                (int) $recordsFailed
                            )
                            : 'N/A'; ?>

                    </div>


                    <div>

                        <strong>
                            Duration:
                        </strong>

                        <?= $durationMs !== null
                            ? number_format(
                                (int) $durationMs
                            )
                            . ' ms'
                            : 'N/A'; ?>

                    </div>

                </div>


                <?php if ($errorMessage !== ''): ?>

                    <div class="update-health-error">

                        <?= htmlspecialchars(
                            $errorMessage,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </div>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    </div>

</section>