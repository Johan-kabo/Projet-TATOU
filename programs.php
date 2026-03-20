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

// Récupérer la liste des programmes
try {
    $stmt = $pdo->query("SELECT * FROM programs ORDER BY name");
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $programs = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Gestion des Programmes</title>
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

  /* ── COMPACT BUTTONS ── */
  .btn-outline {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 6px; height: 36px; padding: 8px 12px;
    background: var(--card-bg); color: var(--text-primary);
    border: 1px solid var(--border); border-radius: 6px;
    font-size: 12px; font-weight: 500; font-family: var(--font);
    cursor: pointer; transition: all 0.2s ease; white-space: nowrap;
  }
  .btn-outline:hover:not(:disabled) {
    background: var(--accent); color: white; border-color: var(--accent);
  }
  .btn-outline:active:not(:disabled) {
    transform: scale(0.95);
  }
  .btn-outline:disabled {
    opacity: 0.4; cursor: not-allowed;
  }
  .btn-outline svg {
    width: 12px; height: 12px;
    flex-shrink: 0;
  }

  /* ── VIEW TOGGLE ── */
  .view-toggle {
    display: flex; gap: 8px; margin-bottom: 18px;
  }
  .toggle-btn {
    padding: 8px 14px; border: 1px solid var(--border);
    background: #fff; border-radius: 8px;
    font-size: 13px; font-weight: 500; color: var(--text-muted);
    cursor: pointer; transition: all 0.15s;
  }
  .toggle-btn.active {
    background: var(--accent); color: #fff; border-color: var(--accent);
  }
  .toggle-btn:hover:not(.active) { background: var(--page-bg); }

  /* ── PROGRAM CARDS ── */
  .programs-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px; animation: fadeUp 0.4s ease both;
  }
  @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

  .program-card {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 14px; overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
  }
  .program-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }

  .program-header {
    padding: 20px; border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #F8FAFC, #F1F5F9);
  }
  .program-title {
    font-size: 18px; font-weight: 700; color: var(--text-primary);
    margin-bottom: 6px;
  }
  .program-meta {
    display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--text-muted);
  }
  .meta-item {
    display: flex; align-items: center; gap: 5px;
  }
  .meta-dot {
    width: 6px; height: 6px; border-radius: 50%;
  }
  .dot-active { background: var(--green); }
  .dot-inactive { background: var(--red); }

  .program-body {
    padding: 20px;
  }
  .program-description {
    font-size: 14px; line-height: 1.6; color: var(--text-muted);
    margin-bottom: 16px;
  }
  .program-stats {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
  }
  .stat-item {
    background: var(--page-bg); padding: 12px; border-radius: 8px;
    text-align: center;
  }
  .stat-value {
    font-size: 20px; font-weight: 700; color: var(--text-primary);
    margin-bottom: 2px;
  }
  .stat-label {
    font-size: 12px; color: var(--text-muted); font-weight: 500;
  }

  .program-footer {
    padding: 16px 20px; border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
  }
  .program-actions {
    display: flex; gap: 8px;
  }
  .action-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
    background: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s, border-color 0.15s; color: var(--text-muted);
  }
  .action-btn:hover { background: var(--page-bg); }
  .action-btn.edit:hover { border-color: var(--accent); color: var(--accent); }
  .action-btn.delete:hover { border-color: var(--red); color: var(--red); }

  .program-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
  }
  .badge-active { background: #ECFDF5; color: #065F46; }
  .badge-inactive { background: #FEF2F2; color: #991B1B; }

  /* ── LIST VIEW ── */
  .programs-list {
    display: none; background: var(--card-bg);
    border: 1px solid var(--border); border-radius: 14px;
    overflow: hidden; animation: fadeUp 0.4s ease both;
  }
  .programs-list.open { display: block; }

  .list-item {
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    transition: background 0.15s;
  }
  .list-item:last-child { border-bottom: none; }
  .list-item:hover { background: var(--page-bg); }

  .list-info {
    flex: 1; display: flex; align-items: center; gap: 16px;
  }
  .list-title {
    font-size: 15px; font-weight: 600; color: var(--text-primary);
  }
  .list-meta {
    font-size: 13px; color: var(--text-muted);
  }

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

  /* ── RESPONSIVE ── */
  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main { margin-left: 0; }
    .programs-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: stretch; }
    .topbar { padding: 8px 16px; }
    .content { padding: 16px; }
  }
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
      <a href="programs.php" class="nav-item active">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2  0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input placeholder="Rechercher un programme..." />
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
          <div class="page-title">Gestion des Programmes</div>
          <div class="page-sub">Gérez les programmes académiques et les cursus proposés.</div>
        </div>
        <div style="display: flex; gap: 10px;">
          <button class="btn-outline" onclick="exportPrograms()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter
          </button>
          <button class="btn-outline" onclick="generateProgramsPDF()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            PDF
          </button>
          <button class="btn-primary" onclick="openModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouveau Programme
          </button>
        </div>
      </div>

      <!-- VIEW TOGGLE -->
      <div class="view-toggle">
        <button class="toggle-btn active" onclick="setView('grid', this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Grille
        </button>
        <button class="toggle-btn" onclick="setView('list', this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          Liste
        </button>
      </div>

      <!-- PROGRAMS GRID -->
      <div class="programs-grid" id="programsGrid">
        <?php foreach ($programs as $program): ?>
        <div class="program-card" onclick="viewProgram(<?php echo $program['id']; ?>)">
          <div class="program-header">
            <div class="program-title"><?php echo htmlspecialchars($program['name']); ?></div>
            <div class="program-meta">
              <div class="meta-item">
                <div class="meta-dot <?php echo $program['active'] ? 'dot-active' : 'dot-inactive'; ?>"></div>
                <span><?php echo $program['active'] ? 'Actif' : 'Inactif'; ?></span>
              </div>
              <span class="meta-item"><?php echo htmlspecialchars($program['level'] ?? 'L1-L3'); ?></span>
            </div>
          </div>
          <div class="program-body">
            <div class="program-description">
              <?php echo htmlspecialchars($program['description'] ?? 'Programme académique complet avec des cours théoriques et pratiques.'); ?>
            </div>
            <div class="program-stats">
              <div class="stat-item">
                <div class="stat-value"><?php echo rand(45, 120); ?></div>
                <div class="stat-label">Étudiants</div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?php echo rand(12, 48); ?></div>
                <div class="stat-label">Cours</div>
              </div>
            </div>
          </div>
          <div class="program-footer">
            <span class="program-badge <?php echo $program['active'] ? 'badge-active' : 'badge-inactive'; ?>">
              <?php echo $program['active'] ? 'Actif' : 'Inactif'; ?>
            </span>
            <div class="program-actions">
              <button class="action-btn edit" title="Modifier" onclick="event.stopPropagation(); editProgram(<?php echo $program['id']; ?>)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button class="action-btn delete" title="Supprimer" onclick="event.stopPropagation(); deleteProgram(<?php echo $program['id']; ?>)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- PROGRAMS LIST -->
      <div class="programs-list" id="programsList">
        <?php foreach ($programs as $program): ?>
        <div class="list-item">
          <div class="list-info">
            <div>
              <div class="list-title"><?php echo htmlspecialchars($program['name']); ?></div>
              <div class="list-meta">
                <span class="meta-item">
                  <div class="meta-dot <?php echo $program['active'] ? 'dot-active' : 'dot-inactive'; ?>"></div>
                  <?php echo $program['active'] ? 'Actif' : 'Inactif'; ?>
                </span>
                • <?php echo htmlspecialchars($program['level'] ?? 'L1-L3'); ?>
              </div>
            </div>
            <span class="program-badge <?php echo $program['active'] ? 'badge-active' : 'badge-inactive'; ?>">
              <?php echo $program['active'] ? 'Actif' : 'Inactif'; ?>
            </span>
          </div>
          <div class="program-actions">
            <button class="action-btn edit" title="Modifier" onclick="editProgram(<?php echo $program['id']; ?>)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="action-btn delete" title="Supprimer" onclick="deleteProgram(<?php echo $program['id']; ?>)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Nouveau Programme -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Ajouter un Programme</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nom du Programme</label>
        <input class="form-input" placeholder="Ex: Licence en Informatique" />
      </div>
      <div class="form-group">
        <label class="form-label">Niveau</label>
        <select class="form-select">
          <option value="">Sélectionner...</option>
          <option>Licence</option>
          <option>Master</option>
          <option>Doctorat</option>
          <option>Certificat</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-textarea" placeholder="Description détaillée du programme..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Durée (en années)</label>
        <input class="form-input" type="number" placeholder="3" min="1" max="10" />
      </div>
      <div class="form-group">
        <label class="form-label">Statut</label>
        <select class="form-select">
          <option value="1">Actif</option>
          <option value="0">Inactif</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="btn-primary" onclick="saveProgram()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
      </button>
    </div>
  </div>
