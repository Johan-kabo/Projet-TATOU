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

// Récupérer les statistiques des inscriptions
try {
    // Total inscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
    $totalRegistrations = $stmt->fetch()['total'];

    // Inscriptions payées
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'paid'");
    $paidRegistrations = $stmt->fetch()['total'];

    // Inscriptions en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'pending'");
    $pendingRegistrations = $stmt->fetch()['total'];

    // Inscriptions non payées
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'unpaid'");
    $unpaidRegistrations = $stmt->fetch()['total'];

    // Récupérer la liste des inscriptions
    $stmt = $pdo->query("
        SELECT r.*, s.first_name, s.last_name, s.email, p.name as program_name, p.level
        FROM registrations r
        LEFT JOIN students s ON r.student_id = s.id
        LEFT JOIN programs p ON r.program_id = p.id
        ORDER BY r.created_at DESC
        LIMIT 12
    ");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $totalRegistrations = 12;
    $paidRegistrations = 9;
    $pendingRegistrations = 2;
    $unpaidRegistrations = 1;
    $registrations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Suivi des Inscriptions</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
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
    --green: #10B981; --red: #EF4444; --blue: #3B82F6;
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
  .btn-primary { display:flex; align-items:center; gap:7px; background:var(--accent); color:#fff; border:none; border-radius:10px; padding:10px 18px; font-size:13px; font-weight:600; font-family:var(--font); cursor:pointer; transition:background .18s; white-space:nowrap; }
  .btn-primary:hover { background:var(--accent-hover); }

  /* STATS */
  .stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-chip { background:#fff; border:1px solid var(--border); border-radius:10px; padding:10px 18px; display:flex; align-items:center; gap:10px; animation:fadeUp .35s ease both; }
  .stat-chip:nth-child(1){animation-delay:.04s}.stat-chip:nth-child(2){animation-delay:.08s}.stat-chip:nth-child(3){animation-delay:.12s}.stat-chip:nth-child(4){animation-delay:.16s}
  .chip-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
  .chip-val { font-size:20px; font-weight:800; color:var(--text-primary); }
  .chip-lbl { font-size:12px; color:var(--text-muted); font-weight:500; }

  /* LAYOUT */
  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:18px; align-items:start; }

  /* LEFT PANEL */
  .list-panel { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; animation:fadeUp .4s .1s ease both; }

  .list-toolbar { display:flex; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
  .list-search { display:flex; align-items:center; gap:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:9px; padding:8px 14px; flex:1; max-width:320px; }
  .list-search input { border:none; background:transparent; outline:none; font-family:var(--font); font-size:13px; color:var(--text-primary); width:100%; }
  .list-search input::placeholder { color:var(--text-muted); }
  .filter-tabs { display:flex; gap:4px; margin-left:auto; }
  .tab-btn { border:1px solid var(--border); background:#fff; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:600; font-family:var(--font); color:var(--text-muted); cursor:pointer; transition:all .15s; }
  .tab-btn:hover { background:var(--page-bg); }
  .tab-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }

  /* INSCRIPTION ITEM */
  .insc-item { display:flex; align-items:center; gap:14px; padding:14px 18px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .14s; }
  .insc-item:last-child { border-bottom:none; }
  .insc-item:hover { background:#FAFBFC; }
  .insc-item.selected { background:#FFFBEB; border-left:3px solid var(--accent); }

  .insc-icon { width:40px; height:40px; border-radius:11px; background:#ECFDF5; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .insc-icon.pending { background:#FFFBEB; }
  .insc-icon.inactive { background:#FEF2F2; }

  .insc-info { flex:1; min-width:0; }
  .insc-name { font-size:14px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:7px; }
  .insc-ref { font-size:11px; font-weight:600; color:var(--accent); background:#FFFBEB; padding:2px 7px; border-radius:5px; }
  .insc-sub { font-size:12px; color:var(--text-muted); margin-top:3px; }

  .insc-right { text-align:right; flex-shrink:0; }
  .insc-amount { font-size:14px; font-weight:700; color:var(--text-primary); }
  .insc-status { font-size:11.5px; font-weight:700; margin-top:3px; }
  .paid    { color:var(--green); }
  .pending { color:var(--accent); }
  .unpaid  { color:var(--red); }

  .arrow-btn { width:28px; height:28px; border-radius:8px; border:1px solid var(--border); background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-muted); flex-shrink:0; transition:all .15s; }
  .arrow-btn:hover { border-color:var(--accent); color:var(--accent); }

  /* PAGINATION */
  .list-footer { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-top:1px solid var(--border); font-size:12px; color:var(--text-muted); }
  .page-btns { display:flex; gap:4px; }
  .pg-btn { width:30px; height:30px; border-radius:7px; border:1px solid var(--border); background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; cursor:pointer; font-family:var(--font); color:var(--text-primary); transition:background .14s; }
  .pg-btn:hover { background:var(--page-bg); }
  .pg-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }

  /* RIGHT PANEL */
  .right-col { display:flex; flex-direction:column; gap:14px; }

  .side-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:20px; animation:fadeUp .4s .18s ease both; }

  .side-card-title { font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:16px; }

  .pay-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
  .pay-label { font-size:13px; color:var(--text-muted); font-weight:500; }
  .pay-val { font-size:14px; font-weight:800; }
  .pay-val.green { color:var(--green); }
  .pay-val.amber { color:var(--accent); }

  .progress-bar { height:10px; background:var(--border); border-radius:20px; overflow:hidden; margin:10px 0 6px; }
  .progress-fill { height:100%; border-radius:20px; background:var(--green); transition:width .6s ease; }
  .progress-label { font-size:11.5px; color:var(--text-muted); text-align:center; }

  /* RAPPEL CARD */
  .rappel-card { background:#FFFBEB; border:1.5px solid #FCD34D; border-radius:14px; padding:18px; animation:fadeUp .4s .24s ease both; }
  .rappel-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
  .rappel-title { font-size:13px; font-weight:800; color:#92400E; text-transform:uppercase; letter-spacing:0.4px; }
  .rappel-text { font-size:12.5px; color:#78350F; line-height:1.55; margin-bottom:14px; }

  /* DETAIL CARD */
  .detail-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:20px; animation:fadeUp .4s .28s ease both; }
  .detail-header { display:flex; align-items:center; gap:10px; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid var(--border); }
  .detail-av { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0; }
  .detail-name { font-size:14px; font-weight:700; color:var(--text-primary); }
  .detail-prog { font-size:12px; color:var(--text-muted); margin-top:2px; }
  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:13px; }
  .detail-row:last-child { border-bottom:none; }
  .detail-key { color:var(--text-muted); font-weight:500; }
  .detail-val { font-weight:600; color:var(--text-primary); text-align:right; }
  .detail-actions { display:flex; gap:8px; margin-top:14px; }
  .btn-approve { flex:1; background:var(--green); color:#fff; border:none; border-radius:9px; padding:9px; font-size:12.5px; font-weight:700; font-family:var(--font); cursor:pointer; transition:opacity .15s; }
  .btn-approve:hover { opacity:.85; }
  .btn-reject { flex:1; background:#FEF2F2; color:var(--red); border:1px solid #FECACA; border-radius:9px; padding:9px; font-size:12.5px; font-weight:700; font-family:var(--font); cursor:pointer; }
  .btn-reject:hover { background:#FEE2E2; }

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
      <button class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Inscriptions
      </button>
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
          <div class="page-title">Suivi des Inscriptions</div>
          <div class="page-sub">Gérez les dossiers d'inscription et les paiements.</div>
        </div>
        <div style="display: flex; gap: 10px;">
          <button class="btn-outline" onclick="exportRegistrations()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter
          </button>
          <button class="btn-outline" onclick="generateRegistrationsPDF()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            PDF
          </button>
          <button class="btn-primary" onclick="openModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouvelle Inscription
          </button>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-row">
        <div class="stat-chip">
          <div class="chip-dot" style="background:#3B82F6;"></div>
          <div><div class="chip-val"><?php echo $totalRegistrations; ?></div><div class="chip-lbl">Total inscriptions</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#10B981;"></div>
          <div><div class="chip-val"><?php echo $paidRegistrations; ?></div><div class="chip-lbl">Payées</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#F59E0B;"></div>
          <div><div class="chip-val"><?php echo $pendingRegistrations; ?></div><div class="chip-lbl">En attente</div></div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#EF4444;"></div>
          <div><div class="chip-val"><?php echo $unpaidRegistrations; ?></div><div class="chip-lbl">Non payée</div></div>
        </div>
      </div>

      <div class="main-grid">
        <!-- LEFT: LIST -->
        <div class="list-panel">
          <div class="list-toolbar">
            <div class="list-search">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input id="searchInput" placeholder="Rechercher une inscription..." oninput="filterList()" />
            </div>
            <div class="filter-tabs">
              <button class="tab-btn active" onclick="setTab('all',this)">Tous</button>
              <button class="tab-btn" onclick="setTab('paid',this)">Payés</button>
              <button class="tab-btn" onclick="setTab('pending',this)">En attente</button>
              <button class="tab-btn" onclick="setTab('unpaid',this)">Impayés</button>
            </div>
          </div>

          <div id="inscList"></div>

          <div class="list-footer">
            <span id="listInfo">Affichage 1–8 sur <?php echo $totalRegistrations; ?></span>
            <div class="page-btns">
              <button class="pg-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <button class="pg-btn active">1</button>
              <button class="pg-btn">2</button>
              <button class="pg-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT: PANELS -->
        <div class="right-col">

          <!-- RÉSUMÉ PAIEMENTS -->
          <div class="side-card">
            <div class="side-card-title">Résumé des Paiements</div>
            <div class="pay-row">
              <span class="pay-label">Total Encaissé</span>
              <span class="pay-val green">1 350 000 FCFA</span>
            </div>
            <div class="pay-row">
              <span class="pay-label">En Attente</span>
              <span class="pay-val amber">300 000 FCFA</span>
            </div>
            <div class="pay-row">
              <span class="pay-label">Non payé</span>
              <span class="pay-val" style="color:var(--red);">150 000 FCFA</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width:75%;"></div>
            </div>
            <div class="progress-label">75% des frais de scolarité réglés</div>
          </div>

          <!-- RAPPEL -->
          <div class="rappel-card">
            <div class="rappel-header">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span class="rappel-title">Rappel de Validation</span>
            </div>
            <div class="rappel-text"><?php echo $pendingRegistrations; ?> étudiants attendent la validation de leur dossier. Un dossier non validé empêche l'accès aux cours.</div>
            <button class="btn-primary" style="width:100%;justify-content:center;">Traiter les dossiers</button>
          </div>

          <!-- DÉTAIL INSCRIPTION -->
          <div class="detail-card" id="detailCard">
            <div style="font-size:13px;color:var(--text-muted);text-align:center;padding:20px 0;" id="detailEmpty">
              Sélectionnez une inscription pour voir le détail
            </div>
            <div id="detailContent" style="display:none;">
              <div class="detail-header">
                <div class="detail-av" id="detailAv"></div>
                <div>
                  <div class="detail-name" id="detailName"></div>
                  <div class="detail-prog" id="detailProg"></div>
                </div>
              </div>
              <div class="detail-row"><span class="detail-key">Référence</span><span class="detail-val" id="dRef"></span></div>
              <div class="detail-row"><span class="detail-key">Programme</span><span class="detail-val" id="dProg"></span></div>
              <div class="detail-row"><span class="detail-key">Niveau</span><span class="detail-val" id="dLevel"></span></div>
              <div class="detail-row"><span class="detail-key">Montant</span><span class="detail-val" id="dAmount"></span></div>
              <div class="detail-row"><span class="detail-key">Statut paiement</span><span class="detail-val" id="dStatus"></span></div>
              <div class="detail-row"><span class="detail-key">Date</span><span class="detail-val" id="dDate"></span></div>
              <div class="detail-actions">
                <button class="btn-approve">✓ Valider</button>
                <button class="btn-reject">✕ Rejeter</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
const COLORS = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#EC4899'];
const inscriptions = [
  { id:1,  name:"Janot NKENG",   email:"nkengjanot@gmail.com",  prog:"Gestion",      level:"L2", ref:"REG-6oMj", amount:150000, status:"paid",    date:"2026-03-17" },
let currentTab = 'all';

// Fonction pour charger les inscriptions depuis le serveur
async function loadRegistrations() {
  try {
    const response = await fetch('api_get_registrations.php');
    const data = await response.json();
    
    if (data.success) {
      inscriptions = data.registrations;
      renderList();
      updateRegistrationStatistics();
    } else {
      console.error('Erreur lors du chargement des inscriptions:', data.message);
    }
  } catch (error) {
    console.error('Erreur de chargement:', error);
  }
}

// Fonction pour mettre à jour les statistiques des inscriptions
function updateRegistrationStatistics() {
  const totalRegistrations = inscriptions.length;
  const paidRegistrations = inscriptions.filter(r => r.status === 'paid').length;
  const pendingRegistrations = inscriptions.filter(r => r.status === 'pending').length;
  const unpaidRegistrations = inscriptions.filter(r => r.status === 'unpaid').length;
  
  // Mettre à jour les KPIs
  const totalKpi = document.querySelector('.chip-val');
  const paidKpi = document.querySelectorAll('.chip-val')[1];
  const pendingKpi = document.querySelectorAll('.chip-val')[2];
  const unpaidKpi = document.querySelectorAll('.chip-val')[3];
  
  if (totalKpi) totalKpi.textContent = totalRegistrations;
  if (paidKpi) paidKpi.textContent = paidRegistrations;
  if (pendingKpi) pendingKpi.textContent = pendingRegistrations;
  if (unpaidKpi) unpaidKpi.textContent = unpaidRegistrations;
}

// Charger les inscriptions au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  loadRegistrations();
});

const COLORS = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#EC4899'];
let selectedId = null;

function getColor(name) { let h=0; for(let c of name) h+=c.charCodeAt(0); return COLORS[h%COLORS.length]; }
function getInitials(name) { return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase(); }

function statusInfo(s) {
  if(s==='paid')    return { label:'PAYÉ',        cls:'paid',    icon:'✓' };
  if(s==='pending') return { label:'EN ATTENTE',  cls:'pending', icon:'⏳' };
  return                   { label:'NON PAYÉ',    cls:'unpaid',  icon:'✕' };
}
function iconBg(s) {
  if(s==='paid')    return '#ECFDF5';
  if(s==='pending') return '#FFFBEB';
  return '#FEF2F2';
}
function iconColor(s) {
  if(s==='paid')    return '#10B981';
  if(s==='pending') return '#F59E0B';
  return '#EF4444';
}

function renderList() {
  const container = document.getElementById('inscList');
  if(filtered.length === 0) {
    container.innerHTML = `<div style="padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">Aucune inscription trouvée</div>`;
    return;
  }
  container.innerHTML = filtered.map(i => {
    const si = statusInfo(i.status);
    const sel = selectedId === i.id ? 'selected' : '';
    return `
    <div class="insc-item ${sel}" onclick="selectInsc(${i.id})">
      <div class="insc-icon ${i.status}" style="background:${iconBg(i.status)};">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${iconColor(i.status)}" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="insc-info">
        <div class="insc-name">
          ${i.name}
          <span class="insc-ref">#${i.ref}</span>
        </div>
        <div class="insc-sub">${i.prog} • ${i.date}</div>
      </div>
      <div class="insc-right">
        <div class="insc-amount">${i.amount.toLocaleString('fr-FR')} FCFA</div>
        <div class="insc-status ${si.cls}">${si.label}</div>
      </div>
      <button class="arrow-btn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>`;
  }).join('');
  document.getElementById('listInfo').textContent = `Affichage 1–${Math.min(8,filtered.length)} sur ${filtered.length}`;
}

function selectInsc(id) {
  selectedId = id;
  const i = inscriptions.find(x => x.id === id);
  const col = getColor(i.name);
  const ini = getInitials(i.name);
  const si  = statusInfo(i.status);

  document.getElementById('detailEmpty').style.display = 'none';
  document.getElementById('detailContent').style.display = 'block';
  document.getElementById('detailAv').style.cssText = `background:${col}18;color:${col};width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0;`;
  document.getElementById('detailAv').textContent = ini;
  document.getElementById('detailName').textContent = i.name;
  document.getElementById('detailProg').textContent = i.prog + ' • ' + i.level;
  document.getElementById('dRef').textContent    = '#' + i.ref;
  document.getElementById('dProg').textContent   = i.prog;
  document.getElementById('dLevel').textContent  = i.level;
  document.getElementById('dAmount').textContent = i.amount.toLocaleString('fr-FR') + ' FCFA';
  document.getElementById('dStatus').innerHTML   = `<span class="${si.cls}" style="font-weight:700;">${si.label}</span>`;
  document.getElementById('dDate').textContent   = i.date;
  renderList();
}

function setTab(tab, btn) {
  currentTab = tab;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function filterList() { applyFilters(); }

function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  filtered = inscriptions.filter(i => {
    const matchTab = currentTab === 'all' || i.status === currentTab;
    const matchQ   = i.name.toLowerCase().includes(q) || i.ref.toLowerCase().includes(q) || i.prog.toLowerCase().includes(q);
    return matchTab && matchQ;
  });
  renderList();
}

renderList();
</script>

<!-- MODAL Nouvelle Inscription -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Ajouter une Inscription</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Étudiant</label>
        <select class="form-select">
          <option value="">Sélectionner un étudiant...</option>
          <option>Janot NKENG</option>
          <option>Alex TAMO</option>
          <option>Johan Manuel</option>
          <option>Marie ONANA</option>
        </select>
      </div>
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
      <div class="form-group">
        <label class="form-label">Montant (FCFA)</label>
        <input class="form-input" type="number" placeholder="150000" min="0" />
      </div>
      <div class="form-group">
        <label class="form-label">Statut de paiement</label>
        <select class="form-select">
          <option value="paid">Payé</option>
          <option value="pending">En attente</option>
          <option value="unpaid">Non payé</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date d'inscription</label>
        <input class="form-input" type="date" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" placeholder="Notes supplémentaires sur l'inscription..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="btn-primary" onclick="saveRegistration()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
      </button>
    </div>
  </div>
</div>

<script>
function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(event) { if(event.target === document.getElementById('modalOverlay')) closeModal(); }
function saveRegistration() {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  // Récupérer les valeurs du formulaire
  const studentSelect = document.querySelector('select');
  const studentId = studentSelect.value;
  const programSelect = document.querySelectorAll('select')[1];
  const programId = programSelect.value;
  const semesterSelect = document.querySelectorAll('select')[2];
  const semester = semesterSelect.value;
  const amount = document.querySelector('input[placeholder="150000"]').value;
  const paymentMethodSelect = document.querySelectorAll('select')[3];
  const paymentMethod = paymentMethodSelect.value;
  const paymentStatusSelect = document.querySelectorAll('select')[4];
  const paymentStatus = paymentStatusSelect.value;
  const registrationDate = document.querySelector('input[type="date"]').value;
  const notes = document.querySelector('textarea').value.trim();
  
  // Validation simple
  if (!studentId || !programId || !semester || !amount || !registrationDate) {
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
  fetch('save_registration.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      student_id: studentId,
      program_id: programId,
      reference: 'REG' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
      registration_date: registrationDate,
      academic_year: '2024-2025',
      semester: semester,
      amount: amount,
      payment_method: paymentMethod,
      payment_status: paymentStatus,
      notes: notes
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      btn.style.background = '#10B981';
      btn.innerHTML = '✅ Inscription enregistrée avec succès !';
      
      // Ajouter la nouvelle inscription à la liste locale
      const newRegistration = {
        id: data.registration_id,
        name: studentSelect.options[studentSelect.selectedIndex].text,
        program: programSelect.options[programSelect.selectedIndex].text,
        amount: amount + ' FCFA',
        status: paymentStatus,
        date: new Date().toLocaleDateString('fr-FR')
      };
      inscriptions.unshift(newRegistration);
      renderList();
      
      // Fermer le modal et réinitialiser le formulaire
      setTimeout(() => {
        closeModal();
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        
        // Réinitialiser le formulaire
        studentSelect.value = '';
        programSelect.value = '';
        semesterSelect.value = '';
        document.querySelector('input[placeholder="150000"]').value = '';
        paymentMethodSelect.value = '';
        paymentStatusSelect.value = '';
        document.querySelector('input[type="date"]').value = '';
        document.querySelector('textarea').value = '';
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

// Fonction pour exporter les inscriptions en CSV
function exportRegistrations() {
  const items = document.querySelectorAll('.insc-item');
  const data = [['Nom', 'Référence', 'Programme', 'Montant', 'Statut', 'Date']];
  
  items.forEach(item => {
    const name = item.querySelector('.insc-name')?.textContent.trim() || '';
    const ref = item.querySelector('.insc-ref')?.textContent || '';
    const program = item.querySelector('.insc-sub')?.textContent.split(' • ')[0] || '';
    const amount = item.querySelector('.insc-amount')?.textContent || '';
    const status = item.querySelector('.insc-status')?.textContent || '';
    const date = item.querySelector('.insc-sub')?.textContent.split(' • ')[1] || '';
    
    data.push([name, ref, program, amount, status, date]);
  });
  
  let csvContent = "data:text/csv;charset=utf-8,";
  data.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'export_inscriptions_taatj.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  alert('Liste des inscriptions exportée avec succès !');
}

// Fonction pour générer un PDF des inscriptions
function generateRegistrationsPDF() {
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
  doc.text('Rapport des Inscriptions', 70, 25);
  
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
  doc.text('Statistiques des Paiements:', 20, 60);
  
  doc.setFontSize(11);
  doc.setFont(undefined, 'normal');
  
  const totalRegistrations = inscriptions.length;
  const paidRegistrations = inscriptions.filter(r => r.status === 'paid').length;
  const pendingRegistrations = inscriptions.filter(r => r.status === 'pending').length;
  const unpaidRegistrations = inscriptions.filter(r => r.status === 'unpaid').length;
  const totalRevenue = inscriptions
    .filter(r => r.status === 'paid')
    .reduce((sum, r) => {
      const amount = parseInt(r.amount.replace(/[^\d]/g, ''));
      return sum + amount;
    }, 0);
  
  const stats = [
    ['Total inscriptions', totalRegistrations],
    ['Payées', paidRegistrations],
    ['En attente', pendingRegistrations],
    ['Non payées', unpaidRegistrations],
    ['Revenus totaux', `${totalRevenue.toLocaleString('fr-FR')} FCFA`]
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
  
  // Tableau des inscriptions
  doc.setFontSize(12);
  doc.setFont(undefined, 'bold');
  doc.text('Liste Détaillée des Inscriptions:', 20, yPos);
  yPos += 15;
  
  // En-têtes de tableau
  const headers = ['#', 'Nom Complet', 'Référence', 'Programme', 'Montant', 'Statut', 'Date'];
  const headerWidths = [8, 45, 25, 35, 30, 20, 25];
  
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
  
  // Données des inscriptions
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(7);
  doc.setFont(undefined, 'normal');
  
  inscriptions.forEach((registration, index) => {
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
      doc.text('Rapport des Inscriptions (Suite)', 70, 25);
      
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
    
    // Données de l'inscription
    const rowData = [
      (index + 1).toString(),
      registration.name,
      registration.ref,
      registration.program,
      registration.amount,
      registration.status === 'paid' ? 'Payé' : (registration.status === 'pending' ? 'En attente' : 'Non payé'),
      registration.date
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
  doc.save('rapport_inscriptions_taatj.pdf');
  
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
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
