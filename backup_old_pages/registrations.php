<?php
$activePage = 'registrations';
$pageTitle = 'Suivi des Inscriptions';
require_once __DIR__ . '/includes/header.php';

$db = getDb();

// Handle registration actions (validate / mark paid)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['registration_id']) && isset($_POST['action'])) {
        $regId = (int) $_POST['registration_id'];
        if ($_POST['action'] === 'validate') {
            $update = $db->prepare("UPDATE registrations SET status = 'paid' WHERE id = ?");
            $update->execute([$regId]);
            flash('Inscription validée avec succès.');
        } elseif ($_POST['action'] === 'mark-pending') {
            $update = $db->prepare("UPDATE registrations SET status = 'pending' WHERE id = ?");
            $update->execute([$regId]);
            flash('Inscription remise en attente.');
        }
        header('Location: registrations.php');
        exit;
    }
}

$registrations = $db->query('SELECT r.*, s.name AS student_name FROM registrations r JOIN students s ON s.id = r.student_id ORDER BY r.date DESC')->fetchAll();

$total = (int) $db->query('SELECT SUM(amount) FROM registrations')->fetchColumn();
$pending = (int) $db->query("SELECT SUM(amount) FROM registrations WHERE status = 'pending'")->fetchColumn();
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();
?>

<div class="page__header">
    <div>
        <h1 class="page__title">Suivi des Inscriptions</h1>
        <p class="page__subtitle">Gérez les dossiers d'inscription et les paiements.</p>
    </div>
</div>

<div class="table-controls">
    <input id="registrations-search" data-filter-target="#registrations-list" type="search" placeholder="Rechercher une inscription..." />
</div>

<div class="grid" style="grid-template-columns: 1fr 320px;">
    <div id="registrations-list" class="list">
        <?php foreach ($registrations as $reg): ?>
            <div class="list__item">
                <div class="list__icon" style="background: var(--success); color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i class="icon-document-check"></i></div>
                <div class="list__body">
                    <div class="list__title"><?= htmlspecialchars($reg['student_name']); ?> <span class="list__id">#<?= htmlspecialchars($reg['code']); ?></span></div>
                    <div class="list__subtitle"><?= htmlspecialchars($reg['program']); ?> · <?= htmlspecialchars($reg['date']); ?></div>
                </div>
                <div class="list__actions">
                    <div class="list__amount">
                        <?= formatMoney((int) $reg['amount']); ?>
                        <span class="status status--<?= $reg['status'] === 'paid' ? 'success' : 'warning'; ?>"><?= strtoupper($reg['status']); ?></span>
                    </div>
                    <button class="btn btn--icon" title="Détails"><i class="icon-arrow-right"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div>
        <div class="panel panel--small">
            <h3>Résumé des Paiements</h3>
            <div class="summary-row">
                <span>Total Encaissé</span>
                <span class="summary-value" style="color: var(--success);"><?= formatMoney($total - $pending); ?></span>
            </div>
            <div class="summary-row">
                <span>En Attente</span>
                <span class="summary-value" style="color: var(--warning);"><?= formatMoney($pending); ?></span>
            </div>
            <div class="progress">
                <div class="progress__bar" style="width: <?= $total ? round((($total - $pending) / $total) * 100) : 0; ?>%"></div>
            </div>
            <p class="small"><?= $total ? round((($total - $pending) / $total) * 100) : 0; ?>% des frais de scolarité réglés</p>
        </div>

        <div class="panel panel--small" style="margin-top: 20px;">
            <h3 style="display: flex; align-items: center; gap: 10px;">
                <span style="color: var(--warning);"><i class="icon-warning"></i></span>
                RAPPEL DE VALIDATION
            </h3>
            <p style="margin: 10px 0; font-size: 14px; line-height: 1.5;">
                <?= $pendingCount; ?> étudiants attendent la validation de leur dossier. Un dossier non validé empêche l'accès aux cours.
            </p>
            <button class="btn btn--primary" style="width: 100%;">Traiter les dossiers</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
