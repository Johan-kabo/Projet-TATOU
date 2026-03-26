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
                <button class="btn-approve" onclick="approveRegistration()">✓ Valider</button>
                <button class="btn-reject" onclick="rejectRegistration()">✕ Rejeter</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
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
  
  // Afficher ou cacher les boutons d'action selon le statut
  const detailActions = document.querySelector('.detail-actions');
  if (registration.status === 'paid') {
    detailActions.style.display = 'none';
  } else {
    detailActions.style.display = 'flex';
  }
  
  // Stocker l'ID de l'inscription courante
  window.currentRegistrationId = id;
}

// Fonction pour valider une inscription
function approveRegistration() {
  if (!window.currentRegistrationId) {
    showNotification('⚠️ Veuillez sélectionner une inscription', 'error');
    return;
  }
  
  updateRegistrationStatus(window.currentRegistrationId, 'paid');
}

// Fonction pour rejeter une inscription
function rejectRegistration() {
  if (!window.currentRegistrationId) {
    showNotification('⚠️ Veuillez sélectionner une inscription', 'error');
    return;
  }
  
  updateRegistrationStatus(window.currentRegistrationId, 'unpaid');
}

// Fonction pour mettre à jour le statut d'une inscription
function updateRegistrationStatus(registrationId, newStatus) {
  const actionText = newStatus === 'paid' ? 'valider' : 'rejeter';
  
  if (!confirm(`Êtes-vous sûr de vouloir ${actionText} cette inscription ?`)) {
    return;
  }
  
  fetch('update_registration_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      registration_id: registrationId,
      status: newStatus
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('✅ ' + data.message, 'success');
      
      // Mettre à jour l'inscription dans la liste
      const registrationIndex = registrations.findIndex(r => r.id === registrationId);
      if (registrationIndex !== -1) {
        registrations[registrationIndex].status = newStatus;
        registrations[registrationIndex] = data.registration; // Mettre à jour avec les données fraîches
      }
      
      // Recharger la liste et le résumé
      renderList();
      updatePaymentSummary();
      
      // Mettre à jour le détail si c'est l'inscription courante
      if (window.currentRegistrationId === registrationId) {
        showDetail(registrationId);
      }
    } else {
      showNotification('❌ ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Erreur updateRegistrationStatus:', error);
    showNotification('❌ Erreur de connexion: ' + error.message, 'error');
  });
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

<script>
// Fonction pour générer un PDF des inscriptions avec design jaune
function generateRegistrationsPDF() {
  showNotification('📄 Génération du PDF en cours...', 'success');
  
  // Récupérer les données des inscriptions
  fetch('generate_report_simple.php?type=registrations&format=json')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        createPDFWithTemplate('RAPPORT DES INSCRIPTIONS', data.data, 'registrations');
      } else {
        showNotification('❌ Erreur: ' + data.message, 'error');
      }
    })
    .catch(error => {
      showNotification('❌ Erreur: ' + error.message, 'error');
    });
}

