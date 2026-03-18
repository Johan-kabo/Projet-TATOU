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

// Récupérer les statistiques des étudiants
try {
    // Total étudiants
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];

    // Étudiants actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
    $activeStudents = $stmt->fetch()['total'];

    // Étudiants en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'pending'");
    $pendingStudents = $stmt->fetch()['total'];

    // Étudiants inactifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'inactive'");
    $inactiveStudents = $stmt->fetch()['total'];

    // Récupérer la liste des étudiants pour la table
    $stmt = $pdo->query("
        SELECT s.*, p.name as program_name
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.id
        ORDER BY s.created_at DESC
        LIMIT 12
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $totalStudents = 124;
    $activeStudents = 108;
    $pendingStudents = 11;
    $inactiveStudents = 5;
    $students = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Gestion des Étudiants</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="assets/css/modern.css" />
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --sidebar-bg: #0F1623;
    --sidebar-text: #94A3B8;
    --accent: #F59E0B;
    --accent-hover: #D97706;
    --page-bg: #F1F5F9;
    --card-bg: #FFFFFF;
    --text-primary: #0F172A;
    --text-muted: #64748B;
    --border: #E2E8F0;
    --green: #10B981;
    --red: #EF4444;
    --blue: #3B82F6;
    --font: 'Plus Jakarta Sans', sans-serif;
  }

  html, body { height: 100%; font-family: var(--font); background: var(--page-bg); color: var(--text-primary); }
  .layout { display: flex; min-height: 100vh; }

  /* ── SIDEBAR ── */
  .sidebar {
    width: 230px; background: var(--sidebar-bg);
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 200;
  }
  .sidebar-logo {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
  }
  .logo-icon {
    width: 36px; height: 36px; background: var(--accent); border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
  }
  .logo-text { font-size: 15px; font-weight: 700; color: #fff; }
  .sidebar-nav { flex: 1; padding: 14px 10px; overflow-y: auto; }
  .nav-section-label {
    font-size: 9.5px; font-weight: 700; letter-spacing: 1.2px;
    color: rgba(148,163,184,0.45); text-transform: uppercase;
    padding: 10px 10px 4px;
  }
  .nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 12px; border-radius: 8px; cursor: pointer;
    font-size: 13px; font-weight: 500; color: var(--sidebar-text);
    transition: background 0.18s, color 0.18s; margin-bottom: 1px;
    border: none; background: transparent; width: 100%; text-align: left; font-family: var(--font);
    text-decoration: none;
  }
  .nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
  .nav-item.active { background: var(--accent); color: #fff; }
  .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }
  .sidebar-bottom { padding: 10px; border-top: 1px solid rgba(255,255,255,0.07); }
  .nav-item.danger:hover { background: rgba(239,68,68,0.12); color: #FCA5A5; }

  /* ── MAIN ── */
  .main { margin-left: 230px; flex: 1; display: flex; flex-direction: column; }

  /* ── TOPBAR ── */
  .topbar {
    background: #fff; border-bottom: 1px solid var(--border);
    padding: 11px 26px; display: flex; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 100;
  }
  .search-wrap {
    flex: 1; max-width: 380px; display: flex; align-items: center; gap: 8px;
    background: var(--page-bg); border: 1px solid var(--border);
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
    cursor: pointer; position: relative;
  }
  .notif-dot { position: absolute; top: 7px; right: 7px; width: 7px; height: 7px; border-radius: 50%; background: var(--red); border: 2px solid #fff; }
  .user-block { display: flex; align-items: center; gap: 10px; }
  .user-names .uname { font-size: 13px; font-weight: 600; color: var(--text-primary); }
  .user-names .urole { font-size: 11px; color: var(--text-muted); }
  .avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #667EEA, #764BA2);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
  }

  /* ── CONTENT ── */
  .content { padding: 24px 26px; flex: 1; }
  .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 22px; }
  .page-title { font-size: 22px; font-weight: 800; color: var(--text-primary); }
  .page-sub { font-size: 13px; color: var(--text-muted); margin-top: 3px; }
  .btn-primary {
    display: flex; align-items: center; gap: 7px;
    background: var(--accent); color: #fff; border: none;
    border-radius: 10px; padding: 10px 18px;
    font-size: 13px; font-weight: 600; font-family: var(--font);
    cursor: pointer; transition: background 0.18s; white-space: nowrap;
  }
  .btn-primary:hover { background: var(--accent-hover); }

  /* ── TABLE CARD ── */
  .table-card {
    background: var(--card-bg); border-radius: 14px;
    border: 1px solid var(--border); overflow: hidden;
    animation: fadeUp 0.4s ease both;
  }
  @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

  .table-toolbar {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
  }
  .table-search {
    display: flex; align-items: center; gap: 8px;
    background: var(--page-bg); border: 1px solid var(--border);
    border-radius: 9px; padding: 8px 14px; flex: 1; max-width: 340px;
  }
  .table-search input {
    border: none; background: transparent; outline: none;
    font-family: var(--font); font-size: 13px; color: var(--text-primary); width: 100%;
  }
  .table-search input::placeholder { color: var(--text-muted); }

  .toolbar-actions { margin-left: auto; display: flex; gap: 10px; align-items: center; }

  .btn-outline {
    display: flex; align-items: center; gap: 6px;
    border: 1px solid var(--border); background: #fff;
    border-radius: 9px; padding: 8px 14px;
    font-size: 13px; font-weight: 500; font-family: var(--font);
    color: var(--text-primary); cursor: pointer; transition: background 0.15s;
  }
  .btn-outline:hover { background: var(--page-bg); }

  .btn-pdf {
    display: flex; align-items: center; gap: 6px;
    border: 1.5px solid var(--accent); background: #fff;
    border-radius: 9px; padding: 8px 14px;
    font-size: 13px; font-weight: 600; font-family: var(--font);
    color: var(--accent); cursor: pointer; transition: background 0.15s;
  }
  .btn-pdf:hover { background: #FFFBEB; }

  /* ── TABLE ── */
  table { width: 100%; border-collapse: collapse; }
  thead tr { border-bottom: 1px solid var(--border); }
  thead th {
    padding: 11px 20px; text-align: left;
    font-size: 11px; font-weight: 700; letter-spacing: 0.7px;
    color: var(--text-muted); text-transform: uppercase; white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.15s; cursor: pointer;
  }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #FAFBFC; }
  td { padding: 14px 20px; font-size: 13.5px; vertical-align: middle; }

  .student-cell { display: flex; align-items: center; gap: 12px; }
  .stu-avatar {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; overflow: hidden;
  }
  .stu-name { font-size: 14px; font-weight: 600; color: var(--text-primary); }
  .stu-email { font-size: 12px; color: var(--text-muted); margin-top: 1px; }

  .level-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 26px; border-radius: 7px;
    font-size: 11.5px; font-weight: 700;
    background: #EFF6FF; color: #1D4ED8;
  }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
  }
  .status-active   { background: #ECFDF5; color: #065F46; }
  .status-inactive { background: #FEF2F2; color: #991B1B; }
  .status-pending  { background: #FFFBEB; color: #92400E; }
  .status-dot { width: 6px; height: 6px; border-radius: 50%; }
  .dot-active   { background: #10B981; }
  .dot-inactive { background: #EF4444; }
  .dot-pending  { background: #F59E0B; }

  .prog-tag {
    font-size: 13px; font-weight: 500; color: var(--text-primary);
  }

  .date-cell { font-size: 13px; color: var(--text-muted); }

  /* ── ACTION BUTTONS ── */
  .actions-cell { display: flex; align-items: center; gap: 6px; }
  .action-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
    background: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s, border-color 0.15s; color: var(--text-muted);
  }
  .action-btn:hover { background: var(--page-bg); }
  .action-btn.view:hover  { border-color: var(--blue); color: var(--blue); }
  .action-btn.edit:hover  { border-color: var(--accent); color: var(--accent); }
  .action-btn.delete:hover { border-color: var(--red); color: var(--red); }

  /* ── PAGINATION ── */
  .pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-top: 1px solid var(--border);
    font-size: 13px; color: var(--text-muted);
  }
  .page-btns { display: flex; gap: 4px; }
  .page-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--border); background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 500; cursor: pointer;
    font-family: var(--font); color: var(--text-primary);
    transition: background 0.15s;
  }
  .page-btn:hover { background: var(--page-bg); }
  .page-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

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
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .form-label { font-size: 12.5px; font-weight: 600; color: var(--text-primary); }
  .form-input, .form-select {
    font-family: var(--font); font-size: 13px; color: var(--text-primary);
    border: 1px solid var(--border); border-radius: 9px; padding: 9px 13px;
    outline: none; transition: border-color 0.15s; background: #fff;
    width: 100%;
  }
  .form-input:focus, .form-select:focus { border-color: var(--accent); }
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

  /* ── FILTER PANEL ── */
  .filter-panel {
    display: none; background: #fff;
    border: 1px solid var(--border); border-radius: 12px;
    padding: 16px 20px; margin-bottom: 14px;
    grid-template-columns: repeat(3, 1fr); gap: 14px;
  }
  .filter-panel.open { display: grid; }

  /* ── STATS ROW ── */
  .stats-row { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
  .stat-chip {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 16px;
    display: flex; align-items: center; gap: 10px;
    animation: fadeUp 0.35s ease both;
  }
  .stat-chip:nth-child(1) { animation-delay: 0.05s; }
  .stat-chip:nth-child(2) { animation-delay: 0.10s; }
  .stat-chip:nth-child(3) { animation-delay: 0.15s; }
  .stat-chip:nth-child(4) { animation-delay: 0.20s; }
  .chip-val { font-size: 20px; font-weight: 800; color: var(--text-primary); }
  .chip-lbl { font-size: 12px; color: var(--text-muted); font-weight: 500; }
  .chip-dot { width: 10px; height: 10px; border-radius: 50%; }
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
      <button class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Étudiants
      </button>
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
            <div class="uname">Johan Kabo</div>
            <div class="urole">Administrateur</div>
          </div>
          <div class="avatar">JK</div>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="page-header">
        <div>
          <div class="page-title">Gestion des Étudiants</div>
          <div class="page-sub">Gérez les dossiers et les inscriptions de vos étudiants.</div>
        </div>
        <div style="display: flex; gap: 10px;">
          <button class="btn-outline" onclick="exportStudents()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter
          </button>
          <button class="btn-outline" onclick="generateStudentsPDF()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            PDF
          </button>
          <button class="btn-primary" onclick="openModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouvel Étudiant
          </button>
        </div>
      </div>

      <!-- STATS CHIPS -->
      <div class="stats-row">
        <div class="stat-chip">
          <div class="chip-dot" style="background:#3B82F6;"></div>
          <div><div class="chip-val"><?php echo $totalStudents; ?></div><div class="chip-lbl">Total étudiants</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#10B981;"></div>
          <div><div class="chip-val"><?php echo $activeStudents; ?></div><div class="chip-lbl">Actifs</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#F59E0B;"></div>
          <div><div class="chip-val"><?php echo $pendingStudents; ?></div><div class="chip-lbl">En attente</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#EF4444;"></div>
          <div><div class="chip-val"><?php echo $inactiveStudents; ?></div><div class="chip-lbl">Inactifs</div></div>
        </div>
      </div>

      <!-- FILTER PANEL -->
      <div class="filter-panel" id="filterPanel">
        <div>
          <div class="form-label" style="margin-bottom:6px;">Programme</div>
          <select class="form-select">
            <option value="">Tous les programmes</option>
            <option>Gestion</option>
            <option>Médecine</option>
            <option>Informatique</option>
            <option>Économie</option>
            <option>Droit</option>
          </select>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:6px;">Niveau</div>
          <select class="form-select">
            <option value="">Tous les niveaux</option>
            <option>L1</option><option>L2</option><option>L3</option>
            <option>M1</option><option>M2</option>
          </select>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:6px;">Statut</div>
          <select class="form-select">
            <option value="">Tous les statuts</option>
            <option>Actif</option>
            <option>Inactif</option>
            <option>En attente</option>
          </select>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-search">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input id="searchInput" placeholder="Rechercher par nom, email..." oninput="filterTable()" />
          </div>
          <div class="toolbar-actions">
            <button class="btn-outline" onclick="toggleFilter()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
              Filtres
            </button>
            <button class="btn-pdf">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Générer PDF
            </button>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>Étudiant</th>
              <th>Programme</th>
              <th>Niveau</th>
              <th>Statut</th>
              <th>Date d'inscription</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="studentTableBody">
            <?php foreach ($students as $student): ?>
            <tr>
              <td>
                <div class="student-cell">
                  <div class="stu-avatar" style="background:<?php echo $student['color'] ?? '#3B82F6'; ?>18;color:<?php echo $student['color'] ?? '#3B82F6'; ?>;">
                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                  </div>
                  <div>
                    <div class="stu-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    <div class="stu-email"><?php echo htmlspecialchars($student['email']); ?></div>
                  </div>
                </div>
              </td>
              <td><?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($student['level'] ?? 'N/A'); ?></td>
              <td><span class="status-badge <?php echo $student['status'] === 'active' ? 'status-active' : ($student['status'] === 'pending' ? 'status-pending' : 'status-inactive'); ?>">
                <?php echo $student['status'] === 'active' ? 'Actif' : ($student['status'] === 'pending' ? 'En attente' : 'Inactif'); ?>
              </span></td>
              <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
              <td>
                <div class="actions-cell">
                  <button class="action-btn view" title="Voir">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button class="action-btn edit" title="Modifier">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="action-btn delete" title="Supprimer">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span id="paginationInfo">Affichage 1–<?php echo min(8, count($students)); ?> sur <?php echo count($students); ?> étudiants</span>
          <div class="page-btns">
            <button class="page-btn" onclick="changePage(-1)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="page-btn active" id="pg1" onclick="setPage(1)">1</button>
            <button class="page-btn" id="pg2" onclick="setPage(2)">2</button>
            <button class="page-btn" onclick="changePage(1)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Nouvel Étudiant -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Ajouter un étudiant</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Prénom</label>
          <input class="form-input" placeholder="Prénom" />
        </div>
        <div class="form-group">
          <label class="form-label">Nom</label>
          <input class="form-input" placeholder="Nom" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input class="form-input" type="email" placeholder="Email" />
      </div>
      <div class="form-group">
        <label class="form-label">Téléphone</label>
        <input class="form-input" placeholder="Téléphone" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Programme</label>
          <select class="form-select">
            <option value="">Sélectionner...</option>
            <option>Gestion</option>
            <option>Médecine</option>
            <option>Informatique</option>
            <option>Économie</option>
            <option>Droit</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Niveau</label>
          <select class="form-select">
            <option value="">Sélectionner...</option>
            <option>L1</option><option>L2</option><option>L3</option>
            <option>M1</option><option>M2</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date de naissance</label>
          <input class="form-input" type="date" />
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select class="form-select">
            <option>Actif</option>
            <option>En attente</option>
            <option>Inactif</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="btn-primary" onclick="saveStudent()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
      </button>
    </div>
  </div>
</div>

<script>
const COLORS = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#EC4899','#84CC16'];

// Récupérer les étudiants depuis la base de données via API
let students = [];
let currentPage = 1;
let filtered = [];
const PER_PAGE = 10; // Constante pour la pagination

// Fonction pour charger les étudiants depuis le serveur
async function loadStudents() {
  try {
    // Utiliser la vraie API pour la production
    const response = await fetch('api_crud_students.php');
    
    // Vérifier si la réponse est OK
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Vérifier si la réponse contient du contenu
    const text = await response.text();
    console.log('Réponse brute de l\'API:', text);
    
    if (!text) {
      throw new Error('Réponse vide du serveur');
    }
    
    // Parser le JSON
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('Erreur de parsing JSON:', e);
      console.error('Réponse brute:', text);
      throw new Error('Réponse JSON invalide: ' + e.message);
    }
    
    console.log('Données parsées:', data);
    
    if (data.success) {
      students = data.students || [];
      filtered = [...students];
      renderTable();
      updateStatistics();
      updateCharts(); // Mettre à jour les graphiques
      showNotification('✅ Étudiants chargés avec succès', 'success');
    } else {
      console.error('Erreur du serveur:', data.message);
      showNotification('❌ Erreur: ' + data.message, 'error');
    }
  } catch (error) {
    console.error('Erreur de chargement:', error);
    showNotification('❌ Erreur de chargement des étudiants: ' + error.message, 'error');
  }
}

// Fonction pour sauvegarder un étudiant dans la base de données
function saveStudent() {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  // Récupérer les valeurs du formulaire avec vérifications
  const firstNameInput = document.querySelector('input[placeholder="Prénom"]');
  const lastNameInput = document.querySelector('input[placeholder="Nom"]');
  const emailInput = document.querySelector('input[placeholder="Email"]');
  const phoneInput = document.querySelector('input[placeholder="Téléphone"]');
  const programSelect = document.querySelector('select');
  const levelSelect = document.querySelectorAll('select')[1];
  const dobInput = document.querySelector('input[type="date"]');
  const statusSelect = document.querySelectorAll('select')[2];
  
  // Vérifier que tous les éléments existent
  if (!firstNameInput || !lastNameInput || !emailInput || !programSelect || !levelSelect || !dobInput || !statusSelect) {
    console.error('Éléments du formulaire non trouvés:', {
      firstName: !!firstNameInput,
      lastName: !!lastNameInput,
      email: !!emailInput,
      phone: !!phoneInput,
      program: !!programSelect,
      level: !!levelSelect,
      dob: !!dobInput,
      status: !!statusSelect
    });
    
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Erreur: Formulaire incomplet';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  const firstName = firstNameInput.value.trim();
  const lastName = lastNameInput.value.trim();
  const email = emailInput.value.trim();
  const phone = phoneInput.value.trim();
  const programId = programSelect.value;
  const level = levelSelect.value;
  const dob = dobInput.value;
  const status = statusSelect.value;
  
  // Validation simple
  if (!firstName || !lastName || !email || !programId || !level || !dob) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Veuillez remplir tous les champs obligatoires';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  // Validation email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Email invalide';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  // Afficher le chargement
  btn.innerHTML = '⏳ Enregistrement en cours...';
  btn.disabled = true;
  
  console.log('Données à envoyer:', {
    first_name: firstName,
    last_name: lastName,
    email: email,
    phone: phone,
    program_id: programId,
    level: level,
    date_of_birth: dob,
    status: status
  });
  
  // Envoyer les données au serveur
  fetch('api_crud_students.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      first_name: firstName,
      last_name: lastName,
      email: email,
      phone: phone,
      program_id: programId,
      level: level,
      date_of_birth: dob,
      status: status
    })
  })
  .then(response => {
    console.log('Réponse du serveur:', response.status);
    return response.json();
  })
  .then(data => {
    console.log('Données reçues:', data);
    
    if (data.success) {
      btn.style.background = '#10B981';
      btn.innerHTML = '✅ Étudiant ajouté avec succès !';
      
      // Recharger la liste des étudiants
      loadStudents();
      
      // Fermer le modal et réinitialiser le formulaire
      setTimeout(() => {
        closeModal();
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        
        // Réinitialiser le formulaire
        firstNameInput.value = '';
        lastNameInput.value = '';
        emailInput.value = '';
        phoneInput.value = '';
        dobInput.value = '';
        programSelect.value = '';
        levelSelect.value = '';
        statusSelect.value = '';
      }, 2000);
    } else {
      btn.style.background = '#EF4444';
      btn.innerHTML = '❌ ' + data.message;
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
      }, 3000);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    btn.style.background = '#EF4444';
    btn.innerHTML = '❌ Erreur de connexion';
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = originalText;
      btn.style.background = '';
    }, 3000);
  });
}

