<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

// Inclure la connexion DB à gestion_inscription
include 'db/mysql_connection_gestion_inscription.php';

// Récupérer les statistiques avancées
try {
    // Total étudiants
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];

    // Taux de réussite (simulation)
    $successRate = 87;

    // Revenus totaux
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM registrations WHERE payment_status = 'paid'");
    $totalRevenue = $stmt->fetch()['total'] ?? 24800000;

    // Programmes actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM programs WHERE active = 1");
    $activePrograms = $stmt->fetch()['total'];

    // Répartition par filière
    $stmt = $pdo->query("
        SELECT p.name, COUNT(s.id) as count 
        FROM programs p 
        LEFT JOIN students s ON p.id = s.program_id 
        GROUP BY p.id, p.name 
        ORDER BY count DESC
    ");
    $programsDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $totalStudents = 124;
    $successRate = 87;
    $totalRevenue = 24800000;
    $activePrograms = 7;
    $programsDistribution = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Statistiques Avancées</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link rel="stylesheet" href="assets/css/modern.css" />
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  :root {
    --sidebar-bg: #0F1623;
    --accent: #F59E0B;
    --accent-hover: #D97706;
    --page-bg: #F1F5F9;
    --card-bg: #FFFFFF;
    --text-primary: #0F172A;
    --text-muted: #64748B;
    --border: #E2E8F0;
    --green: #10B981; --red: #EF4444; --blue: #3B82F6; --purple: #8B5CF6;
    --font: 'Plus Jakarta Sans', sans-serif;
  }
  html, body { height: 100%; font-family: var(--font); background: var(--page-bg); color: var(--text-primary); }
  .layout { display: flex; min-height: 100vh; }

  /* SIDEBAR */
  .sidebar { width: 230px; background: var(--sidebar-bg); display: flex; flex-direction: column; position: fixed; top:0; left:0; bottom:0; z-index:200; }
  .sidebar-logo { display:flex; align-items:center; gap:10px; padding:20px 18px 16px; border-bottom:1px solid rgba(255,255,255,0.07); }
  .logo-icon { width:36px; height:36px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; color:#fff; }
  .logo-text { font-size:15px; font-weight:700; color:#fff; }
  .sidebar-nav { flex:1; padding:14px 10px; overflow-y:auto; }
  .nav-section-label { font-size:9.5px; font-weight:700; letter-spacing:1.2px; color:rgba(148,163,184,0.45); text-transform:uppercase; padding:10px 10px 4px; }
  .nav-item { display:flex; align-items:center; gap:9px; padding:9px 12px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:500; color:#94A3B8; transition:background .18s,color .18s; margin-bottom:1px; border:none; background:transparent; width:100%; text-align:left; font-family:var(--font); text-decoration:none; }
  .nav-item:hover { background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active { background:var(--accent); color:#fff; }
  .nav-item svg { width:15px; height:15px; flex-shrink:0; }
  .sidebar-bottom { padding:10px; border-top:1px solid rgba(255,255,255,0.07); }
  .nav-item.danger:hover { background:rgba(239,68,68,0.12); color:#FCA5A5; }

  /* MAIN */
  .main { margin-left:230px; flex:1; display:flex; flex-direction:column; }

  /* TOPBAR */
  .topbar { background:#fff; border-bottom:1px solid var(--border); padding:11px 26px; display:flex; align-items:center; gap:14px; position:sticky; top:0; z-index:100; }
  .search-wrap { flex:1; max-width:380px; display:flex; align-items:center; gap:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:10px; padding:7px 14px; }
  .search-wrap input { border:none; background:transparent; outline:none; font-family:var(--font); font-size:13px; color:var(--text-primary); width:100%; }
  .search-wrap input::placeholder { color:var(--text-muted); }
  .topbar-right { margin-left:auto; display:flex; align-items:center; gap:12px; }
  .notif-btn { width:36px; height:36px; border-radius:50%; background:var(--page-bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; }
  .notif-dot { position:absolute; top:7px; right:7px; width:7px; height:7px; border-radius:50%; background:var(--red); border:2px solid #fff; }
  .user-block { display:flex; align-items:center; gap:10px; }
  .uname { font-size:13px; font-weight:600; color:var(--text-primary); }
  .urole { font-size:11px; color:var(--text-muted); }
  .avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#667EEA,#764BA2); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }

  /* CONTENT */
  .content { padding:24px 26px; flex:1; }
  .page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:22px; }
  .page-title { font-size:22px; font-weight:800; }
  .page-sub { font-size:13px; color:var(--text-muted); margin-top:3px; }
  .header-actions { display:flex; gap:10px; align-items:center; }
  .btn-primary { display:flex; align-items:center; gap:7px; background:var(--accent); color:#fff; border:none; border-radius:10px; padding:10px 18px; font-size:13px; font-weight:600; font-family:var(--font); cursor:pointer; transition:background .18s; }
  .btn-primary:hover { background:var(--accent-hover); }
  .btn-outline { display:flex; align-items:center; gap:6px; border:1px solid var(--border); background:#fff; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:500; font-family:var(--font); color:var(--text-muted); cursor:pointer; }
  .btn-outline:hover { background:var(--page-bg); }
  .period-select { font-family:var(--font); font-size:13px; color:var(--text-primary); border:1px solid var(--border); border-radius:9px; padding:9px 14px; background:#fff; cursor:pointer; outline:none; }

  /* KPI ROW */
  .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
  .kpi-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:18px; animation:fadeUp .4s ease both; transition:box-shadow .2s; }
  .kpi-card:hover { box-shadow:0 4px 18px rgba(0,0,0,0.07); }
  .kpi-card:nth-child(1){animation-delay:.04s}.kpi-card:nth-child(2){animation-delay:.08s}.kpi-card:nth-child(3){animation-delay:.12s}.kpi-card:nth-child(4){animation-delay:.16s}
  .kpi-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px; }
  .kpi-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; }
  .kpi-label { font-size:12px; font-weight:500; color:var(--text-muted); margin-bottom:4px; }
  .kpi-value { font-size:26px; font-weight:800; color:var(--text-primary); }
  .kpi-value.sm { font-size:18px; }
  .kpi-trend { display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:600; margin-top:5px; }
  .trend-up { color:var(--green); } .trend-down { color:var(--red); }

  /* CHARTS GRID */
  .charts-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
  .charts-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px; }
  .charts-grid-full { display:grid; grid-template-columns:1fr; gap:16px; margin-bottom:16px; }

  .chart-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:20px; animation:fadeUp .45s ease both; }
  .chart-card:nth-child(1){animation-delay:.2s}.chart-card:nth-child(2){animation-delay:.25s}.chart-card:nth-child(3){animation-delay:.3s}
  .chart-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:18px; }
  .chart-title { font-size:15px; font-weight:700; color:var(--text-primary); }
  .chart-sub { font-size:12px; color:var(--text-muted); margin-top:2px; }
  .chart-wrap { position:relative; }
  .chart-wrap.h200 { height:200px; }
  .chart-wrap.h220 { height:220px; }
  .chart-wrap.h260 { height:260px; }
  .chart-wrap.h180 { height:180px; }

  /* LEGEND */
  .legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:14px; justify-content:center; }
  .legend-item { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:500; color:var(--text-muted); }
  .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

  /* RANK TABLE */
  .rank-table { width:100%; }
  .rank-row { display:flex; align-items:center; gap:12px; padding:9px 0; border-bottom:1px solid var(--border); }
  .rank-row:last-child { border-bottom:none; }
  .rank-num { width:22px; height:22px; border-radius:6px; background:var(--page-bg); font-size:11px; font-weight:700; color:var(--text-muted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .rank-num.top { background:#FFFBEB; color:var(--accent); }
  .rank-name { flex:1; font-size:13px; font-weight:600; color:var(--text-primary); }
  .rank-bar-wrap { flex:2; height:6px; background:var(--border); border-radius:20px; overflow:hidden; }
  .rank-bar { height:100%; border-radius:20px; }
  .rank-val { font-size:12px; font-weight:700; color:var(--text-muted); min-width:28px; text-align:right; }

  /* TAUX CARDS */
  .taux-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
  .taux-item { background:var(--page-bg); border-radius:10px; padding:14px; text-align:center; }
  .taux-val { font-size:22px; font-weight:800; color:var(--text-primary); }
  .taux-lbl { font-size:11.5px; color:var(--text-muted); margin-top:3px; font-weight:500; }
  .taux-bar { height:4px; border-radius:20px; margin-top:10px; }

  /* ── MODAL ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,22,35,0.55); z-index: 500;
    align-items: center; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #fff; border-radius: 16px; width: 520px; max-width: 95vw;
    max-height: 90vh; overflow-y: auto;
    animation: modalIn 0.22s ease both;
  }
  @keyframes modalIn { from { opacity:0; transform:scale(0.96) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px 16px; border-bottom: 1px solid var(--border);
  }
  .modal-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
  .modal-close {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
    background: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--text-muted); font-size: 16px; transition: background 0.15s;
  }
  .modal-close:hover { background: var(--page-bg); }
  .modal-body { padding: 20px 24px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .form-label { font-size: 12.5px; font-weight: 600; color: var(--text-primary); }
  .form-input, .form-select, .form-textarea {
    font-family: var(--font); font-size: 13px; color: var(--text-primary);
    border: 1px solid var(--border); border-radius: 9px; padding: 9px 13px;
    outline: none; transition: border-color 0.15s; background: #fff;
    width: 100%;
  }
  .form-textarea { resize: vertical; min-height: 100px; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--accent); }
  .modal-footer {
    display: flex; gap: 10px; justify-content: flex-end;
    padding: 16px 24px; border-top: 1px solid var(--border);
  }
  .btn-cancel {
    font-family: var(--font); font-size: 13px; font-weight: 600;
    border: 1px solid var(--border); background: #fff; border-radius: 9px;
    padding: 9px 20px; cursor: pointer; color: var(--text-muted);
    transition: background 0.15s;
  }
  .btn-cancel:hover { background: var(--page-bg); }

  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">T</div>
      <span class="logo-text">TAAJ Corp</span>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Principal</div>
      <a href="dashboard.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        Tableau de bord
      </a>
      <a href="students.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Étudiants
      </a>
      <a href="programs.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        Programmes
      </a>
      <div class="nav-section-label" style="margin-top:10px;">Gestion</div>
      <a href="registrations.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Inscriptions
      </a>
      <button class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Statistiques
      </button>
    </nav>
    <div class="sidebar-bottom">
      <button class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 1.79 12.88L19 17l-1.79.71A8 8 0 0 1 4.93 4.93"/></svg>
        Paramètres
      </button>
      <a href="logout.php" class="nav-item danger" style="color:#EF4444;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input placeholder="Rechercher un étudiant, un cours..." />
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <div class="notif-dot"></div>
        </div>
        <div class="user-block">
          <div>
            <div class="uname">Junior Atchonkeu</div>
            <div class="urole"><?php echo ucfirst($_SESSION['role']); ?></div>
          </div>
          <div class="avatar">JA</div>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="page-header">
        <div>
          <div class="page-title">Statistiques Avancées</div>
          <div class="page-sub">Analyse détaillée des performances de TAAJ Corp.</div>
        </div>
        <div class="header-actions">
          <select class="period-select">
            <option>Cette année</option>
            <option>Ce semestre</option>
            <option>Ce mois</option>
          </select>
          <button class="btn-outline" onclick="exportData()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter
          </button>
          <button class="btn-primary" onclick="openModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouveau Rapport
          </button>
          <button class="btn-primary" onclick="generatePDFReport()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Rapport PDF
          </button>
        </div>
      </div>

      <!-- KPI ROW -->
      <div class="kpi-row">
        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:#EFF6FF;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
          </div>
          <div class="kpi-label">Total Étudiants</div>
          <div class="kpi-value"><?php echo $totalStudents; ?></div>
          <div class="kpi-trend trend-up">↗ +12% ce mois</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:#ECFDF5;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
          </div>
          <div class="kpi-label">Taux de Réussite</div>
          <div class="kpi-value"><?php echo $successRate; ?>%</div>
          <div class="kpi-trend trend-up">↗ +3% vs N-1</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:#FFFBEB;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
          </div>
          <div class="kpi-label">Revenus Totaux</div>
          <div class="kpi-value sm"><?php echo number_format($totalRevenue, 0, ' ', ' '); ?> FCFA</div>
          <div class="kpi-trend trend-up">↗ +18% ce mois</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:#F5F3FF;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
            </div>
          </div>
          <div class="kpi-label">Programmes Actifs</div>
          <div class="kpi-value"><?php echo $activePrograms; ?></div>
          <div class="kpi-trend trend-down">↘ -2% vs trimestre</div>
        </div>
      </div>

      <!-- ROW 1: Donut + Barres niveaux -->
      <div class="charts-grid-2">
        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Répartition par Filière</div>
              <div class="chart-sub">Distribution des étudiants par programme</div>
            </div>
          </div>
          <div class="chart-wrap h200" style="display:flex;align-items:center;justify-content:center;">
            <canvas id="donutChart" style="max-height:200px;max-width:200px;"></canvas>
          </div>
          <div class="legend" id="donutLegend"></div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Taux de Réussite par Niveau</div>
              <div class="chart-sub">Pourcentage de validation par année</div>
            </div>
          </div>
          <div class="chart-wrap h220">
            <canvas id="barNiveau"></canvas>
          </div>
        </div>
      </div>

      <!-- ROW 2: Évolution + Paiements -->
      <div class="charts-grid-2">
        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Évolution des Inscriptions</div>
              <div class="chart-sub">Inscriptions mensuelles sur 12 mois</div>
            </div>
          </div>
          <div class="chart-wrap h220">
            <canvas id="lineChart"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Statut des Paiements</div>
              <div class="chart-sub">Répartition des paiements reçus</div>
            </div>
          </div>
          <div class="chart-wrap h220">
            <canvas id="barPaiements"></canvas>
          </div>
        </div>
      </div>

      <!-- ROW 3: Classement filières + Taux indicateurs -->
      <div class="charts-grid-2">
        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Classement des Filières</div>
              <div class="chart-sub">Par nombre d'étudiants inscrits</div>
            </div>
          </div>
          <div id="rankTable" class="rank-table"></div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Indicateurs de Performance</div>
              <div class="chart-sub">Taux clés de la plateforme</div>
            </div>
          </div>
          <div class="taux-grid">
            <div class="taux-item">
              <div class="taux-val" style="color:#10B981;">87%</div>
              <div class="taux-lbl">Taux de réussite</div>
              <div class="taux-bar" style="background:#10B981;width:87%;"></div>
            </div>
            <div class="taux-item">
              <div class="taux-val" style="color:#3B82F6;">75%</div>
              <div class="taux-lbl">Paiements reçus</div>
              <div class="taux-bar" style="background:#3B82F6;width:75%;"></div>
            </div>
            <div class="taux-item">
              <div class="taux-val" style="color:#F59E0B;">92%</div>
              <div class="taux-lbl">Taux de présence</div>
              <div class="taux-bar" style="background:#F59E0B;width:92%;"></div>
            </div>
            <div class="taux-item">
              <div class="taux-val" style="color:#8B5CF6;">68%</div>
              <div class="taux-lbl">Taux de complétion</div>
              <div class="taux-bar" style="background:#8B5CF6;width:68%;"></div>
            </div>
            <div class="taux-item">
              <div class="taux-val" style="color:#EF4444;">4%</div>
              <div class="taux-lbl">Taux d'abandon</div>
              <div class="taux-bar" style="background:#EF4444;width:4%;"></div>
            </div>
            <div class="taux-item">
              <div class="taux-val" style="color:#06B6D4;">81%</div>
              <div class="taux-lbl">Satisfaction</div>
              <div class="taux-bar" style="background:#06B6D4;width:81%;"></div>
            </div>
          </div>

          <!-- Radar chart -->
          <div class="chart-wrap h180" style="margin-top:18px;">
            <canvas id="radarChart"></canvas>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const FONT = 'Plus Jakarta Sans';
const gridColor = '#F1F5F9';
const tickColor = '#94A3B8';

Chart.defaults.font.family = FONT;

// ── DONUT ──
const donutData = {
  labels: ['Gestion', 'Informatique', 'Médecine', 'Économie', 'Droit', 'Filière IA'],
  datasets: [{
    data: [28, 34, 22, 19, 16, 5],
    backgroundColor: ['#F59E0B','#10B981','#3B82F6','#8B5CF6','#06B6D4','#EF4444'],
    borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
  }]
};
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: donutData,
  options: {
    responsive: true, maintainAspectRatio: false, cutout: '65%',
    plugins: {
      legend: { display: false },
      tooltip: { backgroundColor:'#0F172A', titleFont:{family:FONT,size:12}, bodyFont:{family:FONT,size:12}, padding:10, cornerRadius:8 }
    }
  }
});
const legend = document.getElementById('donutLegend');
const dColors = ['#F59E0B','#10B981','#3B82F6','#8B5CF6','#06B6D4','#EF4444'];
donutData.labels.forEach((l,i) => {
  legend.innerHTML += `<div class="legend-item"><div class="legend-dot" style="background:${dColors[i]};"></div>${l}</div>`;
});

// ── BAR NIVEAU ──
new Chart(document.getElementById('barNiveau'), {
  type: 'bar',
  data: {
    labels: ['L1', 'L2', 'L3', 'M1', 'M2'],
    datasets: [{
      label: 'Taux de réussite (%)',
      data: [75, 80, 85, 90, 95],
      backgroundColor: '#F59E0B',
      borderRadius: 8, borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { backgroundColor:'#0F172A', titleFont:{family:FONT,size:12}, bodyFont:{family:FONT,size:12}, padding:10, cornerRadius:8, callbacks: { label: ctx => ` ${ctx.parsed.y}%` } }
    },
    scales: {
      y: { beginAtZero:true, max:100, grid:{color:gridColor}, ticks:{color:tickColor,font:{family:FONT,size:11},callback:v=>v+'%'} },
      x: { grid:{display:false}, ticks:{color:tickColor,font:{family:FONT,size:11}} }
    }
  }
});

// ── LINE ──
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: ['Avr','Mai','Jui','Jul','Aoû','Sep','Oct','Nov','Déc','Jan','Fév','Mar'],
    datasets: [
      { label:'Inscriptions', data:[8,12,10,15,9,18,22,16,24,20,28,18], borderColor:'#F59E0B', backgroundColor:'rgba(245,158,11,0.08)', borderWidth:2.5, fill:true, tension:0.4, pointBackgroundColor:'#F59E0B', pointRadius:3, pointHoverRadius:5 },
      { label:'Validées',     data:[6,10,8,12,7,15,18,13,20,17,24,14], borderColor:'#10B981', backgroundColor:'rgba(16,185,129,0.06)', borderWidth:2.5, fill:true, tension:0.4, pointBackgroundColor:'#10B981', pointRadius:3, pointHoverRadius:5 }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins: {
      legend:{ position:'bottom', labels:{font:{family:FONT,size:12},boxWidth:10,padding:14} },
      tooltip:{ backgroundColor:'#0F172A', titleFont:{family:FONT,size:12}, bodyFont:{family:FONT,size:12}, padding:10, cornerRadius:8 }
    },
    scales: {
      y:{ beginAtZero:true, grid:{color:gridColor}, ticks:{color:tickColor,font:{family:FONT,size:11}} },
      x:{ grid:{display:false}, ticks:{color:tickColor,font:{family:FONT,size:11}} }
    }
  }
});

// ── BAR PAIEMENTS ──
new Chart(document.getElementById('barPaiements'), {
  type: 'bar',
  data: {
    labels: ['Sep','Oct','Nov','Déc','Jan','Fév','Mar'],
    datasets: [
      { label:'Payé',        data:[1200000,1800000,1400000,2200000,1900000,2600000,1750000], backgroundColor:'#10B981', borderRadius:6, borderSkipped:false },
      { label:'En attente',  data:[300000,400000,250000,500000,350000,420000,300000],         backgroundColor:'#F59E0B', borderRadius:6, borderSkipped:false },
      { label:'Non payé',    data:[100000,150000,200000,100000,180000,120000,150000],         backgroundColor:'#EF4444', borderRadius:6, borderSkipped:false },
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins: {
      legend:{ position:'bottom', labels:{font:{family:FONT,size:12},boxWidth:10,padding:14} },
      tooltip:{ backgroundColor:'#0F172A', titleFont:{family:FONT,size:12}, bodyFont:{family:FONT,size:12}, padding:10, cornerRadius:8, callbacks:{ label: ctx => ` ${ctx.dataset.label}: ${(ctx.parsed.y/1000).toFixed(0)}k FCFA` } }
    },
    scales: {
      x:{ stacked:false, grid:{display:false}, ticks:{color:tickColor,font:{family:FONT,size:11}} },
      y:{ stacked:false, beginAtZero:true, grid:{color:gridColor}, ticks:{color:tickColor,font:{family:FONT,size:11}, callback:v=>(v/1000000).toFixed(1)+'M'} }
    }
  }
});

// ── RANK TABLE ──
const ranks = [
  { name:'Informatique', val:34, color:'#10B981' },
  { name:'Gestion',      val:28, color:'#F59E0B' },
  { name:'Médecine',     val:22, color:'#3B82F6' },
  { name:'Économie',     val:19, color:'#8B5CF6' },
  { name:'Droit',        val:16, color:'#06B6D4' },
  { name:'Filière IA',   val:5,  color:'#EF4444' },
];
const maxVal = Math.max(...ranks.map(r=>r.val));
const rt = document.getElementById('rankTable');
ranks.forEach((r,i) => {
  rt.innerHTML += `
  <div class="rank-row">
    <div class="rank-num ${i<3?'top':''}">${i+1}</div>
    <div class="rank-name">${r.name}</div>
    <div class="rank-bar-wrap"><div class="rank-bar" style="width:${Math.round(r.val/maxVal*100)}%;background:${r.color};"></div></div>
    <div class="rank-val">${r.val}</div>
  </div>`;
});

// ── RADAR ──
new Chart(document.getElementById('radarChart'), {
  type: 'radar',
  data: {
    labels: ['Réussite','Paiements','Présence','Complétion','Satisfaction','Rétention'],
    datasets: [{
      label: 'Performance',
      data: [87, 75, 92, 68, 81, 96],
      backgroundColor: 'rgba(245,158,11,0.15)',
      borderColor: '#F59E0B',
      borderWidth: 2,
      pointBackgroundColor: '#F59E0B',
      pointRadius: 4,
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins: { legend:{ display:false } },
    scales: {
      r: {
        beginAtZero:true, max:100,
        grid:{ color:gridColor },
        ticks:{ color:tickColor, font:{family:FONT,size:10}, stepSize:25, callback:v=>v+'%' },
        pointLabels:{ color:tickColor, font:{family:FONT,size:11} }
      }
    }
  }
});
</script>

<!-- MODAL Nouveau Rapport -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Générer un Nouveau Rapport</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Type de Rapport</label>
        <select class="form-select">
          <option value="">Sélectionner...</option>
          <option>Rapport Mensuel</option>
          <option>Rapport Trimestriel</option>
          <option>Rapport Annuel</option>
          <option>Rapport Personnalisé</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Période</label>
        <select class="form-select">
          <option value="">Sélectionner...</option>
          <option>Cette année</option>
          <option>Ce semestre</option>
          <option>Ce mois</option>
          <option>Dernier mois</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Programmes</label>
        <select class="form-select" multiple>
          <option value="">Tous les programmes</option>
          <option>Gestion</option>
          <option>Médecine</option>
          <option>Informatique</option>
          <option>Économie</option>
          <option>Droit</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Format</label>
        <select class="form-select">
          <option value="pdf">PDF</option>
          <option value="excel">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Email de destination</label>
        <input class="form-input" type="email" placeholder="admin@taajcorp.com" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" placeholder="Instructions supplémentaires pour le rapport..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="btn-primary" onclick="generateReport()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Générer
      </button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(event) { if(event.target === document.getElementById('modalOverlay')) closeModal(); }

// Fonction pour générer un rapport PDF
function generateReport() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  // Ajouter un titre
  doc.setFontSize(20);
  doc.text('Rapport de Performance - TAAJ Corp', 105, 20, { align: 'center' });
  
  // Ajouter la date
  doc.setFontSize(12);
  doc.text(`Généré le: ${new Date().toLocaleDateString('fr-FR')}`, 105, 30, { align: 'center' });
  
  // Ajouter les KPIs
  doc.setFontSize(14);
  doc.text('Indicateurs Clés:', 20, 50);
  
  doc.setFontSize(11);
  const kpis = [
    ['Total Étudiants', document.querySelector('.kpi-value').textContent],
    ['Taux de Réussite', document.querySelectorAll('.kpi-value')[1].textContent],
    ['Revenus Totaux', document.querySelectorAll('.kpi-value')[2].textContent],
    ['Programmes Actifs', document.querySelectorAll('.kpi-value')[3].textContent]
  ];
  
  let yPos = 60;
  kpis.forEach(([label, value]) => {
    doc.text(`${label}: ${value}`, 20, yPos);
    yPos += 10;
  });
  
  // Ajouter un tableau de répartition
  doc.autoTable({
    head: ['Programme', 'Nombre d\'étudiants', 'Pourcentage'],
    body: [
      ['Informatique', '34', '27%'],
      ['Gestion', '28', '22%'],
      ['Médecine', '22', '18%'],
      ['Économie', '19', '15%'],
      ['Droit', '16', '13%'],
      ['Filière IA', '5', '4%']
    ],
    startY: yPos + 10,
    theme: 'grid'
  });
  
  // Sauvegarder le PDF
  doc.save('rapport_performance_taatj.pdf');
  
  alert('Rapport PDF généré avec succès !');
  closeModal();
}

// Fonction pour exporter les données en CSV
function exportData() {
  const data = [
    ['Programme', 'Étudiants', 'Taux de réussite', 'Revenus'],
    ['Informatique', '34', '85%', '2.5M FCFA'],
    ['Gestion', '28', '82%', '2.1M FCFA'],
    ['Médecine', '22', '90%', '3.3M FCFA'],
    ['Économie', '19', '78%', '1.8M FCFA'],
    ['Droit', '16', '85%', '1.9M FCFA'],
    ['Filière IA', '5', '92%', '0.8M FCFA']
  ];
  
  let csvContent = "data:text/csv;charset=utf-8,";
  data.forEach(row => {
    csvContent += row.join(',') + '\n';
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'export_stats_taatj.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  alert('Données exportées avec succès !');
}

// Fonction pour générer le rapport PDF principal
function generatePDFReport() {
  generateReport();
}
</script>
</body>
</html>
