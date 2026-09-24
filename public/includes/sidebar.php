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
                v1.2.0
            </div>

        </div>

    </div>


    <button
        type="button"
        class="mobile-navigation-toggle"
        aria-controls="main-navigation"
        aria-expanded="false"
        aria-label="Toggle main navigation"
    >
        <span class="mobile-navigation-toggle-label">
            Menu
        </span>

        <span
            class="mobile-navigation-toggle-icon"
            aria-hidden="true"
        >
            ☰
        </span>
    </button>


    <nav
        id="main-navigation"
        class="main-navigation"
        aria-label="Main navigation"
    >

        <a
            href="index.php"
            class="nav-link <?= $activeNav === 'dashboard'
                ? 'active'
                : ''; ?>"
            <?= $activeNav === 'dashboard'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'players'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'compare'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'teams'
                ? 'aria-current="page"'
                : ''; ?>
        >
            <span class="nav-icon">
                ⚽
            </span>

            Teams
        </a>


        <a
            href="fixtures.php"
            class="nav-link <?= $activeNav === 'fixtures'
                ? 'active'
                : ''; ?>"
            <?= $activeNav === 'fixtures'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'transfers'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'transfer-planner'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'transfer-optimizer'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= $activeNav === 'squad'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= (
                $activeNav
                ?? ''
            ) === 'gameweek'
                ? 'aria-current="page"'
                : ''; ?>
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
            <?= (
                $activeNav
                ?? ''
            ) === 'wildcard'
                ? 'aria-current="page"'
                : ''; ?>
        >
            <span class="nav-icon">
                ✦
            </span>

            Wildcard Intelligence
        </a>


        <a
            href="chips.php"
            class="nav-link <?= (
                $activeNav
                ?? ''
            ) === 'chips'
                ? 'active'
                : ''; ?>"
            <?= (
                $activeNav
                ?? ''
            ) === 'chips'
                ? 'aria-current="page"'
                : ''; ?>
        >
            <span class="nav-icon">
                ◇
            </span>

            Chip Intelligence
        </a>

    </nav>


    <div class="sidebar-footer">

        <a
            href="https://ko-fi.com/fplintelligence"
            class="sidebar-support-link"
            target="_blank"
            rel="noopener noreferrer"
        >
            <span
                class="sidebar-support-icon"
                aria-hidden="true"
            >
                ☕
            </span>

            Support FPL Intelligence
        </a>


        <a
            href="index.php#data-health-title"
            class="sidebar-health-link"
        >
            Health on Dashboard
        </a>

    </div>

</aside>