// Fonction pour modifier un étudiant
function editStudent(id) {
  // Récupérer les détails de l'étudiant
  fetch(`api_crud_students.php?action=get&id=${id}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const student = data.student;
        
        // Remplir le formulaire avec les données de l'étudiant
        document.querySelector('input[placeholder="Prénom"]').value = student.first_name;
        document.querySelector('input[placeholder="Nom"]').value = student.last_name;
        document.querySelector('input[placeholder="Email"]').value = student.email;
        document.querySelector('input[placeholder="Téléphone"]').value = student.phone || '';
        document.querySelector('select').value = student.program_id || '';
        document.querySelectorAll('select')[1].value = student.level || '';
        document.querySelector('input[type="date"]').value = student.date_of_birth || '';
        document.querySelectorAll('select')[2].value = student.status || 'pending';
        
        // Changer le bouton pour "Mettre à jour"
        const btn = document.querySelector('.btn-primary');
        btn.innerHTML = '🔄 Mettre à jour';
        btn.onclick = () => updateStudent(id);
        
        // Ouvrir le modal
        openModal();
      } else {
        alert('Erreur: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Erreur:', error);
      alert('Erreur lors du chargement des données de l\'étudiant');
    });
}

// Fonction pour mettre à jour un étudiant
function updateStudent(id) {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  // Récupérer les valeurs du formulaire
  const firstName = document.querySelector('input[placeholder="Prénom"]').value.trim();
  const lastName = document.querySelector('input[placeholder="Nom"]').value.trim();
  const email = document.querySelector('input[placeholder="Email"]').value.trim();
  const phone = document.querySelector('input[placeholder="Téléphone"]').value.trim();
  const programSelect = document.querySelector('select');
  const programId = programSelect.value;
  const levelSelect = document.querySelectorAll('select')[1];
  const level = levelSelect.value;
  const dob = document.querySelector('input[type="date"]').value;
  const statusSelect = document.querySelectorAll('select')[2];
  const status = statusSelect.value;
  
  // Validation simple
  if (!firstName || !lastName || !email || !programId || !level || !dob) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Veuillez remplir tous les champs obligatoires';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  // Afficher le chargement
  btn.innerHTML = '⏳ Mise à jour en cours...';
  btn.disabled = true;
  
  // Envoyer les données au serveur
  fetch(`api_crud_students.php?id=${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      first_name: firstName,
      last_name: lastName,
      email: email,
      phone: phone,
      program_id: programId,
      level: level,
      date_of_birth: dob,
      status: status
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      btn.style.background = '#10B981';
      btn.innerHTML = '✅ Étudiant mis à jour avec succès !';
      
      // Recharger la liste des étudiants
      loadStudents();
      
      // Fermer le modal et réinitialiser le formulaire
      setTimeout(() => {
        closeModal();
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        btn.onclick = saveStudent; // Remettre le bouton en mode "Ajouter"
        
        // Réinitialiser le formulaire
        document.querySelector('input[placeholder="Prénom"]').value = '';
        document.querySelector('input[placeholder="Nom"]').value = '';
        document.querySelector('input[placeholder="Email"]').value = '';
        document.querySelector('input[placeholder="Téléphone"]').value = '';
        document.querySelector('input[type="date"]').value = '';
        programSelect.value = '';
        levelSelect.value = '';
        statusSelect.value = '';
      }, 2000);
    } else {
      btn.style.background = '#EF4444';
      btn.innerHTML = '❌ ' + data.message;
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
      }, 3000);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    btn.style.background = '#EF4444';
    btn.innerHTML = '❌ Erreur de connexion';
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = originalText;
      btn.style.background = '';
    }, 3000);
  });
}

