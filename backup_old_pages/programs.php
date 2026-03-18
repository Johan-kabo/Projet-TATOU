<?php
$activePage = 'programs';
$pageTitle = 'Gestion des Programmes';
require_once __DIR__ . '/includes/header.php';

$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add-program') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $code = trim($_POST['code'] ?? '');

        if ($name) {
            $insert = $db->prepare('INSERT INTO programs (name, description, duration, code) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $description, $duration ?: null, $code]);
            flash('Programme ajouté avec succès.');
        } else {
            flash('Le nom du programme est requis.', 'error');
        }

        header('Location: programs.php');
        exit;
    }
}

$programs = $db->query('SELECT * FROM programs ORDER BY name')->fetchAll();
?>

<div class="page__header">
    <div>
        <h1 class="page__title">Gestion des Programmes</h1>
        <p class="page__subtitle">Configurez les filières et les cursus académiques.</p>
    </div>
    <div>
        <button class="btn btn--outline">📄 <i class="icon-pdf"></i> Rapport PDF</button>
        <button class="btn btn--primary" type="button" onclick="document.getElementById('add-program').classList.toggle('hidden')">+ Nouveau Programme</button>
    </div>
</div>

<div class="table-controls">
    <input type="search" placeholder="Rechercher un programme..." />
</div>

<div id="add-program" class="panel hidden">
    <form method="post" class="grid" style="gap: 12px;">
        <input name="name" placeholder="Nom du programme" required />
        <input name="code" placeholder="Code (ex: IA)" />
        <input name="duration" type="number" min="1" placeholder="Durée (semaines)" />
        <textarea name="description" placeholder="Description"></textarea>
        <input type="hidden" name="action" value="add-program" />
        <button type="submit" class="btn btn--primary" style="grid-column: span 2;">Ajouter</button>
    </form>
</div>

<div class="grid grid--cards">
    <?php foreach ($programs as $program): ?>
        <div class="card card--program">
            <div class="card__icon" style="background: var(--primary); color: white;"><i class="icon-book-open"></i></div>
            <span class="badge" style="position: absolute; top: 18px; right: 18px; background: var(--primary); color: white;"><?= htmlspecialchars($program['code']); ?></span>
            <h3><?= htmlspecialchars($program['name']); ?></h3>
            <p><?= htmlspecialchars($program['description']); ?></p>
            <div class="card__meta">
                <span>⏱ <i class="icon-clock"></i> <?= $program['duration'] ? (int) $program['duration'] : '-'; ?> sem.</span>
                <span>🔑 <i class="icon-code"></i> Code: <?= htmlspecialchars($program['code']); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
