<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../db/mysql_init.php';

$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'TAAJ Corp';

$searchTargets = [
    'students' => '#students-table',
    'registrations' => '#registrations-list',
];
$searchTarget = $searchTargets[$activePage] ?? '';

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle); ?> - TAAJ Corp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/icons.css" />
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand__icon"><i class="icon-graduation-cap"></i></div>
            <div class="brand__name">TAAJ Corp</div>
        </div>

        <nav class="nav">
            <a href="dashboard.php" class="nav__item <?= navActive('dashboard', $activePage); ?>">
                <span class="nav__icon"><i class="icon-dashboard"></i></span>
                <span>Tableau de bord</span>
            </a>
            <a href="students.php" class="nav__item <?= navActive('students', $activePage); ?>">
                <span class="nav__icon"><i class="icon-users"></i></span>
                <span>Étudiants</span>
            </a>
            <a href="programs.php" class="nav__item <?= navActive('programs', $activePage); ?>">
                <span class="nav__icon"><i class="icon-book-open"></i></span>
                <span>Programmes</span>
            </a>
            <a href="registrations.php" class="nav__item <?= navActive('registrations', $activePage); ?>">
                <span class="nav__icon"><i class="icon-document"></i></span>
                <span>Inscriptions</span>
            </a>
            <a href="stats.php" class="nav__item <?= navActive('stats', $activePage); ?>">
                <span class="nav__icon"><i class="icon-chart-bar"></i></span>
                <span>Statistiques</span>
            </a>
        </nav>

        <div class="sidebar__footer">
            <a href="#" class="sidebar__link"><i class="icon-settings"></i> Paramètres</a>
            <a href="#" class="sidebar__link"><i class="icon-logout"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar__search">
                <input id="global-search" type="search" placeholder="Rechercher un étudiant, un cours..." data-filter-target="<?= htmlspecialchars($searchTarget); ?>" />
            </div>
            <div class="topbar__actions">
                <button class="btn btn--icon" title="Notifications"><i class="icon-bell"></i></button>
                <div class="profile">
                    <span class="profile__name">Johan Kabo</span>
                    <span class="profile__role">Administrateur</span>
                    <span class="profile__avatar">K</span>
                </div>
            </div>
        </header>

        <section class="page">
            <?php if ($flash): ?>
                <div class="alert alert--<?= $flash['type'] === 'error' ? 'danger' : 'success'; ?>">
                    <?= htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