// Fonction pour supprimer un étudiant
function deleteStudent(id) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ? Cette action est irréversible.')) {
    return;
  }
  
  fetch(`api_crud_students.php?id=${id}`, {
    method: 'DELETE'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Recharger la liste des étudiants
      loadStudents();
      
      // Afficher une notification
      showNotification('✅ Étudiant supprimé avec succès', 'success');
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('❌ Erreur lors de la suppression');
  });
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed; top: 20px; right: 20px; 
    background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'}; 
    color: white; padding: 15px 20px; 
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000; font-weight: 600; font-size: 14px;
  `;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.transition = 'opacity 0.3s ease-out';
    notification.style.opacity = '0';
    setTimeout(() => document.body.removeChild(notification), 300);
  }, 3000);
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed; top: 20px; right: 20px; 
    background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'}; 
    color: white; padding: 15px 20px; 
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000; font-weight: 600; font-size: 14px;
  `;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.transition = 'opacity 0.3s ease-out';
    notification.style.opacity = '0';
    setTimeout(() => document.body.removeChild(notification), 300);
  }, 3000);
}

// Fonction pour mettre à jour les statistiques
function updateStatistics() {
  const totalStudents = students.length;
  const activeStudents = students.filter(s => s.status === 'active').length;
  const pendingStudents = students.filter(s => s.status === 'pending').length;
  const inactiveStudents = students.filter(s => s.status === 'inactive').length;
  
  // Mettre à jour les KPIs
  const totalKpi = document.querySelector('.kpi-card:nth-child(1) .kpi-value');
  const activeKpi = document.querySelector('.kpi-card:nth-child(2) .kpi-value');
  const pendingKpi = document.querySelector('.kpi-card:nth-child(3) .kpi-value');
  const inactiveKpi = document.querySelector('.kpi-card:nth-child(4) .kpi-value');
  
  if (totalKpi) totalKpi.textContent = totalStudents;
  if (activeKpi) activeKpi.textContent = activeStudents;
  if (pendingKpi) pendingKpi.textContent = pendingStudents;
  if (inactiveKpi) inactiveKpi.textContent = inactiveStudents;
}

