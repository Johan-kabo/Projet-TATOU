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

  /* ── PAGINATION STYLES ── */
  .btn-sm {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 28px; padding: 4px 8px;
    background: var(--card-bg); color: var(--text-primary);
    border: 1px solid var(--border); border-radius: 6px;
    font-size: 12px; font-weight: 500; font-family: var(--font);
    cursor: pointer; transition: all 0.2s ease; white-space: nowrap;
  }
  .btn-sm:hover:not(:disabled) {
    background: var(--primary); color: white; border-color: var(--primary);
  }
  .btn-sm:active:not(:disabled) {
    transform: scale(0.95);
  }
  .btn-sm:disabled {
    opacity: 0.4; cursor: not-allowed;
  }
  .btn-sm svg {
    width: 12px; height: 12px;
  }

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
          <select class="form-select" id="filterProgram">
            <option value="">Tous les programmes</option>
            <?php
            // Charger les programmes dynamiquement depuis la base
            try {
                $stmt = $pdo->query("SELECT id, name, active FROM programs ORDER BY name");
                $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($programs as $program) {
                    $status = $program['active'] ? '' : ' (inactif)';
                    echo '<option value="' . $program['id'] . '">' . htmlspecialchars($program['name'] . $status) . '</option>';
                }
            } catch (PDOException $e) {
                echo '<option value="">Erreur de chargement</option>';
            }
            ?>
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
            <button class="btn-pdf" onclick="generateModernStudentsPDF()">
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
                  <button class="action-btn view" title="Voir" onclick="viewStudent(<?php echo $student['id']; ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button class="action-btn edit" title="Modifier" onclick="editStudent(<?php echo $student['id']; ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="action-btn delete" title="Supprimer" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div id="pagination"></div>
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
          <input class="form-input" id="firstName" placeholder="Prénom" />
        </div>
        <div class="form-group">
          <label class="form-label">Nom</label>
          <input class="form-input" id="lastName" placeholder="Nom" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input class="form-input" type="email" id="email" placeholder="Email" />
      </div>
      <div class="form-group">
        <label class="form-label">Téléphone</label>
        <input class="form-input" id="phone" placeholder="Téléphone" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Programme</label>
          <select class="form-select" id="programId">
            <option value="">Sélectionner...</option>
            <?php
            // Charger les programmes dynamiquement depuis la base
            try {
                $stmt = $pdo->query("SELECT id, name, active FROM programs ORDER BY name");
                $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($programs as $program) {
                    $status = $program['active'] ? '' : ' (inactif)';
                    echo '<option value="' . $program['id'] . '">' . htmlspecialchars($program['name'] . $status) . '</option>';
                }
            } catch (PDOException $e) {
                echo '<option value="">Erreur de chargement</option>';
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Niveau</label>
          <select class="form-select" id="level">
            <option value="">Sélectionner...</option>
            <option value="L1">L1</option>
            <option value="L2">L2</option>
            <option value="L3">L3</option>
            <option value="M1">M1</option>
            <option value="M2">M2</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Date de naissance</label>
        <input class="form-input" type="date" id="dateOfBirth" />
      </div>
      <div class="form-group">
        <label class="form-label">Statut</label>
        <select class="form-select" id="status">
          <option value="pending">En attente</option>
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
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
        // Variables globales
        let students = [];
        let currentPage = 1;
        let editingStudentId = null; // Pour suivre l'ID en cours de modification
        const STUDENTS_PER_PAGE = 8;

        // Fonctions du modal
        function openModal() { 
            console.log('openModal() appelé');
            document.getElementById('modalOverlay').classList.add('open'); 
        }

        function closeModal() { 
            console.log('closeModal() appelé');
            document.getElementById('modalOverlay').classList.remove('open'); 
        }

        function handleOverlayClick(e) { 
            if(e.target===document.getElementById('modalOverlay')) closeModal(); 
        }

        // Fonction de notification
        function showNotification(message, type = 'info') {
            // Supprimer les notifications existantes
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());
            
            // Créer la nouvelle notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; 
                padding: 15px 20px; border-radius: 8px; color: white; 
                font-weight: 600; z-index: 9999; min-width: 250px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            // Couleurs selon le type
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6',
                warning: '#f59e0b'
            };
            notification.style.background = colors[type] || colors.info;
            
            document.body.appendChild(notification);
            
            // Supprimer après 3 secondes
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Charger les étudiants
        async function loadStudents() {
            try {
                const response = await fetch('api_students_simple.php');
                const text = await response.text();
                console.log('Réponse API:', text);
                
                if (!text) {
                    throw new Error('Réponse vide du serveur');
                }
                
                const data = JSON.parse(text);
                console.log('Données parsées:', data);
                
                if (data.success) {
                    students = data.students || [];
                    renderStudentList();
                    showNotification('✅ Étudiants chargés avec succès', 'success');
                } else {
                    showNotification('❌ Erreur: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erreur de chargement:', error);
                showNotification('❌ Erreur de chargement: ' + error.message, 'error');
            }
        }

        // Afficher la liste des étudiants
        function renderStudentList() {
            const tbody = document.getElementById('studentTableBody');
            
            if (!tbody) {
                console.error('ERREUR: studentTableBody non trouvé !');
                return;
            }
            
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">Aucun étudiant trouvé</td></tr>';
                updatePagination();
                return;
            }

            // Calculer les étudiants pour la page actuelle
            const startIndex = (currentPage - 1) * STUDENTS_PER_PAGE;
            const endIndex = startIndex + STUDENTS_PER_PAGE;
            const currentPageStudents = students.slice(startIndex, endIndex);

            let html = '';
            currentPageStudents.forEach(student => {
                const statusClass = student.status === 'active' ? 'status-active' : 
                                   student.status === 'pending' ? 'status-pending' : 'status-inactive';
                const statusText = student.status === 'active' ? 'Actif' : 
                                  student.status === 'pending' ? 'En attente' : 'Inactif';
                
                html += '<tr>';
                html += '<td>';
                html += '<div class="student-cell">';
                html += '<div class="stu-avatar" style="background:#3B82F618;color:#3B82F6;">';
                html += student.name ? student.name.substring(0, 2).toUpperCase() : 'ST';
                html += '</div>';
                html += '<div>';
                html += '<div class="stu-name">' + (student.name || '') + '</div>';
                html += '<div class="stu-email">' + (student.email || '') + '</div>';
                html += '</div>';
                html += '</div>';
                html += '</td>';
                html += '<td>' + (student.program_name || 'N/A') + '</td>';
                html += '<td>' + (student.level || 'N/A') + '</td>';
                html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
                html += '<td>' + (student.date || student.registration_date || '-') + '</td>';
                html += '<td>';
                html += '<div class="actions-cell">';
                html += '<button class="btn btn-sm" onclick="editStudent(' + student.id + ')">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14M11 5l-5 5L5 12l6-6"/></svg>';
                html += '</button>';
                html += '<button class="btn btn-sm btn-danger" onclick="deleteStudent(' + student.id + ')">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6m3 0V4h6"/></svg>';
                html += '</button>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            });

            tbody.innerHTML = html;
            updatePagination();
        }

        // Mettre à jour la pagination
        function updatePagination() {
            const totalPages = Math.ceil(students.length / STUDENTS_PER_PAGE);
            const paginationContainer = document.getElementById('pagination');
            
            if (!paginationContainer) {
                console.error('ERREUR: pagination non trouvé !');
                return;
            }
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let html = '<div style="display: flex; justify-content: center; align-items: center; gap: 4px; padding: 16px; margin-top: 16px;">';
            
            // Bouton précédent
            const prevDisabled = currentPage === 1 ? 'opacity: 0.4; cursor: not-allowed;' : 'cursor: pointer;';
            html += `<button class="btn btn-sm" onclick="changePage(-1)" style="${prevDisabled}" ${currentPage === 1 ? 'disabled' : ''}>`;
            html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>';
            html += '</button>';
            
            // Numéros de page
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    const isActive = i === currentPage;
                    const pageStyle = isActive ? 'background: var(--primary); color: white; border-color: var(--primary);' : 'background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border);';
                    html += `<button class="btn btn-sm" onclick="goToPage(${i})" style="${pageStyle}">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += '<span style="padding: 0 4px; color: var(--text-muted); font-size: 12px;">...</span>';
                }
            }
            
            // Bouton suivant
            const nextDisabled = currentPage === totalPages ? 'opacity: 0.4; cursor: not-allowed;' : 'cursor: pointer;';
            html += `<button class="btn btn-sm" onclick="changePage(1)" style="${nextDisabled}" ${currentPage === totalPages ? 'disabled' : ''}>`;
            html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            html += '</button>';
            
            // Information sur la page
            const startItem = (currentPage - 1) * STUDENTS_PER_PAGE + 1;
            const endItem = Math.min(currentPage * STUDENTS_PER_PAGE, students.length);
            html += `<span style="margin-left: 12px; color: var(--text-muted); font-size: 12px; font-weight: 400;">`;
            html += `${startItem}-${endItem} sur ${students.length}`;
            html += '</span>';
            
            html += '</div>';
            paginationContainer.innerHTML = html;
        }

        // Changer de page
        function changePage(direction) {
            const totalPages = Math.ceil(students.length / STUDENTS_PER_PAGE);
            const newPage = currentPage + direction;
            
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                renderStudentList();
            }
        }

        // Aller à une page spécifique
        function goToPage(page) {
            const totalPages = Math.ceil(students.length / STUDENTS_PER_PAGE);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderStudentList();
            }
        }

        // Sauvegarder un étudiant
        function saveStudent() {
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            
            try {
                // Récupérer les valeurs
                const firstName = document.getElementById('firstName')?.value?.trim() || '';
                const lastName = document.getElementById('lastName')?.value?.trim() || '';
                const email = document.getElementById('email')?.value?.trim() || '';
                const phone = document.getElementById('phone')?.value?.trim() || '';
                const programId = document.getElementById('programId')?.value || '';
                const level = document.getElementById('level')?.value || '';
                const dob = document.getElementById('dateOfBirth')?.value || '';
                const status = document.getElementById('status')?.value || '';
                
                console.log('=== SAVE STUDENT DEBUG ===');
                console.log('firstName:', firstName);
                console.log('lastName:', lastName);
                console.log('email:', email);
                console.log('phone:', phone);
                console.log('programId:', programId);
                console.log('level:', level);
                console.log('dob:', dob);
                console.log('status:', status);
                console.log('=== END DEBUG ===');
                
                // Validation
                
                // Désactiver le bouton
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                
                // Envoyer les données
                const requestData = {
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    phone: phone,
                    program_id: programId,
                    level: level,
                    date_of_birth: dob,
                    status: status
                };
                
                // Ajouter l'ID si c'est une modification
                if (editingStudentId) {
                    requestData.id = editingStudentId;
                }
                
                // Log de debug pour voir les données envoyées
                console.log('=== DONNÉES ENVOYÉES AU SERVEUR ===');
                console.log('requestData:', requestData);
                console.log('programId:', programId);
                console.log('editingStudentId:', editingStudentId);
                console.log('=== FIN DEBUG ===');
                
                fetch('save_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Réponse saveStudent:', data);
                    
                    if (data.success) {
                        showNotification('✅ ' + data.message, 'success');
                        closeModal();
                        currentPage = 1; // Réinitialiser à la première page
                        loadStudents(); // Recharger la liste
                        
                        // Réinitialiser le formulaire
                        resetStudentForm();
                    } else {
                        showNotification('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur saveStudent:', error);
                    showNotification('❌ Erreur de connexion: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
                
            } catch (error) {
                console.error('Erreur dans saveStudent:', error);
                showNotification('❌ Erreur: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Fonction pour voir les détails d'un étudiant
        function viewStudent(id) {
            // Trouver l'étudiant dans les données chargées
            const student = students.find(s => s.id === id);
            if (student) {
                alert(`Détails de l'étudiant:\n\nNom: ${student.first_name} ${student.last_name}\nEmail: ${student.email}\nTéléphone: ${student.phone || 'N/A'}\nProgramme: ${student.program_name || 'N/A'}\nStatut: ${student.status}`);
            }
        }

        // Fonction pour modifier un étudiant
        function editStudent(id) {
            // Trouver l'étudiant dans les données chargées
            const student = students.find(s => s.id === id);
            if (!student) {
                showNotification('Étudiant non trouvé', 'error');
                return;
            }
            
            // Définir l'ID de l'étudiant en cours de modification
            editingStudentId = id;
            
            // Pré-remplir le formulaire avec les données de l'étudiant
            document.getElementById('firstName').value = student.first_name || '';
            document.getElementById('lastName').value = student.last_name || '';
            document.getElementById('email').value = student.email || '';
            document.getElementById('phone').value = student.phone || '';
            document.getElementById('programId').value = student.program_id || '';
            document.getElementById('level').value = student.level || '';
            document.getElementById('dateOfBirth').value = student.date_of_birth || '';
            document.getElementById('status').value = student.status || 'pending';
            
            // Changer le titre du modal et du bouton
            document.querySelector('.modal-title').textContent = 'Modifier l\'Étudiant';
            const saveBtn = document.querySelector('.modal-footer .btn-primary');
            saveBtn.innerHTML = 'Mettre à jour';
            saveBtn.onclick = saveStudent; // Utiliser la même fonction saveStudent
            
            // Ouvrir le modal
            openModal();
        }

        // Fonction pour mettre à jour un étudiant
        function updateStudent(id) {
            const btn = document.querySelector('.modal-footer .btn-primary');
            const originalText = btn.innerHTML;
            
            // Récupérer les valeurs du formulaire
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const programId = document.getElementById('programId').value;
            const level = document.getElementById('level').value;
            const dateOfBirth = document.getElementById('dateOfBirth').value;
            const status = document.getElementById('status').value;
            
            // Validation
            if (!firstName || !lastName || !email) {
                btn.style.background = '#EF4444';
                btn.innerHTML = '⚠️ Veuillez remplir les champs obligatoires';
                setTimeout(() => {
                    btn.style.background = '';
                    btn.innerHTML = originalText;
                }, 3000);
                return;
            }
            
            // Afficher le chargement
            btn.innerHTML = '⏳ Mise à jour...';
            btn.disabled = true;
            
            // Envoyer la mise à jour
            fetch('save_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    phone: phone,
                    programId: programId,
                    level: level,
                    dateOfBirth: dateOfBirth,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.style.background = '#10B981';
                    btn.innerHTML = '✅ Mis à jour';
                    
                    // Mettre à jour l'étudiant dans le tableau local
                    const studentIndex = students.findIndex(s => s.id === id);
                    if (studentIndex !== -1) {
                        students[studentIndex] = {
                            ...students[studentIndex],
                            first_name: firstName,
                            last_name: lastName,
                            email: email,
                            phone: phone,
                            program_id: programId,
                            level: level,
                            date_of_birth: dateOfBirth,
                            status: status
                        };
                    }
                    
                    setTimeout(() => {
                        closeModal();
                        loadStudents();
                        resetStudentForm();
                    }, 1500);
                } else {
                    btn.style.background = '#EF4444';
                    btn.innerHTML = '❌ ' + data.message;
                }
            })
            .catch(error => {
                btn.style.background = '#EF4444';
                btn.innerHTML = '❌ Erreur réseau';
            })
            .finally(() => {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                }, 3000);
            });
        }

        // Fonction pour supprimer un étudiant
        function deleteStudent(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ? Cette action est irréversible.')) {
                // Envoyer la requête de suppression
                fetch('save_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        delete: true,
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Étudiant supprimé avec succès', 'success');
                        
                        // Supprimer l'étudiant du tableau et recharger
                        students = students.filter(s => s.id !== id);
                        loadStudents();
                    } else {
                        showNotification('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur réseau lors de la suppression', 'error');
                });
            }
        }

        // Fonction pour réinitialiser le formulaire étudiant
        function resetStudentForm() {
            editingStudentId = null; // Réinitialiser l'ID de modification
            
            document.getElementById('firstName').value = '';
            document.getElementById('lastName').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('programId').value = '';
            document.getElementById('level').value = '';
            document.getElementById('dateOfBirth').value = '';
            document.getElementById('status').value = 'pending';
            
            // Réinitialiser le titre et le bouton
            document.querySelector('.modal-title').textContent = 'Ajouter un Étudiant';
            const saveBtn = document.querySelector('.modal-footer .btn-primary');
            saveBtn.innerHTML = 'Enregistrer';
            saveBtn.onclick = saveStudent;
        }

        // Fonction pour générer un PDF des étudiants avec jsPDF
        function generateStudentsPDF() {
            showNotification('📄 Génération du PDF en cours...', 'success');
            
            // Récupérer les données des étudiants
            fetch('api_crud_students.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        createPDFWithStudents(data.students);
                    } else {
                        showNotification('❌ Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('❌ Erreur: ' + error.message, 'error');
                });
        }

        // Fonction pour créer le PDF avec jsPDF
        function createPDFWithStudents(studentsData) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Variables pour le design
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            let yPosition = 20;
            
            // Header avec design jaune
            doc.setFillColor(255, 193, 7); // Jaune
            doc.rect(0, 0, pageWidth, 60, 'F');
            
            // Titre
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(24);
            doc.setFont(undefined, 'bold');
            doc.text('RAPPORT DES ÉTUDIANTS', pageWidth / 2, 25, { align: 'center' });
            
            // Sous-titre
            doc.setFontSize(12);
            doc.setFont(undefined, 'normal');
            doc.text('TAAJ Corp - Système de Gestion Académique', pageWidth / 2, 35, { align: 'center' });
            
            // Date
            doc.text('Généré le: ' + new Date().toLocaleDateString('fr-FR'), pageWidth / 2, 45, { align: 'center' });
            
            // Statistiques
            yPosition = 80;
            doc.setFillColor(255, 235, 156); // Jaune clair
            doc.rect(15, yPosition - 10, pageWidth - 30, 30, 'F');
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('Statistiques:', 20, yPosition);
            
            doc.setFontSize(11);
            doc.setFont(undefined, 'normal');
            doc.text(`Total: ${studentsData.length} étudiants`, 20, yPosition + 10);
            doc.text(`Actifs: ${studentsData.filter(s => s.status === 'active').length}`, 80, yPosition + 10);
            doc.text(`En attente: ${studentsData.filter(s => s.status === 'pending').length}`, 140, yPosition + 10);
            
            yPosition += 40;
            
            // En-têtes du tableau
            doc.setFillColor(255, 193, 7); // Jaune
            doc.rect(15, yPosition - 5, pageWidth - 30, 10, 'F');
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.text('Nom', 20, yPosition);
            doc.text('Email', 60, yPosition);
            doc.text('Programme', 120, yPosition);
            doc.text('Statut', 170, yPosition);
            
            yPosition += 10;
            
            // Données des étudiants
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            
            studentsData.forEach((student, index) => {
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
                    doc.text('Nom', 20, yPosition);
                    doc.text('Email', 60, yPosition);
                    doc.text('Programme', 120, yPosition);
                    doc.text('Statut', 170, yPosition);
                    yPosition += 10;
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
                
                // Données de l'étudiant
                const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim();
                const email = student.email || 'N/A';
                const program = student.program_name || 'N/A';
                const status = student.status || 'N/A';
                
                doc.text(fullName.substring(0, 25), 20, yPosition + 5);
                doc.text(email.substring(0, 25), 60, yPosition + 5);
                doc.text(program.substring(0, 25), 120, yPosition + 5);
                
                // Statut avec couleur
                if (status === 'active') {
                    doc.setTextColor(0, 128, 0);
                    doc.text('Actif', 170, yPosition + 5);
                } else if (status === 'pending') {
                    doc.setTextColor(255, 140, 0);
                    doc.text('En attente', 170, yPosition + 5);
                } else {
                    doc.setTextColor(255, 0, 0);
                    doc.text('Inactif', 170, yPosition + 5);
                }
                doc.setTextColor(0, 0, 0);
                
                yPosition += 12;
            });
            
            // Footer
            const footerY = pageHeight - 20;
            doc.setFillColor(255, 193, 7); // Jaune
            doc.rect(0, footerY - 10, pageWidth, 20, 'F');
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            doc.text('TAAJ Corp - Système de Gestion Académique', pageWidth / 2, footerY, { align: 'center' });
            doc.text('Page ' + doc.internal.getNumberOfPages(), pageWidth / 2, footerY + 8, { align: 'center' });
            
            // Télécharger le PDF
            const fileName = `rapport_etudiants_${new Date().toISOString().slice(0, 10)}.pdf`;
            doc.save(fileName);
            
            showNotification('✅ PDF généré et téléchargé avec succès!', 'success');
        }

        // Fonction pour exporter les étudiants (CSV) avec téléchargement direct
        function exportStudents() {
            showNotification('📊 Export CSV en cours...', 'success');
            
            // Récupérer les données et générer le CSV directement
            fetch('api_crud_students.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        downloadCSVFile(data.students, 'etudiants');
                    } else {
                        showNotification('❌ Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('❌ Erreur: ' + error.message, 'error');
                });
        }

        // Fonction pour télécharger un fichier CSV directement
        function downloadCSVFile(data, filename) {
            // Créer le contenu CSV
            let csvContent = '\uFEFF'; // BOM pour UTF-8
            csvContent += 'Nom,Email,Téléphone,Programme,Niveau,Date de naissance,Statut,Carte étudiant\n';
            
            data.forEach(student => {
                const row = [
                    `"${student.first_name || ''} ${student.last_name || ''}"`,
                    `"${student.email || ''}"`,
                    `"${student.phone || ''}"`,
                    `"${student.program_name || ''}"`,
                    `"${student.level || ''}"`,
                    `"${student.date_of_birth || ''}"`,
                    `"${student.status || ''}"`,
                    `"${student.student_id_card || ''}"`
                ];
                csvContent += row.join(',') + '\n';
            });
            
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

        // Fonction pour générer un PDF moderne des étudiants avec style différent
        function generateModernStudentsPDF() {
            showNotification('📄 Génération du PDF moderne en cours...', 'success');
            
            // Récupérer les données des étudiants
            fetch('api_crud_students.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        createModernPDF(data.students);
                    } else {
                        showNotification('❌ Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('❌ Erreur: ' + error.message, 'error');
                });
        }

        // Fonction pour créer un PDF avec style épuré et moins de jaune
        function createModernPDF(studentsData) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Variables pour le design
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            let yPosition = 20;
            
            // Header épuré avec juste une ligne jaune
            doc.setDrawColor(255, 193, 7);
            doc.setLineWidth(3);
            doc.line(15, 25, pageWidth - 15, 25);
            
            // Titre épuré
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(24);
            doc.setFont(undefined, 'bold');
            doc.text('LISTE DES ÉTUDIANTS', pageWidth / 2, 40, { align: 'center' });
            
            // Sous-titre simple
            doc.setFontSize(12);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(100, 100, 100);
            doc.text('TAAJ Corp - Système de Gestion Académique', pageWidth / 2, 50, { align: 'center' });
            doc.text('Généré le: ' + new Date().toLocaleDateString('fr-FR'), pageWidth / 2, 58, { align: 'center' });
            
            // Ligne de séparation
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(1);
            doc.line(15, 70, pageWidth - 15, 70);
            
            yPosition = 85;
            
            // Section statistique épurée
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('Vue d\'ensemble', 15, yPosition);
            
            yPosition += 15;
            doc.setFontSize(11);
            doc.setFont(undefined, 'normal');
            
            const totalStudents = studentsData.length;
            const activeStudents = studentsData.filter(s => s.status === 'active').length;
            const pendingStudents = studentsData.filter(s => s.status === 'pending').length;
            const inactiveStudents = studentsData.filter(s => s.status === 'inactive').length;
            
            // Statistiques sur une ligne
            doc.text(`Total: ${totalStudents} | Actifs: ${activeStudents} | En attente: ${pendingStudents} | Inactifs: ${inactiveStudents}`, 15, yPosition);
            
            yPosition += 20;
            
            // Ligne de séparation avant tableau
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(1);
            doc.line(15, yPosition, pageWidth - 15, yPosition);
            
            yPosition += 10;
            
            // Tableau épuré sans arrondis
            const headers = ['Étudiant', 'Email', 'Programme', 'Statut'];
            const colWidths = [50, 55, 55, 30]; // Largeurs ajustées pour total 190px
            const startX = 15;
            const tableWidth = pageWidth - 30; // Largeur totale disponible
            
            // Header du tableau - fond jaune très subtil
            doc.setFillColor(255, 253, 235); // Jaune ultra-clair
            doc.rect(startX, yPosition - 5, tableWidth, 10, 'F');
            
            // Bordure header
            doc.setDrawColor(255, 193, 7);
            doc.setLineWidth(1);
            doc.rect(startX, yPosition - 5, tableWidth, 10);
            
            // Texte header
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(11);
            doc.setFont(undefined, 'bold');
            
            let currentX = startX;
            headers.forEach((header, index) => {
                const colWidth = (colWidths[index] / 190) * tableWidth; // Proportionnel
                doc.text(header, currentX + 3, yPosition + 3);
                currentX += colWidth;
            });
            
            yPosition += 15;
            
            // Ligne après header
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(1);
            doc.line(startX, yPosition - 5, startX + tableWidth, yPosition - 5);
            
            // Données du tableau
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(9); // Réduire la police pour plus d'espace
            doc.setFont(undefined, 'normal');
            
            studentsData.forEach((student, index) => {
                // Vérifier si on a besoin d'une nouvelle page
                if (yPosition > pageHeight - 50) {
                    doc.addPage();
                    yPosition = 30;
                    
                    // Répéter l'en-tête sur la nouvelle page
                    doc.setFillColor(255, 253, 235);
                    doc.rect(startX, yPosition - 5, tableWidth, 10, 'F');
                    doc.setDrawColor(255, 193, 7);
                    doc.rect(startX, yPosition - 5, tableWidth, 10);
                    
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'bold');
                    
                    currentX = startX;
                    headers.forEach((header, i) => {
                        const colWidth = (colWidths[i] / 190) * tableWidth;
                        doc.text(header, currentX + 3, yPosition + 3);
                        currentX += colWidth;
                    });
                    
                    yPosition += 15;
                    doc.setDrawColor(200, 200, 200);
                    doc.line(startX, yPosition - 5, startX + tableWidth, yPosition - 5);
                    
                    doc.setFontSize(9);
                    doc.setFont(undefined, 'normal');
                }
                
                // Ligne de séparation subtile
                if (index % 2 === 0) {
                    doc.setFillColor(245, 245, 245); // Gris très clair
                    doc.rect(startX, yPosition - 3, tableWidth, 8, 'F');
                }
                
                // Bordure de la ligne
                doc.setDrawColor(230, 230, 230);
                doc.setLineWidth(0.5);
                doc.rect(startX, yPosition - 3, tableWidth, 8);
                
                // Données de l'étudiant
                const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim();
                const email = student.email || 'N/A';
                const program = student.program_name || 'N/A';
                const status = student.status || 'N/A';
                
                currentX = startX;
                
                // Colonne Étudiant (50px proportionnel)
                let colWidth = (colWidths[0] / 190) * tableWidth;
                doc.text(fullName.substring(0, 12), currentX + 2, yPosition + 3); // Moins de caractères
                currentX += colWidth;
                
                // Colonne Email (55px proportionnel)
                colWidth = (colWidths[1] / 190) * tableWidth;
                doc.text(email.substring(0, 18), currentX + 2, yPosition + 3); // Moins de caractères
                currentX += colWidth;
                
                // Colonne Programme (55px proportionnel)
                colWidth = (colWidths[2] / 190) * tableWidth;
                doc.text(program.substring(0, 18), currentX + 2, yPosition + 3); // Moins de caractères
                currentX += colWidth;
                
                // Colonne Statut (30px proportionnel)
                colWidth = (colWidths[3] / 190) * tableWidth;
                if (status === 'active') {
                    doc.setTextColor(34, 197, 94);
                    doc.text('Actif', currentX + 2, yPosition + 3);
                } else if (status === 'pending') {
                    doc.setTextColor(251, 146, 60);
                    doc.text('Att.', currentX + 2, yPosition + 3); // Abrégé
                } else {
                    doc.setTextColor(239, 68, 68);
                    doc.text('Inact.', currentX + 2, yPosition + 3); // Abrégé
                }
                doc.setTextColor(0, 0, 0);
                
                yPosition += 10; // Moins d'espace entre lignes
            });
            
            // Ligne de fin de tableau
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(1);
            doc.line(startX, yPosition, startX + tableWidth, yPosition);
            
            // Footer épuré
            const footerY = pageHeight - 30;
            
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(1);
            doc.line(15, footerY - 10, pageWidth - 15, footerY - 10);
            
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text('© 2024 TAAJ Corp - Document généré automatiquement', pageWidth / 2, footerY, { align: 'center' });
            doc.text(`Page ${doc.internal.getNumberOfPages()}`, pageWidth / 2, footerY + 8, { align: 'center' });
            
            // Télécharger le PDF
            const fileName = `liste_etudiants_${new Date().toISOString().slice(0, 10)}.pdf`;
            doc.save(fileName);
            
            showNotification('✅ PDF généré et téléchargé avec succès!', 'success');
        }

        // Charger les étudiants au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadStudents();
        });
    </script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script src="assets/js/sync_programs.js"></script>
</body>
</html>
