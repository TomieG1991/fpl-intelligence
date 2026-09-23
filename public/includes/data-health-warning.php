<?php

/*
 * ============================================================
 * DATA HEALTH WARNING
 * ============================================================
 *
 * Shared presentation component for application data health.
 *
 * Expected input:
 *
 * $dataHealth = [
 *     'update_type' => [
 *         'status' => 'Healthy|Running|Stale|Partial|Failed|Unavailable',
 *         'reason' => ...,
 *         'last_success_at' => ...
 *     ]
 * ];
 *
 * Healthy feeds are intentionally omitted from the rendered
 * output. Detailed update diagnostics remain available on the
 * Dashboard Data Health panel.
 */

$dataHealth =
    isset($dataHealth)
    && is_array($dataHealth)
        ? $dataHealth
        : [];


/*
 * ============================================================
 * DISPLAY LABELS
 * ============================================================
 */

$dataHealthLabels = [

    'bootstrap' =>
        'Bootstrap',

    'fixtures' =>
        'Fixtures',

    'player_fixture_history' =>
        'Player Fixture History'
];


/*
 * ============================================================
 * DEGRADED / ACTIVE FEEDS
 * ============================================================
 */

$dataHealthNotices =
    [];


foreach (
    $dataHealth
    as $updateType => $health
) {

    if (
        !is_array(
            $health
        )
    ) {

        continue;
    }


    $status =
        trim(
            (string) (
                $health[
                    'status'
                ]
                ?? ''
            )
        );


    /*
     * Healthy data needs no page-level notice.
     */
    if (
        $status === 'Healthy'
        ||
        $status === ''
    ) {

        continue;
    }


    $label =
        $dataHealthLabels[
            $updateType
        ]
        ?? (string) $updateType;


    $lastSuccessAt =
        $health[
            'last_success_at'
        ]
        ?? null;


    $dataHealthNotices[] = [

        'label' =>
            $label,

        'status' =>
            $status,

        'last_success_at' =>
            is_string(
                $lastSuccessAt
            )
            && trim(
                $lastSuccessAt
            ) !== ''
                ? trim(
                    $lastSuccessAt
                )
                : null
    ];
}


/*
 * ============================================================
 * NOTHING TO REPORT
 * ============================================================
 */

if (
    empty(
        $dataHealthNotices
    )
) {

    return;
}


/*
 * ============================================================
 * NOTICE CLASSIFICATION
 * ============================================================
 */

$hasStale =
    false;

$hasIssue =
    false;

$hasUnavailable =
    false;

$hasRunning =
    false;


foreach (
    $dataHealthNotices
    as $notice
) {

    $status =
        $notice[
            'status'
        ];


    if ($status === 'Stale') {

        $hasStale =
            true;

        continue;
    }


    if (
        $status === 'Failed'
        ||
        $status === 'Partial'
    ) {

        $hasIssue =
            true;

        continue;
    }


    if ($status === 'Unavailable') {

        $hasUnavailable =
            true;

        continue;
    }


    if ($status === 'Running') {

        $hasRunning =
            true;
    }
}


/*
 * More serious update states take precedence over stale or
 * informational states when several feeds are displayed
 * together.
 */

if ($hasIssue) {

    $noticeTitle =
        'Data update issue';

    $noticeClass =
        'data-health-warning';

} elseif ($hasStale) {

    $noticeTitle =
        'Data may be out of date';

    $noticeClass =
        'data-health-warning';

} elseif ($hasUnavailable) {

    $noticeTitle =
        'Data health unavailable';

    $noticeClass =
        'data-health-warning';

} elseif ($hasRunning) {

    $noticeTitle =
        'Data update in progress';

    $noticeClass =
        'data-health-notice';

} else {

    /*
     * Unknown future statuses should not silently disappear.
     */
    $noticeTitle =
        'Data health notice';

    $noticeClass =
        'data-health-warning';
}

?>

<div
    class="<?= htmlspecialchars(
        $noticeClass,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    role="<?= $hasRunning
        && !$hasIssue
        && !$hasStale
        && !$hasUnavailable
            ? 'status'
            : 'alert'; ?>"
>

    <div class="data-health-warning-content">

        <strong class="data-health-warning-title">
            <?= htmlspecialchars(
                $noticeTitle,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </strong>

        <p>
            One or more data sources used by this page may not
            reflect the latest available FPL information.
        </p>

        <ul class="data-health-warning-list">

            <?php foreach (
                $dataHealthNotices
                as $notice
            ): ?>

                <li>

                    <strong>
                        <?= htmlspecialchars(
                            $notice[
                                'label'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </strong>

                    —
                    <?= htmlspecialchars(
                        $notice[
                            'status'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                    <?php if (
                        $notice[
                            'last_success_at'
                        ]
                        !==
                        null
                    ): ?>

                        <span class="data-health-last-success">
                            (last successful update:
                            <?= htmlspecialchars(
                                $notice[
                                    'last_success_at'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>)
                        </span>

                    <?php endif; ?>

                </li>

            <?php endforeach; ?>

        </ul>

        <a
            href="index.php#data-health-title"
            class="data-health-warning-link"
        >
            View Data Health on Dashboard
        </a>

    </div>

</div>