// Fonction pour mettre à jour tous les graphiques avec les données de la base
function updateCharts() {
  console.log('Mise à jour des graphiques avec', students.length, 'étudiants');
  
  // Mettre à jour le graphique par niveau
  updateLevelChart();
  
  // Mettre à jour le graphique par programme
  updateProgramChart();
  
  // Mettre à jour le graphique par statut
  updateStatusChart();
  
  // Mettre à jour le graphique mensuel
  updateMonthlyChart();
}

// Graphique par niveau
function updateLevelChart() {
  const levelData = {};
  students.forEach(student => {
    const level = student.level || 'Non spécifié';
    levelData[level] = (levelData[level] || 0) + 1;
  });
  
  const levelChart = document.getElementById('levelChart');
  if (levelChart) {
    const labels = Object.keys(levelData);
    const data = Object.values(levelData);
    
    levelChart.innerHTML = `
      <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 200px;">
        ${labels.map((label, index) => `
          <div style="text-align: center; flex: 1;">
            <div style="height: ${data[index] * 20}px; background: #f59e0b; margin: 0 5px; border-radius: 4px;"></div>
            <div style="margin-top: 5px; font-size: 12px; font-weight: 600;">${label}</div>
            <div style="font-size: 10px; color: #64748b;">${data[index]}</div>
          </div>
        `).join('')}
      </div>
    `;
  }
}

