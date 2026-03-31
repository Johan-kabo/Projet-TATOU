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

// Récupérer les statistiques depuis la base de données
try {
    // Total étudiants
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];

    // Étudiants par statut
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
    $activeStudents = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'pending'");
    $pendingStudents = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'inactive'");
    $inactiveStudents = $stmt->fetch()['total'];

    // Programmes actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM programs");
    $activePrograms = $stmt->fetch()['total'];

    // Inscriptions par statut
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
    $totalRegistrations = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'paid'");
    $paidRegistrations = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'pending'");
    $pendingRegistrations = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'unpaid'");
    $unpaidRegistrations = $stmt->fetch()['total'];

    // Revenus totaux (somme des montants payés)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM registrations WHERE payment_status = 'paid'");
    $totalRevenue = $stmt->fetch()['total'];

} catch (PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $totalStudents = 0;
    $activeStudents = 0;
    $pendingStudents = 0;
    $inactiveStudents = 0;
    $activePrograms = 0;
    $totalRegistrations = 0;
    $paidRegistrations = 0;
    $pendingRegistrations = 0;
    $unpaidRegistrations = 0;
    $totalRevenue = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Tableau de bord</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link rel="stylesheet" href="assets/css/modern.css" />
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --sidebar-bg: #0F1623;
    --sidebar-text: #94A3B8;
    --accent: #1B4FA0;
    --accent-hover: #0c326e;
    --page-bg: #F1F5F9;
    --card-bg: #FFFFFF;
    --text-primary: #0F172A;
    --text-muted: #64748B;
    --border: #E2E8F0;
    --green: #10B981;
    --red: #EF4444;
    --blue: #3B82F6;
    --purple: #8B5CF6;
    --font: 'Plus Jakarta Sans', sans-serif;
  }

  html, body {
    height: 100%;
    font-family: var(--font);
    background: var(--page-bg);
    color: var(--text-primary);
  }

  .layout { display: flex; min-height: 100vh; }

  /* ── SIDEBAR ── */
  .sidebar {
    width: 230px;
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 200;
  }

  .sidebar-logo {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
  }
  .logo-icon {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
  }
  .logo-text { font-size: 15px; font-weight: 700; color: #fff; letter-spacing: 0.2px; }
  .sidebar-ecole-logo { height: 50px; width: auto; margin-left: auto; border-radius: 6px; }

  .sidebar-nav { flex: 1; padding: 14px 10px; overflow-y: auto; }
  .nav-section-label {
    font-size: 9.5px; font-weight: 700; letter-spacing: 1.2px;
    color: rgba(148,163,184,0.45); text-transform: uppercase;
    padding: 10px 10px 4px;
  }

  .nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 12px; border-radius: 8px;
    cursor: pointer; font-size: 13px; font-weight: 500;
    color: var(--sidebar-text);
    transition: background 0.18s, color 0.18s;
    margin-bottom: 1px;
    text-decoration: none;
    border: none; background: transparent; width: 100%; text-align: left;
  }
  .nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
  .nav-item.active { background: var(--accent); color: #fff; }
  .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }

  .sidebar-bottom { padding: 10px; border-top: 1px solid rgba(255,255,255,0.07); }
  .nav-item.danger:hover { background: rgba(239,68,68,0.12); color: #FCA5A5; }

  /* ── MAIN ── */
  .main { margin-left: 230px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

  /* ── TOPBAR ── */
  .topbar {
    background: #fff; border-bottom: 1px solid var(--border);
    padding: 11px 26px;
    display: flex; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 100;
  }
  .search-wrap {
    flex: 1; max-width: 380px;
    display: flex; align-items: center; gap: 8px;
    background: var(--page-bg);
    border: 1px solid var(--border);
    border-radius: 10px; padding: 7px 14px;
  }
  .search-wrap input {
    border: none; background: transparent; outline: none;
    font-family: var(--font); font-size: 13px; color: var(--text-primary); width: 100%;
  }
  .search-wrap input::placeholder { color: var(--text-muted); }

  .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
  .notif-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--page-bg); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; position: relative; transition: background 0.15s;
  }
  .notif-btn:hover { background: var(--border); }
  .notif-dot {
    position: absolute; top: 7px; right: 7px;
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--red); border: 2px solid #fff;
  }
  .user-block { display: flex; align-items: center; gap: 10px; }
  .user-names .uname { font-size: 13px; font-weight: 600; color: var(--text-primary); }
  .user-names .urole { font-size: 11px; color: var(--text-muted); }
  .avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
  }

  /* ── CONTENT ── */
  .content { padding: 24px 26px; flex: 1; }

  .page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 22px;
  }
  .page-title { font-size: 22px; font-weight: 800; color: var(--text-primary); }
  .page-sub { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

  .btn-primary {
    display: flex; align-items: center; gap: 7px;
    background: var(--accent); color: #fff; border: none;
    border-radius: 10px; padding: 10px 18px;
    font-size: 13px; font-weight: 600; font-family: var(--font);
    cursor: pointer; transition: background 0.18s;
    white-space: nowrap;
  }
  .btn-primary:hover { background: var(--accent-hover); }

  /* ── KPI CARDS ── */
  .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }

  .kpi-card {
    background: var(--card-bg); border-radius: 14px; border: 1px solid var(--border);
    padding: 18px; transition: box-shadow 0.2s;
  }
  .kpi-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); }

  .kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
  .kpi-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; }
  .kpi-icon.blue   { background: #EFF6FF; }
  .kpi-icon.amber  { background: #FFFBEB; }
  .kpi-icon.green  { background: #ECFDF5; }
  .kpi-icon.purple { background: #F5F3FF; }
  .kpi-dots { color: var(--text-muted); font-size: 18px; cursor: pointer; line-height: 1; user-select: none; }

  .kpi-label { font-size: 12px; font-weight: 500; color: var(--text-muted); margin-bottom: 5px; }
  .kpi-value { font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
  .kpi-value.sm { font-size: 18px; }
  .kpi-trend { display: flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; margin-top: 6px; }
  .trend-up   { color: var(--green); }
  .trend-down { color: var(--red); }

  /* ── BOTTOM GRID ── */
  .bottom-grid { display: grid; grid-template-columns: 1fr 310px; gap: 16px; }

  .card {
    background: var(--card-bg); border-radius: 14px;
    border: 1px solid var(--border); padding: 20px;
  }
  .card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
  .card-title { font-size: 15px; font-weight: 700; color: var(--text-primary); }
  .card-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

  .filter-select {
    font-family: var(--font); font-size: 12px; color: var(--text-muted);
    border: 1px solid var(--border); border-radius: 8px;
    padding: 6px 10px; background: var(--page-bg); cursor: pointer; outline: none;
  }

  .chart-wrap { position: relative; height: 220px; }

  /* ── ACTIVITY LIST ── */
  .activity-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 0; border-bottom: 1px solid var(--border);
  }
  .activity-item:last-child { border-bottom: none; padding-bottom: 0; }

  .act-av {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
  }
  .act-info { flex: 1; min-width: 0; }
  .act-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
  .act-prog { font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 20px; display: inline-block; margin-top: 3px; }
  .act-date { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

  .prog-gestion { background: #EFF6FF; color: #1D4ED8; }
  .prog-medecine { background: #ECFDF5; color: #065F46; }
  .prog-info { background: #F5F3FF; color: #5B21B6; }
  .prog-eco { background: #FFFBEB; color: #92400E; }
  .prog-droit { background: #FFF1F2; color: #9F1239; }

  /* ── ANIMATIONS ── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .kpi-card { animation: fadeUp 0.4s ease both; }
  .kpi-card:nth-child(1) { animation-delay: 0.05s; }
  .kpi-card:nth-child(2) { animation-delay: 0.10s; }
  .kpi-card:nth-child(3) { animation-delay: 0.15s; }
  .kpi-card:nth-child(4) { animation-delay: 0.20s; }
  .card { animation: fadeUp 0.45s 0.25s ease both; }
</style>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">T</div>
      <span class="logo-text">TAAJ Corp</span>
      <img src="assets/images/logo IME.png" alt="IME Business and Engineering School" class="sidebar-ecole-logo" onerror="this.style.display='none';">
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Principal</div>
      <button class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        Tableau de bord
      </button>
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
      <a href="stats.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Statistiques
      </a>
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

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
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
          <div class="user-names">
            <div class="uname">Junior Atchonkeu</div>
            <div class="urole"><?php echo ucfirst($_SESSION['role']); ?></div>
          </div>
          <div class="avatar">JA</div>
        </div>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="content">
      <div class="page-header">
        <div>
          <div class="page-title">Tableau de bord</div>
          <div class="page-sub">Bienvenue sur la plateforme de gestion TAAJ Corp.</div>
        </div>
        <button class="btn-primary" onclick="generateReport()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Générer un rapport
        </button>
      </div>

      <!-- KPI CARDS -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon blue">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <span class="kpi-dots">···</span>
          </div>
          <div class="kpi-label">Total Étudiants</div>
          <div class="kpi-value"><?php echo number_format($totalStudents); ?></div>
          <div class="kpi-trend trend-up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <?php echo $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0; ?>% actifs
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon amber">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <span class="kpi-dots">···</span>
          </div>
          <div class="kpi-label">Total Inscriptions</div>
          <div class="kpi-value"><?php echo number_format($totalRegistrations); ?></div>
          <div class="kpi-trend trend-up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <?php echo $totalRegistrations > 0 ? round(($paidRegistrations / $totalRegistrations) * 100, 1) : 0; ?>% payées
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <span class="kpi-dots">···</span>
          </div>
          <div class="kpi-label">Programmes Actifs</div>
          <div class="kpi-value"><?php echo $activePrograms; ?></div>
          <div class="kpi-trend trend-up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <?php echo $activePrograms > 0 ? round(($totalStudents / $activePrograms), 1) : 0; ?> étudiants/programme
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <div class="kpi-icon purple">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <span class="kpi-dots">···</span>
          </div>
          <div class="kpi-label">Revenus Totaux</div>
          <div class="kpi-value sm"><?php echo number_format($totalRevenue, 0, ' ', ' '); ?> FCFA</div>
          <div class="kpi-trend trend-up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <?php echo $paidRegistrations > 0 ? number_format($totalRevenue / $paidRegistrations, 0, ' ', ' ') : 0; ?> FCFA/moy
          </div>
        </div>
      </div>

      <!-- BOTTOM -->
      <div class="bottom-grid">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Aperçu des Inscriptions</div>
              <div class="card-sub">Évolution sur les 7 derniers mois</div>
            </div>
            <select class="filter-select">
              <option>Cette année</option>
              <option>2025</option>
              <option>2024</option>
            </select>
          </div>
          <div class="chart-wrap">
            <canvas id="inscChart"></canvas>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">Activités Récentes</div>
          </div>
          <div id="activity-list"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // ── ACTIVITY DATA ──
  const activities = [
    { name: "Janot NKENG",  prog: "Gestion",      cls: "prog-gestion",  date: "17 mars",  ini: "JN", c: "#3B82F6" },
    { name: "Alex TAMO",    prog: "Médecine",     cls: "prog-medecine", date: "17 mars",  ini: "AT", c: "#10B981" },
    { name: "Marie ONANA",  prog: "Informatique", cls: "prog-info",     date: "16 mars",  ini: "MO", c: "#8B5CF6" },
    { name: "Paul MBARGA",  prog: "Économie",     cls: "prog-eco",      date: "16 mars",  ini: "PM", c: "#F59E0B" },
    { name: "Claire FOPA",  prog: "Droit",        cls: "prog-droit",    date: "15 mars",  ini: "CF", c: "#EF4444" },
  ];

  const list = document.getElementById('activity-list');
  activities.forEach(a => {
    list.innerHTML += `
      <div class="activity-item">
        <div class="act-av" style="background:${a.c}18;color:${a.c};">${a.ini}</div>
        <div class="act-info">
          <div class="act-name">${a.name}</div>
          <span class="act-prog ${a.cls}">${a.prog}</span>
        </div>
        <div class="act-date">${a.date}</div>
      </div>`;
  });

  // ── CHART ──
  const ctx = document.getElementById('inscChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Sep', 'Oct', 'Nov', 'Déc', 'Jan', 'Fév', 'Mar'],
      datasets: [
        {
          label: 'Inscriptions',
          data: [12, 19, 15, 28, 22, 35, 18],
          borderColor: '#F59E0B',
          backgroundColor: 'rgba(245,158,11,0.08)',
          borderWidth: 2.5, fill: true, tension: 0.4,
          pointBackgroundColor: '#F59E0B', pointRadius: 4, pointHoverRadius: 6,
        },
        {
          label: 'Validés',
          data: [8, 14, 11, 20, 17, 28, 14],
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59,130,246,0.06)',
          borderWidth: 2.5, fill: true, tension: 0.4,
          pointBackgroundColor: '#3B82F6', pointRadius: 4, pointHoverRadius: 6,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { font: { family: 'Plus Jakarta Sans', size: 12 }, boxWidth: 10, padding: 16 }
        },
        tooltip: {
          backgroundColor: '#0F172A',
          titleFont: { family: 'Plus Jakarta Sans', size: 12 },
          bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
          padding: 10, cornerRadius: 8,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#F1F5F9' },
          ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#94A3B8' }
        },
        x: {
          grid: { display: false },
          ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#94A3B8' }
        }
      }
    }
  });
</script>

<script>
// Synchronisation en temps réel avec les APIs
let studentsData = [];
let registrationsData = [];

// Fonction pour charger les données depuis les APIs
async function loadRealTimeData() {
  try {
    // Charger les étudiants
    const studentsResponse = await fetch('api_crud_students.php');
    const studentsText = await studentsResponse.text();
    
    if (studentsText) {
      const studentsResult = JSON.parse(studentsText);
      if (studentsResult.success) {
        studentsData = studentsResult.students || [];
        updateStudentStats();
      }
    }
    
    // Charger les inscriptions
    const registrationsResponse = await fetch('api_crud_registrations.php');
    const registrationsText = await registrationsResponse.text();
    
    if (registrationsText) {
      const registrationsResult = JSON.parse(registrationsText);
      if (registrationsResult.success) {
        registrationsData = registrationsResult.registrations || [];
        updateRegistrationStats();
      }
    }
    
  } catch (error) {
    console.error('Erreur de chargement des données:', error);
  }
}

// Mettre à jour les statistiques des étudiants
function updateStudentStats() {
  const activeCount = studentsData.filter(s => s.status === 'active').length;
  const totalCount = studentsData.length;
  
  // Mettre à jour le KPI Total Étudiants
  const totalStudentsEl = document.querySelector('.kpi-card:nth-child(1) .kpi-value');
  if (totalStudentsEl) {
    totalStudentsEl.textContent = totalCount.toLocaleString();
  }
  
  // Mettre à jour le pourcentage actif
  const activeTrendEl = document.querySelector('.kpi-card:nth-child(1) .kpi-trend');
  if (activeTrendEl && totalCount > 0) {
    const percentage = Math.round((activeCount / totalCount) * 100);
    activeTrendEl.innerHTML = `
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
      ${percentage}% actifs
    `;
  }
}

// Mettre à jour les statistiques des inscriptions
function updateRegistrationStats() {
  const paidCount = registrationsData.filter(r => r.payment_status === 'paid').length;
  const totalCount = registrationsData.length;
  
  // Mettre à jour le KPI Total Inscriptions
  const totalRegistrationsEl = document.querySelector('.kpi-card:nth-child(2) .kpi-value');
  if (totalRegistrationsEl) {
    totalRegistrationsEl.textContent = totalCount.toLocaleString();
  }
  
  // Mettre à jour le pourcentage payé
  const paidTrendEl = document.querySelector('.kpi-card:nth-child(2) .kpi-trend');
  if (paidTrendEl && totalCount > 0) {
    const percentage = Math.round((paidCount / totalCount) * 100);
    paidTrendEl.innerHTML = `
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
      ${percentage}% payées
    `;
  }
  
  // Mettre à jour le graphique Chart.js avec les vraies données
  updateChartWithRealData();
}

// Mettre à jour le graphique Chart.js avec les vraies données
function updateChartWithRealData() {
  if (window.enrollmentChart && registrationsData.length > 0) {
    // Grouper les inscriptions par mois
    const monthlyData = {};
    const currentYear = new Date().getFullYear();
    
    // Initialiser les 6 derniers mois
    for (let i = 5; i >= 0; i--) {
      const date = new Date();
      date.setMonth(date.getMonth() - i);
      const monthKey = date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
      monthlyData[monthKey] = { total: 0, paid: 0 };
    }
    
    // Remplir avec les vraies données
    registrationsData.forEach(registration => {
      const date = new Date(registration.registration_date || registration.date || new Date());
      const monthKey = date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
      
      if (monthlyData[monthKey]) {
        monthlyData[monthKey].total++;
        if (registration.payment_status === 'paid') {
          monthlyData[monthKey].paid++;
        }
      }
    });
    
    // Mettre à jour le graphique
    const labels = Object.keys(monthlyData);
    const totalData = labels.map(label => monthlyData[label].total);
    const paidData = labels.map(label => monthlyData[label].paid);
    
    window.enrollmentChart.data.labels = labels;
    window.enrollmentChart.data.datasets[0].data = totalData;
    window.enrollmentChart.data.datasets[1].data = paidData;
    window.enrollmentChart.update();
  }
}

// Fonction pour rafraîchir manuellement
function refreshDashboard() {
  loadRealTimeData();
}

// Charger les données au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  // Charger les données initiales
  setTimeout(loadRealTimeData, 1000);
  
  // Rafraîchir automatiquement toutes les 30 secondes
  setInterval(loadRealTimeData, 30000);
  
  // Ajouter un bouton de rafraîchissement si nécessaire
  const refreshBtn = document.querySelector('.btn-primary');
  if (refreshBtn && refreshBtn.textContent.includes('rapport')) {
    refreshBtn.addEventListener('click', function(e) {
      e.preventDefault();
      refreshDashboard();
      // Générer le rapport (fonction existante)
      generateReport();
    });
  }
});

// Fonction pour générer un rapport améliorée
function generateReport() {
  // Créer une modal pour choisir le type de rapport
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
    z-index: 1000;
  `;
  
  modal.innerHTML = `
    <div style="background: white; border-radius: 16px; padding: 32px; max-width: 500px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <h2 style="margin: 0 0 24px 0; color: #333; font-size: 20px; font-weight: 700;">Générer un Rapport</h2>
      
      <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Type de rapport:</label>
        <select id="reportType" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;">
          <option value="summary">Résumé Général</option>
          <option value="students">Liste des Étudiants</option>
          <option value="registrations">Liste des Inscriptions</option>
          <option value="payments">Rapport des Paiements</option>
          <option value="programs">Rapport des Programmes</option>
        </select>
      </div>
      
      <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Format:</label>
        <select id="reportFormat" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;">
          <option value="pdf">PDF (Impression)</option>
          <option value="html">HTML (Affichage)</option>
          <option value="csv">CSV (Excel)</option>
          <option value="json">JSON (Données)</option>
        </select>
      </div>
      
      <div style="display: flex; gap: 12px; justify-content: flex-end;">
        <button onclick="this.closest('div[style*=position]').remove()" style="padding: 10px 20px; border: 1px solid #ddd; background: #f5f5f5; border-radius: 8px; cursor: pointer; font-size: 14px;">Annuler</button>
        <button onclick="downloadReport()" style="padding: 10px 20px; background: #F59E0B; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600;">Télécharger</button>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Fermer la modal en cliquant à l'extérieur
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.remove();
    }
  });
}

function downloadReport() {
  const reportType = document.getElementById('reportType').value;
  const reportFormat = document.getElementById('reportFormat').value;
  
  // Afficher un indicateur de chargement
  const btn = event.target;
  const originalText = btn.textContent;
  btn.textContent = 'Génération en cours...';
  btn.disabled = true;
  
  if (reportFormat === 'pdf') {
    // Utiliser le nouveau système jsPDF avec design jaune
    generatePDFReport(reportType);
  } else if (reportFormat === 'html') {
    window.open(`generate_report_simple.php?type=${reportType}&format=html`, '_blank');
    showNotification('✅ Rapport HTML ouvert dans un nouvel onglet', 'success');
  } else if (reportFormat === 'csv') {
    // Téléchargement direct du CSV
    fetch(`generate_report_simple.php?type=${reportType}&format=json`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          downloadCSVFile(data.data, reportType);
        } else {
          showNotification('❌ Erreur: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showNotification('❌ Erreur: ' + error.message, 'error');
      })
      .finally(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        document.querySelector('div[style*=position]').remove();
      });
    return;
  } else {
    fetch(`generate_report_simple.php?type=${reportType}&format=json`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const jsonData = JSON.stringify(data, null, 2);
          const blob = new Blob([jsonData], { type: 'application/json' });
          const downloadUrl = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = downloadUrl;
          a.download = `${data.title}_${new Date().toISOString().slice(0,10)}.json`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(downloadUrl);
          
          showNotification('✅ Rapport JSON téléchargé avec succès', 'success');
        } else {
          showNotification('❌ Erreur: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showNotification('❌ Erreur: ' + error.message, 'error');
      })
      .finally(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        document.querySelector('div[style*=position]').remove();
      });
    return;
  }
  
  // Fermer la modal pour HTML/CSV
  btn.textContent = originalText;
  btn.disabled = false;
  document.querySelector('div[style*=position]').remove();
}

// Fonction pour générer des PDF avec design jaune (template)
function generatePDFReport(reportType) {
  showNotification('📄 Génération du PDF en cours...', 'success');
  
  // Récupérer les données selon le type de rapport
  let apiUrl = '';
  let reportTitle = '';
  
  switch(reportType) {
    case 'students':
      apiUrl = 'api_crud_students.php?action=get';
      reportTitle = 'RAPPORT DES ÉTUDIANTS';
      break;
    case 'programs':
      apiUrl = 'generate_report_simple.php?type=programs&format=json';
      reportTitle = 'RAPPORT DES PROGRAMMES';
      break;
    case 'registrations':
      apiUrl = 'generate_report_simple.php?type=registrations&format=json';
      reportTitle = 'RAPPORT DES INSCRIPTIONS';
      break;
    case 'payments':
      apiUrl = 'generate_report_simple.php?type=payments&format=json';
      reportTitle = 'RAPPORT DES PAIEMENTS';
      break;
    default:
      apiUrl = 'generate_report_simple.php?type=summary&format=json';
      reportTitle = 'RAPPORT GÉNÉRAL';
  }
  
  fetch(apiUrl)
    .then(response => response.json())
    .then(data => {
      if (data.success || data.students) {
        const reportData = data.students || data.data || data;
        createPDFWithTemplate(reportTitle, reportData, reportType);
      } else {
        showNotification('❌ Erreur: ' + data.message, 'error');
      }
    })
    .catch(error => {
      showNotification('❌ Erreur: ' + error.message, 'error');
    });
}

// Fonction template pour créer des PDF avec design jaune
function createPDFWithTemplate(title, data, reportType) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  // Variables pour le design
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  let yPosition = 20;
  
  // Header avec design jaune (template)
  doc.setFillColor(255, 193, 7); // Jaune
  doc.rect(0, 0, pageWidth, 60, 'F');
  
  // Titre
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(24);
  doc.setFont(undefined, 'bold');
  doc.text(title, pageWidth / 2, 25, { align: 'center' });
  
  // Sous-titre
  doc.setFontSize(12);
  doc.setFont(undefined, 'normal');
  doc.text('TAAJ Corp - Système de Gestion Académique', pageWidth / 2, 35, { align: 'center' });
  
  // Date
  doc.text('Généré le: ' + new Date().toLocaleDateString('fr-FR'), pageWidth / 2, 45, { align: 'center' });
  
  yPosition = 80;
  
  // Contenu spécifique selon le type de rapport
  if (reportType === 'summary') {
    // Rapport résumé avec statistiques
    doc.setFillColor(255, 235, 156); // Jaune clair
    doc.rect(15, yPosition - 10, pageWidth - 30, 60, 'F');
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Statistiques Générales:', 20, yPosition);
    
    yPosition += 15;
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    
    Object.keys(data).forEach(key => {
      if (data[key] !== null) {
        let label = '';
        switch(key) {
          case 'total_students': label = 'Total Étudiants'; break;
          case 'total_programs': label = 'Total Programmes'; break;
          case 'total_registrations': label = 'Total Inscriptions'; break;
          case 'paid_registrations': label = 'Inscriptions Payées'; break;
          case 'total_revenue': label = 'Revenus Totaux (FCFA)'; break;
          default: label = key.replace(/_/g, ' ').toUpperCase();
        }
        
        const value = key.includes('revenue') ? 
          Number(data[key]).toLocaleString('fr-FR') + ' FCFA' : 
          Number(data[key]).toLocaleString('fr-FR');
        
        doc.text(`${label}: ${value}`, 20, yPosition);
        yPosition += 12;
      }
    });
    
  } else {
    // Tableaux pour les autres rapports
    let headers = [];
    let dataRows = [];
    
    switch(reportType) {
      case 'students':
        headers = ['Nom', 'Email', 'Programme', 'Statut'];
        dataRows = data.map(student => [
          `${student.first_name || ''} ${student.last_name || ''}`.trim(),
          student.email || 'N/A',
          student.program_name || 'N/A',
          student.status || 'N/A'
        ]);
        break;
        
      case 'programs':
        headers = ['Programme', 'Code', 'Niveau', 'Durée', 'Capacité', 'Prix (FCFA)'];
        dataRows = data.map(program => [
          program.name || 'N/A',
          program.code || 'N/A',
          program.level || 'N/A',
          program.duration || 'N/A',
          program.capacity || 'N/A',
          Number(program.price || 0).toLocaleString('fr-FR')
        ]);
        break;
        
      case 'registrations':
        headers = ['Étudiant', 'Programme', 'Date', 'Statut'];
        dataRows = data.map(reg => [
          reg.student_name || 'N/A',
          reg.program_name || 'N/A',
          reg.registration_date ? new Date(reg.registration_date).toLocaleDateString('fr-FR') : 'N/A',
          reg.status || 'N/A'
        ]);
        break;
        
      case 'payments':
        headers = ['Référence', 'Étudiant', 'Programme', 'Montant (FCFA)', 'Date'];
        dataRows = data.map(payment => [
          payment.reference || 'N/A',
          payment.student_name || 'N/A',
          payment.program_name || 'N/A',
          Number(payment.amount || 0).toLocaleString('fr-FR'),
          payment.payment_date ? new Date(payment.payment_date).toLocaleDateString('fr-FR') : 'N/A'
        ]);
        break;
    }
    
    // En-têtes du tableau
    doc.setFillColor(255, 193, 7); // Jaune
    doc.rect(15, yPosition - 5, pageWidth - 30, 10, 'F');
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(10);
    doc.setFont(undefined, 'bold');
    
    headers.forEach((header, index) => {
      const x = 20 + (index * 40);
      doc.text(header.substring(0, 15), x, yPosition);
    });
    
    yPosition += 15;
    
    // Données du tableau
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    
    dataRows.forEach((row, index) => {
      // Vérifier si on a besoin d'une nouvelle page
      if (yPosition > pageHeight - 30) {
        doc.addPage();
        yPosition = 20;
        
        // Répéter l'en-tête sur la nouvelle page
        doc.setFillColor(255, 193, 7);
        doc.rect(15, yPosition - 5, pageWidth - 30, 10, 'F');
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        
        headers.forEach((header, i) => {
          const x = 20 + (i * 40);
          doc.text(header.substring(0, 15), x, yPosition);
        });
        
        yPosition += 15;
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
      }
      
      // Ligne de séparation
      doc.setDrawColor(200, 200, 200);
      doc.line(15, yPosition - 2, pageWidth - 15, yPosition - 2);
      
      // Alternance de couleurs
      if (index % 2 === 0) {
        doc.setFillColor(255, 248, 220); // Jaune très clair
        doc.rect(15, yPosition - 2, pageWidth - 30, 8, 'F');
      }
      
      // Données de la ligne
      row.forEach((cellData, cellIndex) => {
        const x = 20 + (cellIndex * 40);
        const displayData = String(cellData).substring(0, 20);
        
        // Couleur pour les statuts
        if (cellIndex === 3 && (reportType === 'students' || reportType === 'registrations')) {
          if (cellData === 'active' || cellData === 'paid') {
            doc.setTextColor(0, 128, 0);
          } else if (cellData === 'pending') {
            doc.setTextColor(255, 140, 0);
          } else {
            doc.setTextColor(255, 0, 0);
          }
          doc.text(displayData, x, yPosition + 5);
          doc.setTextColor(0, 0, 0);
        } else {
          doc.text(displayData, x, yPosition + 5);
        }
      });
      
      yPosition += 12;
    });
  }
  
  // Footer (template)
  const footerY = pageHeight - 20;
  doc.setFillColor(255, 193, 7); // Jaune
  doc.rect(0, footerY - 10, pageWidth, 20, 'F');
  
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(8);
  doc.setFont(undefined, 'normal');
  doc.text('TAAJ Corp - Système de Gestion Académique', pageWidth / 2, footerY, { align: 'center' });
  doc.text('Page ' + doc.internal.getNumberOfPages(), pageWidth / 2, footerY + 8, { align: 'center' });
  
  // Télécharger le PDF
  const fileName = `${title.toLowerCase().replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.pdf`;
  doc.save(fileName);
  
  showNotification('✅ PDF généré et téléchargé avec succès!', 'success');
  
  // Fermer la modal
  const modal = document.querySelector('div[style*=position]');
  if (modal) modal.remove();
}

// Fonction pour télécharger un fichier CSV directement
function downloadCSVFile(data, reportType) {
  // Définir les en-têtes selon le type de rapport
  let headers = '';
  let filename = '';
  
  switch(reportType) {
    case 'students':
      headers = 'Nom,Email,Téléphone,Programme,Niveau,Date de naissance,Statut,Carte étudiant';
      filename = 'etudiants';
      break;
    case 'programs':
      headers = 'Programme,Code,Niveau,Durée,Capacité,Prix,Description,Statut';
      filename = 'programmes';
      break;
    case 'registrations':
      headers = 'Référence,Étudiant,Programme,Date inscription,Montant,Statut paiement,Notes';
      filename = 'inscriptions';
      break;
    case 'payments':
      headers = 'Référence,Étudiant,Programme,Montant,Date paiement,Statut';
      filename = 'paiements';
      break;
    default:
      headers = 'Type,Valeur';
      filename = 'rapport';
  }
  
  // Créer le contenu CSV
  let csvContent = '\uFEFF'; // BOM pour UTF-8
  csvContent += headers + '\n';
  
  if (reportType === 'summary') {
    // Cas spécial pour le rapport résumé
    Object.keys(data).forEach(key => {
      if (data[key] !== null) {
        let label = '';
        switch(key) {
          case 'total_students': label = 'Total Étudiants'; break;
          case 'total_programs': label = 'Total Programmes'; break;
          case 'total_registrations': label = 'Total Inscriptions'; break;
          case 'paid_registrations': label = 'Inscriptions Payées'; break;
          case 'total_revenue': label = 'Revenus Totaux (FCFA)'; break;
          default: label = key.replace(/_/g, ' ').toUpperCase();
        }
        
        const value = key.includes('revenue') ? 
          Number(data[key]).toLocaleString('fr-FR') + ' FCFA' : 
          Number(data[key]).toLocaleString('fr-FR');
        
        csvContent += `"${label}","${value}"\n`;
      }
    });
  } else {
    // Pour les autres types de rapports
    data.forEach(item => {
      let row = [];
      
      switch(reportType) {
        case 'students':
          row = [
            `"${item.first_name || ''} ${item.last_name || ''}"`,
            `"${item.email || ''}"`,
            `"${item.phone || ''}"`,
            `"${item.program_name || ''}"`,
            `"${item.level || ''}"`,
            `"${item.date_of_birth || ''}"`,
            `"${item.status || ''}"`,
            `"${item.student_id_card || ''}"`
          ];
          break;
          
        case 'programs':
          row = [
            `"${item.name || ''}"`,
            `"${item.code || ''}"`,
            `"${item.level || ''}"`,
            `"${item.duration || ''}"`,
            `"${item.capacity || ''}"`,
            `"${item.price || ''}"`,
            `"${(item.description || '').replace(/"/g, '""')}"`,
            `"${item.active ? 'Actif' : 'Inactif'}"`
          ];
          break;
          
        case 'registrations':
          row = [
            `"${item.reference || ''}"`,
            `"${item.student_name || ''}"`,
            `"${item.program_name || ''}"`,
            `"${item.registration_date ? new Date(item.registration_date).toLocaleDateString('fr-FR') : ''}"`,
            `"${item.amount || ''}"`,
            `"${item.payment_status || ''}"`,
            `"${(item.notes || '').replace(/"/g, '""')}"`
          ];
          break;
          
        case 'payments':
          row = [
            `"${item.reference || ''}"`,
            `"${item.student_name || ''}"`,
            `"${item.program_name || ''}"`,
            `"${item.amount || ''}"`,
            `"${item.payment_date ? new Date(item.payment_date).toLocaleDateString('fr-FR') : ''}"`,
            `"${item.payment_status || ''}"`
          ];
          break;
      }
      
      csvContent += row.join(',') + '\n';
    });
  }
  
  // Créer le Blob et télécharger
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', `${filename}_${new Date().toISOString().slice(0, 10)}.csv`);
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  showNotification('✅ Export CSV téléchargé avec succès!', 'success');
}
</script>
</body>
</html>