</div>

<script>
function setView(view, btn) {
  document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  
  const grid = document.getElementById('programsGrid');
  const list = document.getElementById('programsList');
  
  if (view === 'grid') {
    grid.style.display = 'grid';
    list.classList.remove('open');
  } else {
    grid.style.display = 'none';
    list.classList.add('open');
  }
}

function openModal() {
  document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

function handleOverlayClick(event) {
  if (event.target === document.getElementById('modalOverlay')) {
    closeModal();
  }
}

function viewProgram(id) {
  alert(`Voir les détails du programme ID: ${id}`);
}

function editProgram(id) {
  alert(`Modifier le programme ID: ${id}`);
  openModal();
}

function deleteProgram(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce programme ?')) {
    alert(`Programme ${id} supprimé avec succès !`);
    // Ici, vous pourriez faire un appel AJAX pour supprimer le programme
    location.reload();
  }
}

function saveProgram() {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  // Récupérer les valeurs du formulaire
  const name = document.querySelector('input[placeholder="Nom du programme"]').value.trim();
  const level = document.querySelector('select').value;
  const description = document.querySelector('textarea').value.trim();
  const duration = document.querySelector('input[placeholder="Durée"]').value;
  const active = document.querySelector('select').value;
  
  // Validation simple
  if (!name || !level || !description || !duration) {
    btn.style.background = '#EF4444';
    btn.innerHTML = '⚠️ Veuillez remplir tous les champs obligatoires';
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
  fetch('save_program.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      name: name,
      level: level,
      description: description,
      duration: duration,
      active: active
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      btn.style.background = '#10B981';
      btn.innerHTML = '✅ Programme ajouté avec succès !';
      
      // Ajouter le nouveau programme à la liste locale
      const newProgram = {
        id: data.program_id,
        name: name,
        level: level,
        duration: duration + ' ans',
        active: active
      };
      programs.unshift(newProgram);
      renderPrograms();
      
      // Fermer le modal et réinitialiser le formulaire
      setTimeout(() => {
        closeModal();
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        
        // Réinitialiser le formulaire
        document.querySelector('input[placeholder="Nom du programme"]').value = '';
        document.querySelector('select').value = '';
        document.querySelector('textarea').value = '';
        document.querySelector('input[placeholder="Durée"]').value = '';
        document.querySelector('select').value = '';
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

// Fonction pour exporter les programmes en CSV
function exportPrograms() {
  const cards = document.querySelectorAll('.program-card');
  const data = [['Nom', 'Statut', 'Niveau', 'Étudiants', 'Cours']];
  
  cards.forEach(card => {
    const name = card.querySelector('.program-title')?.textContent || '';
    const status = card.querySelector('.meta-item')?.textContent || '';
    const level = card.querySelectorAll('.meta-item')[1]?.textContent || '';
    const students = card.querySelector('.stat-value')?.textContent || '';
    const courses = card.querySelectorAll('.stat-value')[1]?.textContent || '';
    
    data.push([name, status, level, students, courses]);
  });
  
  let csvContent = "data:text/csv;charset=utf-8,";
  data.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'export_programs_taatj.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  alert('Liste des programmes exportée avec succès !');
}

// Fonction pour générer un PDF des programmes
function generateProgramsPDF() {
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
  doc.text('Catalogue des Programmes', 70, 25);
  
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
  doc.text('Statistiques des Programmes:', 20, 60);
  
  doc.setFontSize(11);
  doc.setFont(undefined, 'normal');
  
  const totalPrograms = programs.length;
  const activePrograms = programs.filter(p => p.active).length;
  const totalStudents = programs.reduce((sum, p) => sum + p.students, 0);
  
  const stats = [
    ['Total programmes', totalPrograms],
    ['Programmes actifs', activePrograms],
    ['Total étudiants', totalStudents],
    ['Moyenne étudiants/programme', totalPrograms > 0 ? Math.round(totalStudents / totalPrograms) : 0]
  ];
  
  let yPos = 70;
  stats.forEach(([label, value]) => {
    doc.text(`${label}:`, 30, yPos);
    doc.setFont(undefined, 'bold');
    doc.text(`${value}`, 110, yPos);
    doc.setFont(undefined, 'normal');
    yPos += 8;
  });
  
  // Ligne de séparation
  doc.setDrawColor(200, 200, 200);
  doc.line(20, yPos + 5, 190, yPos + 5);
  yPos += 15;
  
  // Tableau des programmes
  doc.setFontSize(12);
  doc.setFont(undefined, 'bold');
  doc.text('Catalogue Détaillé des Programmes:', 20, yPos);
  yPos += 15;
  
  // En-têtes de tableau
  const headers = ['#', 'Nom du Programme', 'Code', 'Niveau', 'Durée', 'Capacité', 'Étudiants', 'Statut'];
  const headerWidths = [8, 50, 25, 20, 20, 20, 20, 17];
  
  // Fond d'en-tête
  doc.setFillColor(240, 240, 240);
  doc.rect(20, yPos, 170, 10, 'F');
  
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(8);
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
  
  // Données des programmes
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(7);
  doc.setFont(undefined, 'normal');
  
  programs.forEach((program, index) => {
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
      doc.text('Catalogue des Programmes (Suite)', 70, 25);
      
      // En-têtes de tableau sur nouvelle page
      doc.setFillColor(240, 240, 240);
      doc.rect(20, yPos, 170, 10, 'F');
      doc.setTextColor(0, 0, 0);
      doc.setFontSize(8);
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
      doc.setFontSize(7);
      doc.setFont(undefined, 'normal');
    }
    
    // Ligne horizontale
    doc.setDrawColor(200, 200, 200);
    doc.line(20, yPos, 190, yPos);
    
    // Données du programme
    const rowData = [
      (index + 1).toString(),
      program.name,
      program.code,
      program.level,
      program.duration,
      program.capacity.toString(),
      program.students.toString(),
      program.active ? 'Actif' : 'Inactif'
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
  
  // Sauvegarder le PDF
  doc.save('catalogue_programmes_taatj.pdf');
  
  // Message de succès
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed; top: 20px; right: 20px; 
    background: #10B981; color: white; padding: 15px 20px; 
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000; font-weight: 600; font-size: 14px;
  `;
  notification.textContent = '✅ PDF généré avec succès !';
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.transition = 'opacity 0.3s ease-out';
    notification.style.opacity = '0';
    setTimeout(() => document.body.removeChild(notification), 300);
  }, 3000);
}

// Charger les programmes au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  loadPrograms();
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