// Graphique par programme
function updateProgramChart() {
  const programData = {};
  students.forEach(student => {
    const program = student.program_name || student.prog || 'Non spécifié';
    programData[program] = (programData[program] || 0) + 1;
  });
  
  const programChart = document.getElementById('programChart');
  if (programChart) {
    const labels = Object.keys(programData);
    const data = Object.values(programData);
    
    programChart.innerHTML = `
      <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 200px;">
        ${labels.map((label, index) => `
          <div style="text-align: center; flex: 1;">
            <div style="height: ${data[index] * 15}px; background: #10b981; margin: 0 5px; border-radius: 4px;"></div>
            <div style="margin-top: 5px; font-size: 12px; font-weight: 600;">${label.substring(0, 10)}${label.length > 10 ? '...' : ''}</div>
            <div style="font-size: 10px; color: #64748b;">${data[index]}</div>
          </div>
        `).join('')}
      </div>
    `;
  }
}

// Graphique par statut
function updateStatusChart() {
  const statusData = {
    'Actif': students.filter(s => s.status === 'active').length,
    'En attente': students.filter(s => s.status === 'pending').length,
    'Inactif': students.filter(s => s.status === 'inactive').length
  };
  
  const statusChart = document.getElementById('statusChart');
  if (statusChart) {
    const labels = Object.keys(statusData);
    const data = Object.values(statusData);
    const colors = ['#10b981', '#f59e0b', '#dc2626'];
    
    statusChart.innerHTML = `
      <div style="display: flex; justify-content: space-around; align-items: center; height: 150px;">
        ${labels.map((label, index) => `
          <div style="text-align: center;">
            <div style="width: 80px; height: 80px; background: ${colors[index]}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">
              ${data[index]}
            </div>
            <div style="margin-top: 10px; font-size: 12px; font-weight: 600;">${label}</div>
          </div>
        `).join('')}
      </div>
    `;
  }
}

