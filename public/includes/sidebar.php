<?php

$activeNav =
    $activeNav
    ?? '';

?>

<aside class="sidebar">

    <div class="brand">

        <div class="brand-mark">
            FI
        </div>

        <div>

            <div class="brand-name">
                FPL Intelligence
            </div>

            <div class="brand-version">
                v0.31.0
            </div>

        </div>

    </div>


    <nav
        class="main-navigation"
        aria-label="Main navigation"
    >

        <a
            href="index.php"
            class="nav-link <?= $activeNav === 'dashboard'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ◫
            </span>

            Dashboard
        </a>


        <a
            href="players.php"
            class="nav-link <?= $activeNav === 'players'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                👤
            </span>

            Players
        </a>


        <a
            href="compare.php"
            class="nav-link <?= $activeNav === 'compare'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ⇄
            </span>

            Compare
        </a>


        <a
            href="teams.php"
            class="nav-link <?= $activeNav === 'teams'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ⚽
            </span>

            Teams
        </a>


        <a
            href="#"
            class="nav-link <?= $activeNav === 'fixtures'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ◈
            </span>

            Fixtures
        </a>


        <a
            href="transfers.php"
            class="nav-link <?= $activeNav === 'transfers'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ⇄
            </span>

            Transfers
        </a>


        <a
            href="transfer-planner.php"
            class="nav-link <?= $activeNav === 'transfer-planner'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ⤢
            </span>

            Transfer Planner
        </a>
        
        <a
            href="transfer-optimizer.php"
            class="nav-link <?= $activeNav === 'transfer-optimizer'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ✦
            </span>

            Transfer Optimizer
        </a>


        <a
            href="squad.php"
            class="nav-link <?= $activeNav === 'squad'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ★
            </span>

            Squad Intelligence
        </a>
        
        <a
            href="gameweek.php"
            class="nav-link <?= (
                $activeNav
                ?? ''
            ) === 'gameweek'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ◉
            </span>

            Gameweek Intelligence
        </a>
        
        <a
            href="wildcard.php"
            class="nav-link <?= (
                $activeNav
                ?? ''
            ) === 'wildcard'
                ? 'active'
                : ''; ?>"
        >
            <span class="nav-icon">
                ✦
            </span>

            Wildcard Intelligence
        </a>

    </nav>


    <div class="sidebar-footer">

        <span class="status-dot online"></span>

        System Online

    </div>

</aside>