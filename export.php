<?php
require_once __DIR__ . '/db/mysql_init.php';

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';
$allowed = ['students', 'programs', 'registrations'];
if (!in_array($type, $allowed, true)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Type d’export invalide.';
    exit;
}

$db = getDb();

if ($format === 'pdf') {
    // Export as a simple printable HTML page (user can "Print as PDF").
    $rows = [];
    $headers = [];

    if ($type === 'students') {
        $headers = ['ID', 'Nom', 'Email', 'Programme', 'Niveau', 'Statut', 'Inscription'];
        $rows = $db->query('SELECT * FROM students ORDER BY id')->fetchAll();
    } elseif ($type === 'programs') {
        $headers = ['ID', 'Nom', 'Description', 'Durée', 'Code'];
        $rows = $db->query('SELECT * FROM programs ORDER BY id')->fetchAll();
    } elseif ($type === 'registrations') {
        $headers = ['ID', 'Étudiant', 'Programme', 'Date', 'Statut', 'Montant', 'Code'];
        $rows = $db->query('SELECT r.*, s.name AS student_name FROM registrations r JOIN students s ON s.id = r.student_id ORDER BY r.id')->fetchAll();
    }

    $title = ucfirst($type);
    echo "<!DOCTYPE html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><title>Export $title</title><style>body{font-family:system-ui, sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f3f4f6;}h1{margin-top:0;}@media print{button{display:none;}}</style></head><body>";
    echo "<h1>Export $title</h1>";
    echo "<button onclick=\"window.print()\">Imprimer / Enregistrer en PDF</button>";
    echo "<table><thead><tr>";
    foreach ($headers as $h) {
        echo "<th>" . htmlspecialchars($h) . "</th>";
    }
    echo "</tr></thead><tbody>";

    foreach ($rows as $row) {
        echo "<tr>";
        if ($type === 'students') {
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['program']) . "</td>";
            echo "<td>" . htmlspecialchars($row['level']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['joined']) . "</td>";
        } elseif ($type === 'programs') {
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
            echo "<td>" . htmlspecialchars($row['code']) . "</td>";
        } elseif ($type === 'registrations') {
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['program']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
            echo "<td>" . htmlspecialchars($row['code']) . "</td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table></body></html>";
    exit;
}

$filename = sprintf('%s-%s.csv', $type, date('Ymd-His'));
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

if ($type === 'students') {
    fputcsv($out, ['ID', 'Nom', 'Email', 'Programme', 'Niveau', 'Statut', 'Inscription', 'Avatar']);
    $rows = $db->query('SELECT * FROM students ORDER BY id')->fetchAll();
    foreach ($rows as $row) {
        fputcsv($out, [$row['id'], $row['name'], $row['email'], $row['program'], $row['level'], $row['status'], $row['joined'], $row['avatar']]);
    }
} elseif ($type === 'programs') {
    fputcsv($out, ['ID', 'Nom', 'Description', 'Durée', 'Code']);
    $rows = $db->query('SELECT * FROM programs ORDER BY id')->fetchAll();
    foreach ($rows as $row) {
        fputcsv($out, [$row['id'], $row['name'], $row['description'], $row['duration'], $row['code']]);
    }
} elseif ($type === 'registrations') {
    fputcsv($out, ['ID', 'Étudiant', 'Programme', 'Date', 'Statut', 'Montant', 'Code']);
    $rows = $db->query('SELECT r.*, s.name AS student_name FROM registrations r JOIN students s ON s.id = r.student_id ORDER BY r.id')->fetchAll();
    foreach ($rows as $row) {
        fputcsv($out, [$row['id'], $row['student_name'], $row['program'], $row['date'], $row['status'], $row['amount'], $row['code']]);
    }
}

fclose($out);