// Graphique mensuel (inscriptions par mois)
function updateMonthlyChart() {
  const monthlyData = {};
  const currentYear = new Date().getFullYear();
  
  students.forEach(student => {
    const date = student.registration_date || student.date || new Date().toISOString();
    const month = new Date(date).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    monthlyData[month] = (monthlyData[month] || 0) + 1;
  });
  
  const monthlyChart = document.getElementById('monthlyChart');
  if (monthlyChart) {
    const labels = Object.keys(monthlyData).slice(-6); // 6 derniers mois
    const data = labels.map(label => monthlyData[label]);
    
    monthlyChart.innerHTML = `
      <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 150px;">
        ${labels.map((label, index) => `
          <div style="text-align: center; flex: 1;">
            <div style="height: ${data[index] * 25}px; background: #3b82f6; margin: 0 5px; border-radius: 4px;"></div>
            <div style="margin-top: 5px; font-size: 10px; font-weight: 600;">${label}</div>
            <div style="font-size: 10px; color: #64748b;">${data[index]}</div>
          </div>
        `).join('')}
      </div>
    `;
  }
}

// Charger les étudiants au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  loadStudents();
});

function getInitials(name) {
  return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
}
function getColor(name) {
  let h = 0; for(let c of name) h += c.charCodeAt(0);
  return COLORS[h % COLORS.length];
}
function statusInfo(s) {
  if(s==='active')   return { label:'Actif',      cls:'status-active',   dot:'dot-active'   };
  if(s==='inactive') return { label:'Inactif',    cls:'status-inactive', dot:'dot-inactive' };
  if(s==='pending')  return { label:'En attente', cls:'status-pending',  dot:'dot-pending'  };
  return { label:s, cls:'status-pending', dot:'dot-pending' };
}

function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(e) { if(e.target===document.getElementById('modalOverlay')) closeModal(); }

function renderTable() {
  console.log('=== renderTable() appelé ===');
  console.log('students:', students);
  console.log('filtered:', filtered);
  console.log('currentPage:', currentPage);
  console.log('PER_PAGE:', PER_PAGE);
  
  const tbody = document.getElementById('studentTableBody');
  console.log('tbody element:', tbody);
  
  if (!tbody) {
    console.error('ERREUR: studentTableBody non trouvé !');
    return;
  }
  
  const start = (currentPage - 1) * PER_PAGE;
  const page = filtered.slice(start, start + PER_PAGE);
  
  console.log('start:', start);
  console.log('page:', page);
  console.log('nombre d\'étudiants à afficher:', page.length);
  
  tbody.innerHTML = '';
  
  if (page.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">Aucun étudiant trouvé</td></tr>';
    return;
  }
  
  page.forEach((s, index) => {
    console.log(`Traitement étudiant ${index}:`, s);
    
    const ini = getInitials(s.name);
    const col = getColor(s.name);
    const si = statusInfo(s.status);
    const parts = s.name.split(' ');
    const disp = `<span style="font-weight:700">${parts[0]}</span> ${parts.slice(1).join(' ')}`;
    
    const rowHtml = `
      <tr>
        <td>
          <div class="student-cell">
            <div class="stu-avatar" style="background:${col}18;color:${col};">${ini}</div>
            <div>
              <div class="stu-name">${disp}</div>
              <div class="stu-email">${s.email}</div>
            </div>
          </div>
        </td>
        <td><span class="prog-tag">${s.prog || s.program_name || 'N/A'}</span></td>
        <td><span class="level-badge">${s.level || 'N/A'}</span></td>
        <td>
          <span class="status-badge ${si.cls}">
            <span class="status-dot ${si.dot}"></span>
            ${si.label}
          </span>
        </td>
        <td>${s.date || 'N/A'}</td>
        <td>
          <div class="actions-cell">
            <button class="action-btn view" title="Voir">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="action-btn edit" title="Modifier">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="action-btn delete" title="Supprimer">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `;
    
    tbody.innerHTML += rowHtml;
  });
  
  const total = filtered.length;
  const end = Math.min(start + PER_PAGE, total);
  document.getElementById('paginationInfo').textContent = 
    `Affichage ${start+1}–${end} sur ${total} étudiant${total>1?'s':''}`;
  document.getElementById('pg1').classList.toggle('active', currentPage===1);
  document.getElementById('pg2').classList.toggle('active', currentPage===2);
  
  console.log('renderTable() terminé');
}

