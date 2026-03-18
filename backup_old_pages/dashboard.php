<?php
$activePage = 'dashboard';
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';

$db = getDb();

$totalStudents = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalPrograms = (int) $db->query('SELECT COUNT(*) FROM programs')->fetchColumn();

$totalRevenue = (int) $db->query('SELECT SUM(amount) FROM registrations')->fetchColumn();
$pendingRegistrations = (int) $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();

$recentStudents = $db->query('SELECT name, program, joined FROM students ORDER BY joined DESC LIMIT 3')->fetchAll();
?>

<h1 class="page__title">Tableau de bord</h1>
<p class="page__subtitle">Bienvenue sur la plateforme de gestion TAAJ Corp.</p>

<div class="cards">
    <div class="card">
        <div class="card__icon"><i class="icon-users"></i></div>
        <div class="card__body">
            <div class="card__label">Total Étudiants</div>
            <div class="card__value"><?= $totalStudents; ?></div>
            <div class="card__delta">↑ +12%</div>
        </div>
    </div>
    <div class="card">
        <div class="card__icon"><i class="icon-user-plus"></i></div>
        <div class="card__body">
            <div class="card__label">Nouvelles Inscriptions</div>
            <div class="card__value"><?= $totalStudents; ?></div>
            <div class="card__delta">↑ +5%</div>
        </div>
    </div>
    <div class="card">
        <div class="card__icon"><i class="icon-book"></i></div>
        <div class="card__body">
            <div class="card__label">Programmes Actifs</div>
            <div class="card__value"><?= $totalPrograms; ?></div>
            <div class="card__delta">↓ -2%</div>
        </div>
    </div>
    <div class="card">
        <div class="card__icon"><i class="icon-credit-card"></i></div>
        <div class="card__body">
            <div class="card__label">Revenus Estimés</div>
            <div class="card__value"><?= formatMoney($totalRevenue); ?></div>
            <div class="card__delta">↑ +18%</div>
        </div>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <div class="panel__header">
            <h2>Aperçu des Inscriptions</h2>
            <select class="select">
                <option>Cette année</option>
                <option>Derniers 6 mois</option>
            </select>
        </div>
        <div class="panel__content">
            <canvas id="registrationsChart" width="400" height="220"></canvas>
        </div>
    </div>
    <div class="panel">
        <div class="panel__header">
            <h2>Activités Récentes</h2>
        </div>
        <div class="panel__content">
            <?php foreach ($recentStudents as $student): ?>
                <div class="activity">
                    <div class="activity__meta">
                        <span class="activity__name"><?= htmlspecialchars($student['name']); ?></span>
                        <span class="activity__subtitle">Inscrit en <?= htmlspecialchars($student['program']); ?></span>
                    </div>
                    <span class="activity__date"><?= htmlspecialchars($student['joined']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('registrationsChart').getContext('2d');
    const registrationsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Sept', 'Oct', 'Nov', 'Déc', 'Jan', 'Fév', 'Mar'],
            datasets: [{
                label: 'Inscriptions',
                data: [2, 3, 2, 4, 3, 3, 4],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 1,
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
});
</script>
