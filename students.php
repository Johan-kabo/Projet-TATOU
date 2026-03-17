<?php
$activePage = 'students';
$pageTitle = 'Gestion des Étudiants';
require_once __DIR__ . '/includes/header.php';

$db = getDb();

// Handle actions (add student, toggle block)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle-block' && !empty($_POST['student_id'])) {
        $studentId = (int) $_POST['student_id'];
        $student = $db->prepare('SELECT status FROM students WHERE id = ?');
        $student->execute([$studentId]);
        $current = $student->fetchColumn();
        if ($current !== false) {
            $newStatus = $current === 'Blocked' ? 'Active' : 'Blocked';
            $update = $db->prepare('UPDATE students SET status = ? WHERE id = ?');
            $update->execute([$newStatus, $studentId]);
            flash("Statut de l'étudiant mis à jour : $newStatus.");
        }
        header('Location: students.php');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add-student') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $level = trim($_POST['level'] ?? 'L1');

        if ($name && $email && $program) {
            $insert = $db->prepare('INSERT INTO students (name, email, program, level, status, joined, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([
                $name,
                $email,
                $program,
                $level,
                'Pending',
                date('Y-m-d'),
                'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop',
            ]);
            flash("Étudiant ajouté avec succès.");
        } else {
            flash('Veuillez remplir tous les champs.', 'error');
        }

        header('Location: students.php');
        exit;
    }
}

$students = $db->query('SELECT * FROM students ORDER BY joined DESC')->fetchAll();
$programs = $db->query('SELECT name FROM programs ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page__header">
    <div>
        <h1 class="page__title">Gestion des Étudiants</h1>
        <p class="page__subtitle">Gérez les dossiers et les inscriptions de vos étudiants.</p>
    </div>
    <button class="btn btn--primary" type="button" onclick="document.getElementById('add-student').classList.toggle('hidden')">+ Nouvel Étudiant</button>
</div>

<div class="table-controls">
    <input id="students-search" data-filter-target="#students-table" type="search" placeholder="Rechercher par nom, email..." />
    <div class="table-controls__actions">
        <button class="btn btn--outline">🔽 <i class="icon-filter"></i> Filtres</button>
        <button class="btn btn--outline">📄 <i class="icon-pdf"></i> Générer PDF</button>
    </div>
</div>

<div id="add-student" class="panel hidden">
    <form method="post" class="grid" style="gap: 12px;">
        <input name="name" placeholder="Nom complet" required />
        <input name="email" type="email" placeholder="Email" required />
        <input name="program" placeholder="Programme" required />
        <select name="level">
            <option value="L1">L1</option>
            <option value="L2">L2</option>
            <option value="L3">L3</option>
        </select>
        <input type="hidden" name="action" value="add-student" />
        <button type="submit" class="btn btn--primary" style="grid-column: span 2;">Ajouter</button>
    </form>
</div>

<table id="students-table" class="table">
    <thead>
        <tr>
            <th>ÉTUDIANT</th>
            <th>PROGRAMME</th>
            <th>NIVEAU</th>
            <th>STATUT</th>
            <th>DATE D'INSCRIPTION</th>
            <th>ACTIONS</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td class="student">
                    <div class="avatar" style="background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; width: 40px; height: 40px; border-radius: 50%;">
                        <?= strtoupper(substr(htmlspecialchars($student['name']), 0, 1)); ?>
                    </div>
                    <div>
                        <div class="student__name"><?= htmlspecialchars($student['name']); ?></div>
                        <div class="student__email"><?= htmlspecialchars($student['email']); ?></div>
                    </div>
                </td>
                <td><?= htmlspecialchars($student['program']); ?></td>
                <td><span class="badge"><?= htmlspecialchars($student['level']); ?></span></td>
                <td>
                    <span class="badge badge--<?= $student['status'] === 'Active' ? 'success' : ($student['status'] === 'Blocked' ? 'danger' : 'warning'); ?>">
                        <?= htmlspecialchars($student['status']); ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($student['joined']); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="toggle-block" />
                        <input type="hidden" name="student_id" value="<?= $student['id']; ?>" />
                        <button type="submit" class="btn btn--outline">
                            <?= $student['status'] === 'Blocked' ? 'Débloquer' : 'Bloquer'; ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php';