// ...

function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  filtered = students.filter(s =>
    s.name.toLowerCase().includes(q) || s.email.toLowerCase().includes(q)
  );
  currentPage = 1;
  renderTable();
}

function changePage(d) {
  const max = Math.ceil(filtered.length / PER_PAGE);
  currentPage = Math.max(1, Math.min(max, currentPage + d));
  renderTable();
}
function setPage(p) { currentPage = p; renderTable(); }

function toggleFilter() {
  document.getElementById('filterPanel').classList.toggle('open');
}

function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(e) { if(e.target===document.getElementById('modalOverlay')) closeModal(); }

// Fonction pour sauvegarder un étudiant dans la base de données
function saveStudent() {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  // Récupérer les valeurs du formulaire
  const firstName = document.querySelector('input[placeholder="Prénom"]').value.trim();
  const lastName = document.querySelector('input[placeholder="Nom"]').value.trim();
  const email = document.querySelector('input[placeholder="Email"]').value.trim();
  const phone = document.querySelector('input[placeholder="Téléphone"]').value.trim();
  const programSelect = document.querySelector('select');
  const programId = programSelect.value;
  const levelSelect = document.querySelectorAll('select')[1];
  const level = levelSelect.value;
  const dob = document.querySelector('input[type="date"]').value;
  const statusSelect = document.querySelectorAll('select')[2];
  const status = statusSelect.value;
  
  // Validation simple
  if (!firstName || !lastName || !email || !programId || !level || !dob) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Veuillez remplir tous les champs obligatoires';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  // Validation email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Email invalide';
    setTimeout(() => {
      btn.style.background = '';
      btn.innerHTML = originalText;
    }, 3000);
    return;
  }
  
  // Afficher le chargement
  btn.innerHTML = '⏳ Enregistrement en cours...';
  btn.disabled = true;
  
  // Envoyer les données au serveur
  fetch('save_student.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      first_name: firstName,
      last_name: lastName,
      email: email,
      phone: phone,
      program_id: programId,
      level: level,
      date_of_birth: dob,
      status: status
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      btn.style.background = '#10B981';
      btn.innerHTML = '✅ Étudiant ajouté avec succès !';
      
      // Ajouter le nouvel étudiant à la liste locale
      const newStudent = {
        id: data.student_id,
        name: firstName + ' ' + lastName,
        email: email,
        prog: programSelect.options[programSelect.selectedIndex].text,
        level: level,
        status: status,
        date: new Date().toLocaleDateString('fr-FR')
      };
      students.unshift(newStudent);
      renderTable();
      updateStatistics();
      
      // Fermer le modal et réinitialiser le formulaire
      setTimeout(() => {
        closeModal();
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        
        // Réinitialiser le formulaire
        document.querySelector('input[placeholder="Prénom"]').value = '';
        document.querySelector('input[placeholder="Nom"]').value = '';
        document.querySelector('input[placeholder="Email"]').value = '';
        document.querySelector('input[placeholder="Téléphone"]').value = '';
        document.querySelector('input[type="date"]').value = '';
        programSelect.value = '';
        levelSelect.value = '';
        statusSelect.value = '';
      }, 2000);
    } else {
      btn.style.background = '#EF4444';
      btn.innerHTML = '❌ ' + data.message;
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
      }, 3000);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    btn.style.background = '#EF4444';
    btn.innerHTML = '❌ Erreur de connexion';
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = originalText;
      btn.style.background = '';
    }, 3000);
  });
}

// Fonction pour exporter les étudiants en CSV
function exportStudents() {
  const rows = document.querySelectorAll('#studentTableBody tr');
  const data = [['Nom', 'Email', 'Programme', 'Niveau', 'Statut', 'Date']];
  
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    const name = cells[0].querySelector('.stu-name')?.textContent || '';
    const email = cells[0].querySelector('.stu-email')?.textContent || '';
    const program = cells[1].textContent;
    const level = cells[2].textContent;
    const status = cells[3].querySelector('span')?.textContent || '';
    const date = cells[4].textContent;
    
    data.push([name, email, program, level, status, date]);
  });
  
  let csvContent = "data:text/csv;charset=utf-8,";
  data.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'export_etudiants_taatj.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  alert('Liste des étudiants exportée avec succès !');
}

