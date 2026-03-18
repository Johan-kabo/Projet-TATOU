<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

// Récupérer les statistiques
$stats = [
    'total_students' => 0,
    'active_students' => 0,
    'pending_students' => 0,
    'inactive_students' => 0,
    'total_programs' => 0,
    'total_registrations' => 0,
    'paid_registrations' => 0,
    'pending_registrations' => 0,
    'unpaid_registrations' => 0
];

try {
    // Statistiques des étudiants
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stats['active_students'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'pending'");
    $stats['pending_students'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'inactive'");
    $stats['inactive_students'] = $stmt->fetch()['count'];
    
    // Statistiques des programmes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM programs");
    $stats['total_programs'] = $stmt->fetch()['count'];
    
    // Statistiques des inscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
    $stats['total_registrations'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'paid'");
    $stats['paid_registrations'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'pending'");
    $stats['pending_registrations'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'unpaid'");
    $stats['unpaid_registrations'] = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    // En cas d'erreur, garder les valeurs par défaut
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de Bord - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --bg: #f8fafc; --sidebar: #1e293b; --sidebar-text: #cbd5e1;
    --text-primary: #1a202c; --text-muted: #64748b; --border: #e2e8f0;
    --accent: #f59e0b; --page-bg: #f8fafc; --red: #dc2626;
}
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg); min-height: 100vh;
}
.container {
    max-width: 1400px; margin: 0 auto; padding: 20px;
}
.header {
    background: white; border-radius: 16px; padding: 24px; margin-bottom: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.title {
    font-size: 28px; font-weight: 800; color: var(--text-primary);
}
.subtitle {
    color: var(--text-muted); margin-top: 8px;
}
.kpi-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px; margin-bottom: 24px;
}
.kpi-card {
    background: white; border-radius: 16px; padding: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover {
    transform: translateY(-2px); box-shadow: 0 8px 12px rgba(0,0,0,0.15);
}
.kpi-header {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
}
.kpi-icon {
    width: 48px; height: 48px; border-radius: 12px; display: flex;
    align-items: center; justify-content: center; font-size: 20px;
}
.kpi-title {
    font-size: 14px; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.5px;
}
.kpi-value {
    font-size: 32px; font-weight: 800; color: var(--text-primary);
    line-height: 1;
}
.kpi-change {
    font-size: 12px; font-weight: 600; margin-top: 8px;
    display: flex; align-items: center; gap: 4px;
}
.positive { color: #10b981; }
.negative { color: #dc2626; }
.charts-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px; margin-bottom: 24px;
}
.chart-card {
    background: white; border-radius: 16px; padding: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.chart-title {
    font-size: 18px; font-weight: 700; color: var(--text-primary);
    margin-bottom: 20px;
}
.chart-container {
    height: 300px; display: flex; align-items: flex-end;
    justify-content: space-around;
}
.chart-bar {
    flex: 1; margin: 0 8px; border-radius: 8px; position: relative;
    transition: all 0.3s; cursor: pointer;
}
.chart-bar:hover {
    opacity: 0.8; transform: translateY(-2px);
}
.chart-label {
    position: absolute; bottom: -25px; left: 50%;
    transform: translateX(-50%); font-size: 12px;
    font-weight: 600; text-align: center; width: 100%;
}
.chart-value {
    position: absolute; top: -25px; left: 50%;
    transform: translateX(-50%); font-size: 12px;
    font-weight: 700; color: var(--text-primary);
}
.actions-row {
    display: flex; gap: 12px; margin-top: 24px;
}
.btn {
    background: var(--accent); color: white; border: none; border-radius: 12px;
    padding: 12px 24px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-decoration: none; display: inline-flex;
    align-items: center; gap: 8px;
}
.btn:hover {
    background: #d97706; transform: translateY(-1px);
}
.btn-primary { background: #3b82f6; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #10b981; }
.btn-success:hover { background: #059669; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">📊 Tableau de Bord</h1>
        <p class="subtitle">Vue d'ensemble en temps réel de votre plateforme</p>
    </div>

    <!-- KPIs Principaux -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #3b82f620; color: #3b82f6;">👨‍🎓</div>
                <div class="kpi-title">Total Étudiants</div>
            </div>
            <div class="kpi-value" id="totalStudents"><?php echo $stats['total_students']; ?></div>
            <div class="kpi-change positive">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
                En direct
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #10b98120; color: #10b981;">✅</div>
                <div class="kpi-title">Étudiants Actifs</div>
            </div>
            <div class="kpi-value" id="activeStudents"><?php echo $stats['active_students']; ?></div>
            <div class="kpi-change positive">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
                <?php echo $stats['total_students'] > 0 ? round(($stats['active_students'] / $stats['total_students']) * 100, 1) : 0; ?>%
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #f59e0b20; color: #f59e0b;">📚</div>
                <div class="kpi-title">Programmes</div>
            </div>
            <div class="kpi-value"><?php echo $stats['total_programs']; ?></div>
            <div class="kpi-change positive">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
                Total
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #8b5cf620; color: #8b5cf6;">💰</div>
                <div class="kpi-title">Inscriptions</div>
            </div>
            <div class="kpi-value"><?php echo $stats['total_registrations']; ?></div>
            <div class="kpi-change positive">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
                Cette année
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3 class="chart-title">📊 Étudiants par Niveau</h3>
            <div class="chart-container" id="levelChart">
                <div style="text-align: center; color: #64748b; padding: 40px;">
                    Chargement des données...
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">📈 Étudiants par Programme</h3>
            <div class="chart-container" id="programChart">
                <div style="text-align: center; color: #64748b; padding: 40px;">
                    Chargement des données...
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">📊 Statut des Étudiants</h3>
            <div class="chart-container" id="statusChart">
                <div style="text-align: center; color: #64748b; padding: 40px;">
                    Chargement des données...
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">💳 Statut des Paiements</h3>
            <div class="chart-container" id="paymentChart">
                <div style="text-align: center; color: #64748b; padding: 40px;">
                    Chargement des données...
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Rapides -->
    <div class="actions-row">
        <a href="students.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Gérer les Étudiants
        </a>
        
        <a href="programs.php" class="btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            Gérer les Programmes
        </a>
        
        <a href="registrations_dynamic.php" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            Gérer les Inscriptions
        </a>
        
        <button class="btn" onclick="refreshData()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"/>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
            </svg>
            Actualiser
        </button>
    </div>
</div>

<script>
// Variables globales
let students = [];
let registrations = [];

// Fonction pour charger les données depuis les APIs
async function loadDashboardData() {
    try {
        // Charger les étudiants
        const studentsResponse = await fetch('api_crud_students.php');
        const studentsText = await studentsResponse.text();
        
        if (studentsText) {
            const studentsData = JSON.parse(studentsText);
            if (studentsData.success) {
                students = studentsData.students || [];
                updateCharts();
            }
        }
        
        // Charger les inscriptions
        const registrationsResponse = await fetch('api_crud_registrations.php');
        const registrationsText = await registrationsResponse.text();
        
        if (registrationsText) {
            const registrationsData = JSON.parse(registrationsText);
            if (registrationsData.success) {
                registrations = registrationsData.registrations || [];
                updatePaymentChart();
            }
        }
        
    } catch (error) {
        console.error('Erreur de chargement:', error);
    }
}

// Fonction pour mettre à jour les graphiques
function updateCharts() {
    updateLevelChart();
    updateProgramChart();
    updateStatusChart();
}

// Graphique par niveau
function updateLevelChart() {
    const levelData = {};
    students.forEach(student => {
        const level = student.level || 'Non spécifié';
        levelData[level] = (levelData[level] || 0) + 1;
    });
    
    const container = document.getElementById('levelChart');
    if (container && Object.keys(levelData).length > 0) {
        const labels = Object.keys(levelData);
        const data = Object.values(levelData);
        const maxValue = Math.max(...data);
        
        container.innerHTML = labels.map((label, index) => `
            <div class="chart-bar" style="height: ${(data[index] / maxValue) * 250}px; background: #f59e0b;">
                <div class="chart-value">${data[index]}</div>
                <div class="chart-label">${label}</div>
            </div>
        `).join('');
    }
}

// Graphique par programme
function updateProgramChart() {
    const programData = {};
    students.forEach(student => {
        const program = student.program_name || student.prog || 'Non spécifié';
        programData[program] = (programData[program] || 0) + 1;
    });
    
    const container = document.getElementById('programChart');
    if (container && Object.keys(programData).length > 0) {
        const labels = Object.keys(programData);
        const data = Object.values(programData);
        const maxValue = Math.max(...data);
        
        container.innerHTML = labels.map((label, index) => `
            <div class="chart-bar" style="height: ${(data[index] / maxValue) * 250}px; background: #10b981;">
                <div class="chart-value">${data[index]}</div>
                <div class="chart-label">${label.substring(0, 8)}${label.length > 8 ? '...' : ''}</div>
            </div>
        `).join('');
    }
}

// Graphique par statut
function updateStatusChart() {
    const statusData = {
        'Actif': students.filter(s => s.status === 'active').length,
        'En attente': students.filter(s => s.status === 'pending').length,
        'Inactif': students.filter(s => s.status === 'inactive').length
    };
    
    const container = document.getElementById('statusChart');
    if (container) {
        const labels = Object.keys(statusData);
        const data = Object.values(statusData);
        const colors = ['#10b981', '#f59e0b', '#dc2626'];
        const maxValue = Math.max(...data);
        
        container.innerHTML = labels.map((label, index) => `
            <div class="chart-bar" style="height: ${(data[index] / maxValue) * 250}px; background: ${colors[index]};">
                <div class="chart-value">${data[index]}</div>
                <div class="chart-label">${label}</div>
            </div>
        `).join('');
    }
}

// Graphique par statut de paiement
function updatePaymentChart() {
    const paymentData = {
        'Payé': registrations.filter(r => r.payment_status === 'paid').length,
        'En attente': registrations.filter(r => r.payment_status === 'pending').length,
        'Non payé': registrations.filter(r => r.payment_status === 'unpaid').length
    };
    
    const container = document.getElementById('paymentChart');
    if (container) {
        const labels = Object.keys(paymentData);
        const data = Object.values(paymentData);
        const colors = ['#10b981', '#f59e0b', '#dc2626'];
        const maxValue = Math.max(...data);
        
        container.innerHTML = labels.map((label, index) => `
            <div class="chart-bar" style="height: ${(data[index] / maxValue) * 250}px; background: ${colors[index]};">
                <div class="chart-value">${data[index]}</div>
                <div class="chart-label">${label}</div>
            </div>
        `).join('');
    }
}

// Fonction pour rafraîchir les données
function refreshData() {
    // Afficher un indicateur de chargement
    document.querySelectorAll('.chart-container').forEach(container => {
        container.innerHTML = '<div style="text-align: center; color: #64748b; padding: 40px;">Chargement...</div>';
    });
    
    // Recharger les données
    loadDashboardData();
}

// Charger les données au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    
    // Rafraîchir automatiquement toutes les 30 secondes
    setInterval(loadDashboardData, 30000);
});
</script>
</body>
</html>
