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

    // Revenus totaux (simulation)
    $totalRevenue = 1240000;

} catch (PDOException $e) {
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suivi des Inscriptions - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  :root {
    --accent: #F59E0B; --accent-hover: #D97706;
    --text-primary: #0F172A; --text-muted: #64748B;
    --border: #E2E8F0; --page-bg: #F8FAFC;
    --font: 'Plus Jakarta Sans', sans-serif;
    --success: #10B981; --warning: #F59E0B; --danger: #EF4444;
  }
  html, body {
    height: 100%; font-family: var(--font);
    background: var(--page-bg); color: var(--text-primary);
  }
  .page { display: flex; min-height: 100vh; }
  .sidebar { width: 230px; background: #1e293b; color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 1000; }
  .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
  .brand { display: flex; align-items: center; gap: 12px; }
  .brand-icon { width: 36px; height: 36px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: #fff; }
  .brand-name { font-size: 16px; font-weight: 700; }
  .nav { flex: 1; padding: 12px 0; }
  .nav-item { width: 100%; padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 8px; transition: all 0.2s; cursor: pointer; }
  .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
  .nav-item.active { background: var(--accent); color: white; }
  .nav-item svg { width: 18px; height: 18px; }
  .sidebar-bottom { padding: 20px; margin-top: auto; }
  .main { margin-left: 230px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
  .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: 11px 26px; display: flex; align-items: center; gap: 14px; }
  .search-wrap { position: relative; flex: 1; max-width: 400px; }
  .search-wrap input { width: 100%; font-family: var(--font); font-size: 14px; color: var(--text-primary); background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 12px; padding: 13px 16px 13px 42px; outline: none; transition: border-color 0.2s, background 0.2s; }
  .search-wrap input:focus { border-color: var(--accent); background: #fff; }
  .search-wrap input::placeholder { color: var(--text-muted); }
  .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
  .notif-btn { width: 36px; height: 36px; border-radius: 50%; background: var(--page-bg); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; transition: all 0.2s; }
  .notif-btn:hover { background: #fff; border-color: var(--accent); }
  .notif-dot { width: 8px; height: 8px; background: var(--danger); border-radius: 50%; position: absolute; top: 8px; right: 8px; }
  .user-block { display: flex; align-items: center; gap: 12px; }
  .user-names { text-align: right; }
  .uname { font-size: 14px; font-weight: 600; color: var(--text-primary); }
  .urole { font-size: 12px; color: var(--text-muted); }
  .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent-hover)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; }
  .content { flex: 1; padding: 24px; overflow-y: auto; }
  .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
  .page-title { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
  .page-sub { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-top: 4px; }
  .chips { display: flex; gap: 12px; margin: 16px 0; }
  .chip { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 6px 16px; display: flex; align-items: center; gap: 8px; }
  .chip-val { font-size: 20px; font-weight: 800; }
  .chip-label { font-size: 12px; color: var(--text-muted); }
  .view-toggle { display: flex; gap: 8px; margin: 20px 0; }
  .toggle-btn { padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px; background: #fff; color: var(--text-muted); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
  .toggle-btn.active { background: var(--accent); color: white; border-color: var(--accent); }
  .toggle-btn:hover:not(.active) { border-color: var(--accent); color: var(--accent); }
  .tabs { display: flex; gap: 4px; margin: 20px 0; border-bottom: 2px solid var(--border); }
  .tab { padding: 8px 16px; border: none; background: none; color: var(--text-muted); font-size: 13px; font-weight: 600; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
  .tab.active { color: var(--accent); border-bottom-color: var(--accent); }
  .insc-list { display: grid; gap: 16px; }
  .insc-item { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 20px; transition: all 0.2s; }
  .insc-item:hover { border-color: var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
  .insc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
  .insc-name { font-size: 16px; font-weight: 700; color: var(--text-primary); }
  .insc-meta { text-align: right; }
  .insc-ref { font-size: 12px; color: var(--text-muted); font-family: monospace; }
  .insc-sub { font-size: 13px; color: var(--text-muted); }
  .insc-amount { font-size: 18px; font-weight: 800; color: var(--accent); margin: 8px 0; }
  .insc-status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
  .status-paid { background: var(--success); color: white; }
  .status-pending { background: var(--warning); color: white; }
  .status-unpaid { background: var(--danger); color: white; }
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 2000; }
  .modal-overlay.open { display: flex; }
  .modal { background: white; border-radius: 16px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
  .modal-header { padding: 24px 24px 0; border-bottom: 1px solid var(--border); }
  .modal-title { font-size: 18px; font-weight: 700; color: var(--text-primary); }
  .modal-close { float: right; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); }
  .modal-body { padding: 24px; }
  .form-group { margin-bottom: 20px; }
  .form-label { display: block; font-size: 13.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
  .form-input, .form-select, .form-textarea { width: 100%; font-family: var(--font); font-size: 14px; color: var(--text-primary); background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 12px; padding: 13px 16px; outline: none; transition: border-color 0.2s, background 0.2s; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--accent); background: #fff; }
  .form-input::placeholder, .form-select::placeholder { color: var(--text-muted); }
  .form-textarea { resize: vertical; min-height: 80px; }
  .modal-footer { padding: 16px 24px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px; }
  .btn-cancel { background: #fff; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
  .btn-cancel:hover { background: var(--page-bg); }
  .btn-primary { background: var(--accent); color: white; border: none; border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
  .btn-primary:hover { background: var(--accent-hover); }
  .btn-primary:disabled { background: var(--text-muted); cursor: not-allowed; }
  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main { margin-left: 0; }
    .page-header { flex-direction: column; gap: 16px; }
    .chips { flex-wrap: wrap; }
    .insc-list { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<div class="page">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="brand">
        <div class="brand-icon">T</div>
        <span class="brand-name">TAAJ Corp</span>
      </div>
    </div>
    <nav class="nav">
      <a href="dashboard.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><line x1="7" y1="7" x2="14" y2="7"/><line x1="7" y1="14" x2="14" y2="14"/></svg>
        Tableau de bord
      </a>
      <a href="students.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a8.001 8.001 0 0 1-5.914 5.814l-3.1-3.1"/></svg>
        Étudiants
      </a>
      <a href="programs.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
        Programmes
      </a>
      <a href="registrations.php" class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
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

      <!-- CHIPS -->
      <div class="chips">
        <div class="chip">
          <span class="chip-val"><?php echo $totalRegistrations; ?></span>
          <span class="chip-label">Total</span>
        </div>
        <div class="chip">
          <span class="chip-val"><?php echo $paidRegistrations; ?></span>
          <span class="chip-label">Payées</span>
        </div>
        <div class="chip">
          <span class="chip-val"><?php echo $pendingRegistrations; ?></span>
          <span class="chip-label">En attente</span>
        </div>
        <div class="chip">
          <span class="chip-val"><?php echo $unpaidRegistrations; ?></span>
          <span class="chip-label">Non payées</span>
        </div>
      </div>

      <!-- VIEW TOGGLE -->
      <div class="view-toggle">
        <button class="toggle-btn active" onclick="setView('list', this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          Liste
        </button>
        <button class="toggle-btn" onclick="setView('grid', this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Grille
        </button>
      </div>

      <!-- TABS -->
      <div class="tabs">
        <button class="tab active" onclick="setTab('all', this)">Toutes</button>
        <button class="tab" onclick="setTab('paid', this)">Payées</button>
        <button class="tab" onclick="setTab('pending', this)">En attente</button>
        <button class="tab" onclick="setTab('unpaid', this)">Non payées</button>
      </div>

      <!-- INSCRIPTIONS LIST -->
      <div class="insc-list" id="inscList">
        <!-- Les inscriptions seront chargées dynamiquement -->
      </div>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">Ajouter une inscription</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Étudiant</label>
        <select class="form-select">
          <option value="">Sélectionner un étudiant</option>
          <?php
          try {
              $students = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM students ORDER BY first_name")->fetchAll();
              foreach ($students as $student) {
                  echo "<option value='{$student['id']}'>{$student['name']}</option>";
              }
          } catch (PDOException $e) {
              echo "<option value=''>Erreur de chargement</option>";
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Programme</label>
        <select class="form-select">
          <option value="">Sélectionner un programme</option>
          <?php
          try {
              $programs = $pdo->query("SELECT id, name FROM programs ORDER BY name")->fetchAll();
              foreach ($programs as $program) {
                  echo "<option value='{$program['id']}'>{$program['name']}</option>";
              }
          } catch (PDOException $e) {
              echo "<option value=''>Erreur de chargement</option>";
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Semestre</label>
        <select class="form-select">
          <option value="Semestre 1">Semestre 1</option>
          <option value="Semestre 2">Semestre 2</option>
          <option value="Année complète">Année complète</option>
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
const COLORS = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#EC4899','#84CC16'];

// Récupérer les inscriptions depuis la base de données via API
let inscriptions = [];
let currentView = 'list';
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

function renderList() {
  const list = document.getElementById('inscList');
  const filtered = currentTab === 'all' ? inscriptions : inscriptions.filter(r => r.status === currentTab);
  
  list.innerHTML = filtered.map(item => `
    <div class="insc-item">
      <div class="insc-header">
        <div>
          <div class="insc-name">${item.name}</div>
          <div class="insc-meta">
            <div class="insc-ref">${item.ref}</div>
            <div class="insc-sub">${item.program} • ${item.date}</div>
          </div>
        </div>
      </div>
      <div class="insc-amount">${item.amount}</div>
      <div class="insc-status status-${item.status}">${getStatusText(item.status)}</div>
    </div>
  `).join('');
}

function getStatusText(status) {
  const statusMap = {
    'paid': 'PAYÉ',
    'pending': 'EN ATTENTE',
    'unpaid': 'NON PAYÉ'
  };
  return statusMap[status] || status;
}

function setView(view, btn) {
  currentView = view;
  document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function setTab(tab, btn) {
  currentTab = tab;
  document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderList();
}

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
      reference: 'REG' + date('Y') + str_pad(Math.floor(Math.random() * 9999), 4, '0', STR_PAD_LEFT),
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
  
  // Ajouter un titre
  doc.setFontSize(20);
  doc.text('Rapport des Inscriptions - TAAJ Corp', 105, 20, { align: 'center' });
  
  // Ajouter la date
  doc.setFontSize(12);
  doc.text(`Généré le: ${new Date().toLocaleDateString('fr-FR')}`, 105, 30, { align: 'center' });
  
  // Ajouter les statistiques
  doc.setFontSize(14);
  doc.text('Statistiques des Paiements:', 20, 50);
  
  doc.setFontSize(11);
  const stats = [
    ['Total inscriptions', document.querySelector('.chip-val').textContent],
    ['Payées', document.querySelectorAll('.chip-val')[1].textContent],
    ['En attente', document.querySelectorAll('.chip-val')[2].textContent],
    ['Non payées', document.querySelectorAll('.chip-val')[3].textContent]
  ];
  
  let yPos = 60;
  stats.forEach(([label, value]) => {
    doc.text(`${label}: ${value}`, 20, yPos);
    yPos += 10;
  });
  
  // Ajouter la table des inscriptions
  const items = document.querySelectorAll('.insc-item');
  const tableData = [];
  
  items.forEach(item => {
    const name = item.querySelector('.insc-name')?.textContent.trim() || '';
    const ref = item.querySelector('.insc-ref')?.textContent || '';
    const program = item.querySelector('.insc-sub')?.textContent.split(' • ')[0] || '';
    const amount = item.querySelector('.insc-amount')?.textContent || '';
    const status = item.querySelector('.insc-status')?.textContent || '';
    
    tableData.push([name, ref, program, amount, status]);
  });
  
  doc.autoTable({
    head: ['Nom', 'Référence', 'Programme', 'Montant', 'Statut'],
    body: tableData,
    startY: yPos + 10,
    theme: 'grid',
    styles: { fontSize: 9 }
  });
  
  // Sauvegarder le PDF
  doc.save('rapport_inscriptions_taatj.pdf');
  
  alert('PDF des inscriptions généré avec succès !');
}

// Charger les inscriptions au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  loadRegistrations();
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