// Fonction pour générer un PDF des étudiants
function generateStudentsPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  // Ajouter un en-tête professionnel
  doc.setFillColor(59, 130, 246); // Bleu TAAJ
  doc.rect(0, 0, 210, 40, 'F');
  
  // Logo et titre
  doc.setTextColor(255, 255, 255);
  doc.setFontSize(24);
  doc.setFont(undefined, 'bold');
  doc.text('TAAJ Corp', 20, 25);
  
  doc.setFontSize(16);
  doc.setFont(undefined, 'normal');
  doc.text('Liste des Étudiants', 70, 25);
  
  // Date
  doc.setTextColor(100, 100, 100);
  doc.setFontSize(10);
  doc.text(`Généré le: ${new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} à ${new Date().toLocaleTimeString('fr-FR')}`, 140, 25);
  
  // Ligne de séparation
  doc.setDrawColor(200, 200, 200);
  doc.setLineWidth(0.5);
  doc.line(20, 45, 190, 45);
  
  // Statistiques
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.text('Statistiques des Étudiants:', 20, 60);
  
  doc.setFontSize(11);
  doc.setFont(undefined, 'normal');
  
  const totalStudents = students.length;
  const activeStudents = students.filter(s => s.status === 'active').length;
  const pendingStudents = students.filter(s => s.status === 'pending').length;
  const inactiveStudents = students.filter(s => s.status === 'inactive').length;
  
  const stats = [
    ['Total étudiants', totalStudents],
    ['Actifs', activeStudents],
    ['En attente', pendingStudents],
    ['Inactifs', inactiveStudents]
  ];
  
  let yPos = 70;
  stats.forEach(([label, value]) => {
    doc.text(`${label}:`, 30, yPos);
    doc.setFont(undefined, 'bold');
    doc.text(`${value}`, 100, yPos);
    doc.setFont(undefined, 'normal');
    yPos += 8;
  });
  
  // Ligne de séparation
  doc.setDrawColor(200, 200, 200);
  doc.line(20, yPos + 5, 190, yPos + 5);
  yPos += 15;
  
  // Tableau des étudiants
  doc.setFontSize(12);
  doc.setFont(undefined, 'bold');
  doc.text('Liste Détaillée des Étudiants:', 20, yPos);
  yPos += 15;
  
  // En-têtes de tableau
  const headers = ['#', 'Nom Complet', 'Email', 'Programme', 'Niveau', 'Statut', "Date d'inscription"];
  const headerWidths = [10, 50, 45, 30, 20, 25, 30];
  
  // Fond d'en-tête
  doc.setFillColor(240, 240, 240);
  doc.rect(20, yPos, 170, 10, 'F');
  
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(9);
  doc.setFont(undefined, 'bold');
  
  let xPos = 20;
  headers.forEach((header, index) => {
    doc.text(header, xPos + 2, yPos + 7);
    xPos += headerWidths[index];
  });
  
  // Lignes verticales du tableau
  doc.setDrawColor(200, 200, 200);
  xPos = 20;
  headers.forEach((header, index) => {
    doc.line(xPos, yPos, xPos, yPos + 10);
    xPos += headerWidths[index];
  });
  doc.line(190, yPos, 190, yPos + 10);
  
  yPos += 10;
  
  // Données des étudiants
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(8);
  doc.setFont(undefined, 'normal');
  
  students.forEach((student, index) => {
    if (yPos > 270) {
      doc.addPage();
      yPos = 20;
      
      // Répéter l'en-tête sur la nouvelle page
      doc.setFillColor(59, 130, 246);
      doc.rect(0, 0, 210, 40, 'F');
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(24);
      doc.setFont(undefined, 'bold');
      doc.text('TAAJ Corp', 20, 25);
      doc.setFontSize(16);
      doc.setFont(undefined, 'normal');
      doc.text('Liste des Étudiants (Suite)', 70, 25);
      
      // En-têtes de tableau sur nouvelle page
      doc.setFillColor(240, 240, 240);
      doc.rect(20, yPos, 170, 10, 'F');
      doc.setTextColor(0, 0, 0);
      doc.setFontSize(9);
      doc.setFont(undefined, 'bold');
      
      xPos = 20;
      headers.forEach((header, index) => {
        doc.text(header, xPos + 2, yPos + 7);
        xPos += headerWidths[index];
      });
      
      doc.setDrawColor(200, 200, 200);
      xPos = 20;
      headers.forEach((header, index) => {
        doc.line(xPos, yPos, xPos, yPos + 10);
        xPos += headerWidths[index];
      });
      doc.line(190, yPos, 190, yPos + 10);
      
      yPos += 10;
      doc.setTextColor(0, 0, 0);
      doc.setFontSize(8);
      doc.setFont(undefined, 'normal');
    }
    
    // Ligne horizontale
    doc.setDrawColor(200, 200, 200);
    doc.line(20, yPos, 190, yPos);
    
    // Données de l'étudiant
    const rowData = [
      (index + 1).toString(),
      student.name,
      student.email,
      student.prog,
      student.level,
      student.status === 'active' ? 'Actif' : (student.status === 'pending' ? 'En attente' : 'Inactif'),
      student.date
    ];
    
    xPos = 20;
    rowData.forEach((data, index) => {
      doc.text(data, xPos + 2, yPos + 6);
      xPos += headerWidths[index];
    });
    
    // Lignes verticales
    doc.setDrawColor(200, 200, 200);
    xPos = 20;
    rowData.forEach((data, index) => {
      doc.line(xPos, yPos, xPos, yPos + 10);
      xPos += headerWidths[index];
    });
    doc.line(190, yPos, 190, yPos + 10);
    
    yPos += 10;
  });
  
  // Pied de page
  const finalY = yPos + 20;
  if (finalY > 280) {
    doc.addPage();
    finalY = 20;
  }
  
  doc.setDrawColor(200, 200, 200);
  doc.line(20, finalY, 190, finalY);
  
  doc.setTextColor(100, 100, 100);
  doc.setFontSize(9);
  doc.setFont(undefined, 'italic');
  doc.text('Document généré par la plateforme TAAJ Corp - Tous droits réservés', 105, finalY + 10);
  doc.autoTable({
    head: ['Nom', 'Email', 'Programme', 'Niveau', 'Statut'],
    body: tableData,
    startY: yPos + 10,
    theme: 'grid',
    styles: { fontSize: 9 }
  });
  
  // Sauvegarder le PDF
  doc.save('liste_etudiants_taatj.pdf');
  
  alert('PDF des étudiants généré avec succès !');
}

renderTable();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