// Fonction template pour créer des PDF avec design jaune (réutilisable)
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
  if (reportType === 'registrations') {
    // Statistiques des inscriptions
    doc.setFillColor(255, 235, 156); // Jaune clair
    doc.rect(15, yPosition - 10, pageWidth - 30, 40, 'F');
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Statistiques des Inscriptions:', 20, yPosition);
    
    yPosition += 15;
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    
    const totalRegistrations = data.length;
    const paidRegistrations = data.filter(r => r.payment_status === 'paid').length;
    const pendingRegistrations = data.filter(r => r.payment_status === 'pending').length;
    const unpaidRegistrations = data.filter(r => r.payment_status === 'unpaid').length;
    const totalRevenue = data.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
    
    doc.text(`Total: ${totalRegistrations} inscriptions`, 20, yPosition);
    doc.text(`Payées: ${paidRegistrations} inscriptions`, 20, yPosition + 12);
    doc.text(`En attente: ${pendingRegistrations} inscriptions`, 120, yPosition + 12);
    doc.text(`Non payées: ${unpaidRegistrations} inscriptions`, 20, yPosition + 24);
    doc.text(`Revenus: ${totalRevenue.toLocaleString('fr-FR')} FCFA`, 120, yPosition + 24);
    
    yPosition += 50;
    
    // Tableau des inscriptions
    const headers = ['Étudiant', 'Programme', 'Date', 'Montant (FCFA)', 'Statut'];
    const dataRows = data.map(reg => [
      reg.student_name || 'N/A',
      reg.program_name || 'N/A',
      reg.registration_date ? new Date(reg.registration_date).toLocaleDateString('fr-FR') : 'N/A',
      Number(reg.amount || 0).toLocaleString('fr-FR'),
      reg.payment_status || 'N/A'
    ]);
    
    // En-têtes du tableau
    doc.setFillColor(255, 193, 7); // Jaune
    doc.rect(15, yPosition - 5, pageWidth - 30, 10, 'F');
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(10);
    doc.setFont(undefined, 'bold');
    
    headers.forEach((header, index) => {
      const x = 20 + (index * 35);
      doc.text(header.substring(0, 12), x, yPosition);
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
          const x = 20 + (i * 35);
          doc.text(header.substring(0, 12), x, yPosition);
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
        const x = 20 + (cellIndex * 35);
        const displayData = String(cellData).substring(0, 18);
        
        // Couleur pour les statuts de paiement
        if (cellIndex === 4) {
          if (cellData === 'paid') {
            doc.setTextColor(0, 128, 0);
            doc.text('Payé', x, yPosition + 5);
          } else if (cellData === 'pending') {
            doc.setTextColor(255, 140, 0);
            doc.text('En attente', x, yPosition + 5);
          } else {
            doc.setTextColor(255, 0, 0);
            doc.text('Non payé', x, yPosition + 5);
          }
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
}

// Fonction pour exporter les inscriptions (CSV) avec téléchargement direct
function exportRegistrations() {
  showNotification('📊 Export CSV en cours...', 'success');
  
  // Récupérer les données et générer le CSV directement
  fetch('generate_report_simple.php?type=registrations&format=json')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        downloadCSVFile(data.data, 'inscriptions');
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
  csvContent += 'Référence,Étudiant,Programme,Date inscription,Montant,Statut paiement,Notes\n';
  
  data.forEach(registration => {
    const row = [
      `"${registration.reference || ''}"`,
      `"${registration.student_name || ''}"`,
      `"${registration.program_name || ''}"`,
      `"${registration.registration_date ? new Date(registration.registration_date).toLocaleDateString('fr-FR') : ''}"`,
      `"${registration.amount || ''}"`,
      `"${registration.payment_status || ''}"`,
      `"${(registration.notes || '').replace(/"/g, '""')}"`
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

// Fonction de notification (si elle n'existe pas déjà)
function showNotification(message, type = 'info') {
  // Supprimer les notifications existantes
  const existingNotifications = document.querySelectorAll('.notification');
  existingNotifications.forEach(n => n.remove());
  
  // Créer la notification
  const notification = document.createElement('div');
  notification.className = 'notification';
  notification.style.cssText = `
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    padding: 12px 20px; border-radius: 8px; color: white;
    font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease; max-width: 300px;
  `;
  
  // Couleur selon le type
  switch(type) {
    case 'success':
      notification.style.background = 'linear-gradient(135deg, #10B981, #059669)';
      break;
    case 'error':
      notification.style.background = 'linear-gradient(135deg, #EF4444, #DC2626)';
      break;
    default:
      notification.style.background = 'linear-gradient(135deg, #3B82F6, #2563EB)';
  }
  
  notification.textContent = message;
  document.body.appendChild(notification);
  
  // Auto-suppression
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Styles d'animation
if (!document.querySelector('#notification-styles')) {
  const style = document.createElement('style');
  style.id = 'notification-styles';
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
  `;
  document.head.appendChild(style);
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script src="assets/js/sync_programs.js"></script>
</body>
</html>