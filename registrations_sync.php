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
<title>TAAJ Corp – Gestion des Inscriptions</title>
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
    width: 220px; background: var(--sidebar-bg);
    display: flex; flex-direction: column;
    border-right: 1px solid rgba(255,255,255,0.08);
  }
  .logo-area { padding: 16px 14px; border-bottom: 1px solid rgba(255,255,255,0.08); }
  .logo { display: flex; align-items: center; gap: 10px; }
  .logo-icon { width:36px; height:36px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; color:#fff; }
  .logo-text { font-size:15px; font-weight:700; color:#fff; }
  .sidebar-nav { flex:1; padding:14px 10px; overflow-y:auto; }
  .nav-section-label { font-size:9.5px; font-weight:700; letter-spacing:1.2px; color:rgba(148,163,184,0.45); text-transform:uppercase; padding:10px 10px 4px; }
  .nav-item { display:flex; align-items:center; gap:9px; padding:9px 12px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:500; color:#94A3B8; transition:background .18s,color .18s; margin-bottom:1px; border:none; background:transparent; width:100%; text-align:left; font-family:var(--font); text-decoration:none; }
  .nav-item:hover { background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active { background:var(--accent); color:#fff; }

  /* ── MAIN CONTENT ── */
  .main { flex:1; display: flex; flex-direction: column; }
  .topbar { background:var(--card-bg); border-bottom:1px solid var(--border); padding:12px 24px; display:flex; align-items:center; justify-content:space-between; }
  .search-wrap { display:flex; align-items:center; gap:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
  .search-wrap svg { width:14px; height:14px; color:var(--text-muted); }
  .search-wrap input { border:none; background:transparent; outline:none; font-family:var(--font); font-size:13px; color:var(--text-primary); width:100%; }
  .search-wrap input::placeholder { color:var(--text-muted); }
  .topbar-right { margin-left:auto; display:flex; align-items:center; gap:12px; }
  .notif-btn { width:36px; height:36px; border-radius:50%; background:var(--page-bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; }
  .notif-dot { position:absolute; top:7px; right:7px; width:7px; height:7px; border-radius:50%; background:var(--red); border:2px solid #fff; }
  .user-block { display:flex; align-items:center; gap:10px; }
  .uname { font-size:13px; font-weight:600; color:var(--text-primary); }
  .uavatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#667EEA,#764BA2); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; }

  /* CONTENT */
  .content { padding:24px 26px; flex:1; }
  .page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:22px; }
  .page-title { font-size:22px; font-weight:800; }
  .page-sub { font-size:13px; color:var(--text-muted); margin-top:3px; }
  .btn-primary { display:flex; align-items:center; gap:7px; background:var(--accent); color:#fff; border:none; border-radius:10px; padding:10px 18px; font-size:13px; font-weight:600; font-family:var(--font); cursor:pointer; transition:background .18s; white-space:nowrap; }
  .btn-primary:hover { background:var(--accent-hover); }

  /* ── COMPACT BUTTONS ── */
  .btn-outline {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 6px; height: 36px; padding: 8px 12px;
    background: var(--card-bg); color: var(--text-primary);
    border: 1px solid var(--border); border-radius: 10px;
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

  /* STATS */
  .stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-chip { background:#fff; border:1px solid var(--border); border-radius:10px; padding:10px 18px; display:flex; align-items:center; gap:10px; animation:fadeUp .35s ease both; }
  .stat-chip:nth-child(1){animation-delay:.04s}.stat-chip:nth-child(2){animation-delay:.08s}.stat-chip:nth-child(3){animation-delay:.12s}.stat-chip:nth-child(4){animation-delay:.16s}
  .chip-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
  .chip-val { font-size:20px; font-weight:800; color:var(--text-primary); }
  .chip-lbl { font-size:12px; color:var(--text-muted); font-weight:500; }

  /* LAYOUT */
  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:18px; align-items:start; }

  /* LIST CARD */
  .list-card { background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; }
  .list-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
  .list-title { font-size:15px; font-weight:700; color:var(--text-primary); }
  .list-search { display:flex; align-items:center; gap:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:8px; padding:6px 10px; }
  .list-search input { border:none; background:transparent; outline:none; font-family:var(--font); font-size:13px; color:var(--text-primary); width:100%; }
  .list-search input::placeholder { color:var(--text-muted); }
  .filter-tabs { display:flex; gap:4px; margin-left:auto; }
  .tab-btn { border:1px solid var(--border); background:#fff; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:600; font-family:var(--font); color:var(--text-muted); cursor:pointer; transition:all .15s; }
  .tab-btn:hover { background:var(--page-bg); }
  .tab-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }

  /* INSCRIPTION ITEM */
  .insc-item { display:flex; align-items:center; gap:14px; padding:14px 18px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .14s; }
  .insc-item:hover { background:var(--page-bg); }
  .insc-avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
  .insc-name { font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:2px; }
  .insc-sub { font-size:12px; color:var(--text-muted); }
  .insc-amount { font-size:13px; font-weight:700; color:var(--text-primary); text-align:right; margin-bottom:2px; }
  .insc-status { font-size:11px; font-weight:600; padding:3px 8px; border-radius:12px; text-align:right; display:inline-block; }
  .paid { background:#ECFDF5; color:#10B981; }
  .pending { background:#FFFBEB; color:#F59E0B; }
  .unpaid { background:#FEF2F2; color:#EF4444; }

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

  /* SIDE CARD */
  .side-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:18px; animation:fadeUp .4s .12s ease both; }
  .side-card-title { font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:14px; }
  .pay-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
  .pay-label { font-size:12px; color:var(--text-muted); font-weight:500; }
  .pay-val { font-size:14px; font-weight:700; }
  .green { color:var(--green); }
  .amber { color:var(--accent); }
  .progress-bar { height:6px; background:var(--border); border-radius:3px; margin:12px 0; overflow:hidden; }
  .progress-fill { height:100%; background:linear-gradient(90deg,var(--green),var(--accent)); border-radius:3px; transition:width .5s ease; }
  .progress-label { font-size:11px; color:var(--text-muted); text-align:center; }

  /* RAPPEL CARD */
  .rappel-card { background:#FFFBEB; border:1.5px solid #FCD34D; border-radius:14px; padding:18px; animation:fadeUp .4s .24s ease both; }
  .rappel-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
  .rappel-title { font-size:13px; font-weight:800; color:#92400E; text-transform:uppercase; letter-spacing:0.4px; }
  .rappel-text { font-size:12.5px; color:#78350F; line-height:1.55; margin-bottom:14px; }

  /* DETAIL CARD */
  .detail-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:18px; animation:fadeUp .4s .36s ease both; }
  .detail-header { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
  .detail-av { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0; }
  .detail-name { font-size:15px; font-weight:700; color:var(--text-primary); margin-bottom:2px; }
  .detail-prog { font-size:12px; color:var(--text-muted); }
  .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
  .detail-item { display:flex; flex-direction:column; gap:4px; }
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
    <div class="logo-area">
      <div class="logo">
        <div class="logo-icon">T</div>
        <div class="logo-text">TAAJ Corp</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">MENU</div>
      <a href="dashboard.php" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </a>
      <a href="students.php" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Étudiants
      </a>
      <a href="registrations.php" class="nav-item active">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Inscriptions
      </a>
      <a href="programs.php" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Programmes
      </a>
      <a href="stats.php" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Statistiques
      </a>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input placeholder="Rechercher un étudiant, un cours..." />
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <div class="notif-dot"></div>
        </div>
        <div class="user-block">
          <div class="uavatar">JD</div>
          <div class="uname">John Doe</div>
        </div>
      </div>
    </header>

    <!-- CONTENT -->
    <section class="content">
      <div class="page-header">
        <div>
          <div class="page-title">Gestion des Inscriptions</div>
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
          <div>
            <div class="chip-val"><?php echo $totalRegistrations; ?></div>
            <div class="chip-lbl">Total</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#10B981;"></div>
          <div>
            <div class="chip-val"><?php echo $paidRegistrations; ?></div>
            <div class="chip-lbl">Payés</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#F59E0B;"></div>
          <div>
            <div class="chip-val"><?php echo $pendingRegistrations; ?></div>
            <div class="chip-lbl">En attente</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="chip-dot" style="background:#EF4444;"></div>
          <div>
            <div class="chip-val"><?php echo $unpaidRegistrations; ?></div>
            <div class="chip-lbl">Impayés</div>
          </div>
        </div>
      </div>

      <!-- MAIN GRID -->
      <div class="main-grid">
        <!-- LIST CARD -->
        <div class="list-card">
          <div class="list-header">
            <div class="list-title">Liste des Inscriptions</div>
            <div class="list-search">
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
              <span class="pay-val green">0 FCFA</span>
            </div>
            <div class="pay-row">
              <span class="pay-label">En Attente</span>
              <span class="pay-val amber">0 FCFA</span>
            </div>
            <div class="pay-row">
              <span class="pay-label">Non payé</span>
              <span class="pay-val" style="color:var(--red);">0 FCFA</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width:0%;"></div>
            </div>
            <div class="progress-label">0% des frais de scolarité réglés</div>
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
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-key">Référence</div>
                  <div class="detail-val" id="dRef"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-key">Programme</div>
                  <div class="detail-val" id="dProg"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-key">Niveau</div>
                  <div class="detail-val" id="dLevel"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-key">Montant</div>
                  <div class="detail-val" id="dAmount"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-key">Statut</div>
                  <div class="detail-val" id="dStatus"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-key">Date</div>
                  <div class="detail-val" id="dDate"></div>
                </div>
              </div>
              <div class="detail-actions">
                <button class="btn-approve">Approuver</button>
                <button class="btn-reject">Rejeter</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>
  </main>
</div>
</div>

<script>
// Variables globales
let registrations = [];

function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(event) { if(event.target === document.getElementById('modalOverlay')) closeModal(); }

// Fonction pour afficher une notification
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

// Charger les inscriptions depuis l'API
function loadRegistrations() {
  try {
    fetch('api_get_registrations.php')
      .then(response => response.json())
      .then(data => {
        console.log('Réponse loadRegistrations:', data);
        
        if (data.success) {
          registrations = data.registrations || [];
          renderList();
          updatePaymentSummary(); // Mettre à jour le résumé des paiements
          showNotification('✅ Inscriptions chargées avec succès', 'success');
        } else {
          showNotification('❌ Erreur: ' + data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Erreur de chargement:', error);
        showNotification('❌ Erreur de chargement: ' + error.message, 'error');
      });
  } catch (error) {
    console.error('Erreur dans loadRegistrations:', error);
  }
}

// Fonction pour afficher la liste des inscriptions
function renderList() {
  const container = document.getElementById('inscList');
  
  if (!container) {
    console.error('ERREUR: inscList non trouvé !');
    return;
  }
  
  if (registrations.length === 0) {
    container.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">Aucune inscription trouvée</div>';
    return;
  }

  let html = '';
  registrations.forEach(registration => {
    const statusClass = registration.status === 'paid' ? 'paid' : 
                        registration.status === 'pending' ? 'pending' : 'unpaid';
    const statusLabel = registration.status === 'paid' ? 'PAYÉ' : 
                       registration.status === 'pending' ? 'EN ATTENTE' : 'NON PAYÉ';
    
    html += '<div class="insc-item" onclick="showDetail(' + registration.id + ')">';
    html += '<div class="insc-avatar" style="background:' + getColor(registration.name) + '18;color:' + getColor(registration.name) + ';">';
    html += getInitials(registration.name);
    html += '</div>';
    html += '<div style="flex:1;">';
    html += '<div class="insc-name">' + registration.name + '</div>';
    html += '<div class="insc-sub">' + registration.ref + ' • ' + registration.date + '</div>';
    html += '</div>';
    html += '<div style="text-align:right;">';
    html += '<div class="insc-amount">' + registration.amount + '</div>';
    html += '<span class="insc-status ' + statusClass + '">' + statusLabel + '</span>';
    html += '</div>';
    html += '</div>';
  });

  container.innerHTML = html;
  
  // Mettre à jour le résumé des paiements
  updatePaymentSummary();
}

// Fonction pour mettre à jour le résumé des paiements
function updatePaymentSummary() {
  if (!registrations || registrations.length === 0) {
    // Valeurs par défaut si aucune inscription
    document.querySelector('.pay-val.green').textContent = '0 FCFA';
    document.querySelector('.pay-val.amber').textContent = '0 FCFA';
    document.querySelector('.pay-val[style*="color:var(--red)"]').textContent = '0 FCFA';
    document.querySelector('.progress-fill').style.width = '0%';
    document.querySelector('.progress-label').textContent = '0% des frais de scolarité réglés';
    return;
  }
  
  // Calculer les montants réels
  let totalPaid = 0;
  let totalPending = 0;
  let totalUnpaid = 0;
  
  registrations.forEach(registration => {
    const amount = parseFloat(registration.amount) || 0;
    switch (registration.status) {
      case 'paid':
        totalPaid += amount;
        break;
      case 'pending':
        totalPending += amount;
        break;
      case 'unpaid':
        totalUnpaid += amount;
        break;
    }
  });
  
  // Formater les montants
  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
  };
  
  // Mettre à jour l'affichage
  document.querySelector('.pay-val.green').textContent = formatAmount(totalPaid);
  document.querySelector('.pay-val.amber').textContent = formatAmount(totalPending);
  document.querySelector('.pay-val[style*="color:var(--red)"]').textContent = formatAmount(totalUnpaid);
  
  // Calculer et mettre à jour la progression
  const totalAmount = totalPaid + totalPending + totalUnpaid;
  const percentage = totalAmount > 0 ? Math.round((totalPaid / totalAmount) * 100) : 0;
  document.querySelector('.progress-fill').style.width = percentage + '%';
  document.querySelector('.progress-label').textContent = percentage + '% des frais de scolarité réglés';
}

// Fonctions utilitaires
const COLORS = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#EC4899'];

function getColor(name) { 
  let h=0; 
  for(let c of name) h+=c.charCodeAt(0); 
  return COLORS[h%COLORS.length]; 
}

function getInitials(name) { 
  return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase(); 
}

function showDetail(id) {
  const registration = registrations.find(r => r.id === id);
  if (!registration) return;
  
  // Mettre à jour le panneau de détails
  document.getElementById('detailEmpty').style.display = 'none';
  document.getElementById('detailContent').style.display = 'block';
  
  document.getElementById('detailAv').textContent = getInitials(registration.name);
  document.getElementById('detailAv').style.background = getColor(registration.name) + '18';
  document.getElementById('detailAv').style.color = getColor(registration.name);
  
  document.getElementById('detailName').textContent = registration.name;
  document.getElementById('detailProg').textContent = registration.ref + ' • ' + registration.program;
  document.getElementById('dAmount').textContent = registration.amount;
  document.getElementById('dStatus').innerHTML = '<span class="insc-status ' + registration.status + '">' + 
    (registration.status === 'paid' ? 'PAYÉ' : registration.status === 'pending' ? 'EN ATTENTE' : 'NON PAYÉ') + '</span>';
  document.getElementById('dDate').textContent = registration.date;
}

function saveRegistration() {
  const btn = document.querySelector('.btn-primary');
  const originalText = btn.innerHTML;
  
  try {
    // Récupérer les valeurs du formulaire par ID
    const studentId = document.getElementById('studentId')?.value || '';
    const programId = document.getElementById('programId')?.value || '';
    const amount = document.getElementById('amount')?.value || '';
    const paymentStatus = document.getElementById('paymentStatus')?.value || '';
    const registrationDate = document.getElementById('registrationDate')?.value || '';
    const notes = document.getElementById('notes')?.value?.trim() || '';
    
    console.log('=== SAVE REGISTRATION DEBUG ===');
    console.log('studentId:', studentId);
    console.log('programId:', programId);
    console.log('amount:', amount);
    console.log('paymentStatus:', paymentStatus);
    console.log('registrationDate:', registrationDate);
    console.log('notes:', notes);
    console.log('=== END DEBUG ===');
    
    // Validation des champs obligatoires
    if (!studentId || !programId || !amount || !registrationDate) {
      showNotification('⚠️ Veuillez remplir tous les champs obligatoires', 'error');
      return;
    }
    
    // Validation du montant
    if (amount <= 0) {
      showNotification('⚠️ Veuillez entrer un montant valide', 'error');
      return;
    }
    
    // Désactiver le bouton et montrer le chargement
    btn.disabled = true;
    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Enregistrement...';
    
    // Envoyer les données au serveur
    fetch('save_registration_final.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        student_id: studentId,
        program_id: programId,
        amount: amount,
        payment_status: paymentStatus,
        registration_date: registrationDate,
        academic_year: '2024-2025',
        notes: notes
      })
    })
    .then(response => response.json())
    .then(data => {
      console.log('Réponse saveRegistration:', data);
      
      if (data.success) {
        showNotification('✅ ' + data.message, 'success');
        closeModal();
        loadRegistrations(); // Recharger la liste
        
        // Réinitialiser le formulaire
        document.getElementById('studentId').value = '';
        document.getElementById('programId').value = '';
        document.getElementById('amount').value = '';
        document.getElementById('paymentStatus').value = 'pending';
        document.getElementById('registrationDate').value = '';
        document.getElementById('notes').value = '';
      } else {
        showNotification('❌ ' + data.message, 'error');
      }
    })
    .catch(error => {
      console.error('Erreur saveRegistration:', error);
      showNotification('❌ Erreur de connexion: ' + error.message, 'error');
    })
    .finally(() => {
      // Réactiver le bouton
      btn.disabled = false;
      btn.innerHTML = originalText;
    });
    
  } catch (error) {
    console.error('Erreur dans saveRegistration:', error);
    showNotification('❌ Erreur: ' + error.message, 'error');
    
    // Réactiver le bouton en cas d'erreur
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Charger les inscriptions au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  loadRegistrations();
});
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
        <select class="form-select" id="studentId">
          <option value="">Sélectionner un étudiant...</option>
          <?php
          // Charger les étudiants depuis la base
          try {
              $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM students ORDER BY first_name, last_name");
              $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
              foreach ($students as $student) {
                  echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . ' - ' . htmlspecialchars($student['email']) . '</option>';
              }
          } catch (PDOException $e) {
              echo '<option value="">Erreur de chargement</option>';
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Programme</label>
        <select class="form-select" id="programId">
          <option value="">Sélectionner...</option>
          <?php
          // Charger les programmes depuis la base
          try {
              $stmt = $pdo->query("SELECT id, name FROM programs ORDER BY name");
              $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
              foreach ($programs as $program) {
                  echo '<option value="' . $program['id'] . '">' . htmlspecialchars($program['name']) . '</option>';
              }
          } catch (PDOException $e) {
              echo '<option value="">Erreur de chargement</option>';
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Montant (FCFA)</label>
        <input class="form-input" type="number" id="amount" placeholder="150000" min="0" />
      </div>
      <div class="form-group">
        <label class="form-label">Statut de paiement</label>
        <select class="form-select" id="paymentStatus">
          <option value="paid">Payé</option>
          <option value="pending">En attente</option>
          <option value="unpaid">Non payé</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date d'inscription</label>
        <input class="form-input" type="date" id="registrationDate" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" id="notes" placeholder="Notes supplémentaires sur l'inscription..."></textarea>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
