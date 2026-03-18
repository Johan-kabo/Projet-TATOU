<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

// Récupérer les données initiales pour le formulaire
$students = [];
$programs = [];

try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM students ORDER BY last_name, first_name");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
}

try {
    $stmt = $pdo->query("SELECT id, name FROM programs ORDER BY name");
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $programs = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscriptions - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f8fafc; min-height: 100vh;
}
.container {
    max-width: 1400px; margin: 0 auto; padding: 20px;
}
.header {
    background: white; border-radius: 16px; padding: 24px; margin-bottom: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex; justify-content: space-between; align-items: center;
}
.title {
    font-size: 28px; font-weight: 800; color: #1a202c;
}
.subtitle {
    color: #64748b; margin-top: 8px;
}
.btn {
    background: #f59e0b; color: white; border: none; border-radius: 12px;
    padding: 12px 24px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px;
}
.btn:hover { background: #d97706; transform: translateY(-1px); }
.btn-success { background: #10b981; }
.btn-success:hover { background: #059669; }
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);
    display: none; align-items: center; justify-content: center; z-index: 1000;
}
.modal-overlay.open { display: flex; }
.modal {
    background: white; border-radius: 16px; width: 90%; max-width: 600px;
    max-height: 90vh; overflow-y: auto;
}
.modal-header {
    padding: 24px; border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-title {
    font-size: 20px; font-weight: 700; color: #1a202c;
}
.modal-close {
    background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer;
    padding: 4px; border-radius: 8px; transition: background 0.2s;
}
.modal-close:hover { background: #f1f5f9; }
.modal-body {
    padding: 24px;
}
.modal-footer {
    padding: 24px; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: flex-end; gap: 12px;
}
.btn-cancel {
    background: #f1f5f9; color: #475569; border: none;
    padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;
}
.btn-cancel:hover { background: #e2e8f0; }
.form-group {
    margin-bottom: 20px;
}
.form-label {
    display: block; font-size: 14px; font-weight: 600; color: #1a202c; margin-bottom: 8px;
}
.form-input, .form-select {
    width: 100%; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: #1a202c;
    background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 13px 16px;
    outline: none; transition: border-color 0.2s, background 0.2s;
}
.form-input:focus, .form-select:focus {
    border-color: #f59e0b; background: #fff;
}
.form-row {
    display: flex; gap: 16px;
}
.form-row .form-group {
    flex: 1;
}
.notification {
    position: fixed; top: 20px; right: 20px; padding: 15px 20px;
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000; font-weight: 600; font-size: 14px;
}
.success { background: #10b981; color: white; }
.error { background: #dc2626; color: white; }
.info { background: #3b82f6; color: white; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1 class="title">📝 Inscriptions</h1>
            <p class="subtitle">Gestion des inscriptions des étudiants</p>
        </div>
        <button class="btn" onclick="openModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nouvelle Inscription
        </button>
    </div>

    <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="margin-bottom: 20px; color: #1a202c;">📋 Liste des Inscriptions</h2>
        <div id="registrationsList">
            <p style="text-align: center; padding: 40px; color: #64748b;">Chargement des inscriptions...</p>
        </div>
    </div>
</div>

<!-- MODAL Nouvelle Inscription -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Ajouter une Inscription</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <form id="registrationForm">
                <div class="form-group">
                    <label class="form-label">Étudiant *</label>
                    <select class="form-select" id="studentId" required>
                        <option value="">Sélectionner un étudiant...</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                (<?php echo htmlspecialchars($student['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Programme *</label>
                    <select class="form-select" id="programId" required>
                        <option value="">Sélectionner un programme...</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Niveau *</label>
                    <select class="form-select" id="level" required>
                        <option value="">Sélectionner...</option>
                        <option value="L1">L1</option>
                        <option value="L2">L2</option>
                        <option value="L3">L3</option>
                        <option value="M1">M1</option>
                        <option value="M2">M2</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Montant (FCFA) *</label>
                        <input class="form-input" type="number" id="amount" placeholder="150000" min="0" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut de paiement *</label>
                        <select class="form-select" id="paymentStatus" required>
                            <option value="paid">Payé</option>
                            <option value="pending">En attente</option>
                            <option value="unpaid">Non payé</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Méthode de paiement</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="cash">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="check">Chèque</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'inscription</label>
                        <input class="form-input" type="date" id="registrationDate" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-input" id="notes" rows="3" placeholder="Notes additionnelles..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Annuler</button>
            <button class="btn btn-success" onclick="saveRegistration()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Enregistrer
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let registrations = [];

// Fonctions du modal
function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { 
    document.getElementById('modalOverlay').classList.remove('open');
    document.getElementById('registrationForm').reset();
}
function handleOverlayClick(e) { 
    if(e.target === document.getElementById('modalOverlay')) closeModal(); 
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s ease-out';
        notification.style.opacity = '0';
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

// Fonction pour charger les inscriptions
async function loadRegistrations() {
    try {
        const response = await fetch('api_crud_registrations.php');
        const text = await response.text();
        
        if (!text) {
            throw new Error('Réponse vide du serveur');
        }
        
        const data = JSON.parse(text);
        
        if (data.success) {
            registrations = data.registrations || [];
            renderRegistrations();
        } else {
            showNotification('❌ Erreur: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Erreur de chargement:', error);
        showNotification('❌ Erreur de chargement des inscriptions', 'error');
    }
}

// Fonction pour afficher les inscriptions
function renderRegistrations() {
    const listDiv = document.getElementById('registrationsList');
    
    if (registrations.length === 0) {
        listDiv.innerHTML = '<p style="text-align: center; padding: 40px; color: #64748b;">Aucune inscription trouvée</p>';
        return;
    }
    
    let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';
    html += '<thead><tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Étudiant</th>';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Programme</th>';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Niveau</th>';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Montant</th>';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Statut</th>';
    html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #1a202c;">Date</th>';
    html += '</tr></thead><tbody>';
    
    registrations.forEach(reg => {
        const statusColor = reg.payment_status === 'paid' ? '#10b981' : 
                           reg.payment_status === 'pending' ? '#f59e0b' : '#dc2626';
        const statusText = reg.payment_status === 'paid' ? 'Payé' : 
                          reg.payment_status === 'pending' ? 'En attente' : 'Non payé';
        
        html += `<tr style="border-bottom: 1px solid #e2e8f0;">`;
        html += `<td style="padding: 12px;">${reg.name}</td>`;
        html += `<td style="padding: 12px;">${reg.program_name}</td>`;
        html += `<td style="padding: 12px;">${reg.level || 'N/A'}</td>`;
        html += `<td style="padding: 12px;">${reg.amount?.toLocaleString() || 'N/A'} FCFA</td>`;
        html += `<td style="padding: 12px;"><span style="background: ${statusColor}20; color: ${statusColor}; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">${statusText}</span></td>`;
        html += `<td style="padding: 12px;">${reg.date || 'N/A'}</td>`;
        html += `</tr>`;
    });
    
    html += '</tbody></table></div>';
    listDiv.innerHTML = html;
}

// Fonction pour sauvegarder une inscription
function saveRegistration() {
    const studentId = document.getElementById('studentId').value;
    const programId = document.getElementById('programId').value;
    const level = document.getElementById('level').value;
    const amount = document.getElementById('amount').value;
    const paymentStatus = document.getElementById('paymentStatus').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    const registrationDate = document.getElementById('registrationDate').value;
    const notes = document.getElementById('notes').value;
    
    // Validation
    if (!studentId || !programId || !level || !amount) {
        showNotification('⚠️ Veuillez remplir tous les champs obligatoires', 'error');
        return;
    }
    
    // Envoyer les données
    fetch('api_crud_registrations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: studentId,
            program_id: programId,
            level: level,
            amount: amount,
            payment_status: paymentStatus,
            payment_method: paymentMethod,
            registration_date: registrationDate || new Date().toISOString().split('T')[0],
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Inscription ajoutée avec succès !', 'success');
            closeModal();
            loadRegistrations(); // Recharger la liste
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('❌ Erreur de connexion', 'error');
    });
}

// Charger les inscriptions au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadRegistrations();
    
    // Mettre la date du jour par défaut
    document.getElementById('registrationDate').value = new Date().toISOString().split('T')[0];
});
</script>
</body>
</html>
