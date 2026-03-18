<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

// Récupérer les programmes pour le formulaire
$programs = [];
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
<title>Test Formulaire Corrigé - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f8fafc; padding: 20px;
}
.container {
    max-width: 800px; margin: 0 auto;
}
.header {
    background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.title {
    font-size: 24px; font-weight: 700; color: #1a202c; margin-bottom: 10px;
}
.form-row {
    display: flex; gap: 20px;
    margin-bottom: 20px;
}
.form-group {
    flex: 1;
}
.form-label {
    display: block; font-size: 13.5px; font-weight: 600; color: #1a202c; margin-bottom: 8px;
}
.form-input, .form-select {
    width: 100%; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: #1a202c;
    background: #F8FAFC; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 13px 16px;
    outline: none; transition: border-color 0.2s, background 0.2s;
}
.form-input:focus, .form-select:focus {
    border-color: #f59e0b; background: #fff;
}
.btn {
    background: #f59e0b; color: white; border: none; border-radius: 8px;
    padding: 12px 24px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: background 0.2s; margin: 10px 5px 0 0;
}
.btn:hover { background: #d97706; }
.btn:disabled { background: #64748b; cursor: not-allowed; }
.notification {
    position: fixed; top: 20px; right: 20px; padding: 15px 20px;
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000; font-weight: 600; font-size: 14px;
    max-width: 300px;
}
.success { background: #10b981; color: white; }
.error { background: #dc2626; color: white; }
.info { background: #3b82f6; color: white; }
.debug-info {
    background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px;
    font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0;
    white-space: pre-wrap; overflow-x: auto;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">✅ Test Formulaire Corrigé</h1>
        <p>Formulaire étudiant avec placeholders corrigés et debug complet</p>
    </div>

    <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <form id="studentForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prénom</label>
                    <input class="form-input" placeholder="Prénom" id="firstName">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input class="form-input" placeholder="Nom" id="lastName">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Adresse email</label>
                <input class="form-input" type="email" placeholder="Email" id="email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input class="form-input" placeholder="Téléphone" id="phone">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Programme</label>
                    <select class="form-select" id="programId">
                        <option value="">Sélectionner...</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></option>
                        <?php endforeach; ?>
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
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input class="form-input" type="date" id="dateOfBirth">
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select class="form-select" id="status">
                        <option value="active">Actif</option>
                        <option value="pending">En attente</option>
                        <option value="inactive">Inactif</option>
                    </select>
                </div>
            </div>
            
            <button type="button" class="btn" onclick="testElements()">
                🔍 Tester les éléments
            </button>
            
            <button type="button" class="btn" onclick="saveStudent()">
                💾 Enregistrer l'étudiant
            </button>
        </form>
        
        <div id="debugInfo" class="debug-info" style="display: none;"></div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔗 Actions Rapides</h2>
        <button class="btn" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Page Étudiants (corrigée)
        </button>
        
        <button class="btn" onclick="window.open('simple_test.php', '_blank')">
            🧪 Test Simple BD
        </button>
        
        <button class="btn" onclick="window.open('debug_students_form.php', '_blank')">
            🐛 Debug Formulaire
        </button>
    </div>
</div>

<script>
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

// Fonction pour afficher les infos de debug
function showDebugInfo(message) {
    const debugDiv = document.getElementById('debugInfo');
    debugDiv.style.display = 'block';
    debugDiv.textContent = new Date().toLocaleTimeString() + ': ' + message;
}

// Fonction pour tester les éléments du formulaire
function testElements() {
    showDebugInfo('=== Test des éléments du formulaire ===');
    
    // Test avec les sélecteurs exacts du code corrigé
    const elements = {
        'firstName': document.querySelector('input[placeholder="Prénom"]'),
        'lastName': document.querySelector('input[placeholder="Nom"]'),
        'email': document.querySelector('input[placeholder="Email"]'),
        'phone': document.querySelector('input[placeholder="Téléphone"]'),
        'program': document.querySelector('select'),
        'level': document.querySelectorAll('select')[1],
        'dob': document.querySelector('input[type="date"]'),
        'status': document.querySelectorAll('select')[2]
    };
    
    let allFound = true;
    
    for (const [name, element] of Object.entries(elements)) {
        const found = !!element;
        const value = found ? element.value : 'N/A';
        
        showDebugInfo(`${name}: ${found ? '✅' : '❌'} - Valeur: "${value}"`);
        
        if (!found) {
            allFound = false;
        }
    }
    
    if (allFound) {
        showNotification('✅ Tous les éléments du formulaire sont trouvés !', 'success');
    } else {
        showNotification('❌ Certains éléments sont manquants', 'error');
    }
}

// Fonction pour sauvegarder un étudiant
function saveStudent() {
    showDebugInfo('=== Début de saveStudent() ===');
    
    const btn = event.target;
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
        showDebugInfo('ERREUR: Éléments du formulaire non trouvés');
        showNotification('⚠️ Erreur: Formulaire incomplet', 'error');
        return;
    }
    
    showDebugInfo('✅ Tous les éléments trouvés');
    
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();
    const programId = programSelect.value;
    const level = levelSelect.value;
    const dob = dobInput.value;
    const status = statusSelect.value;
    
    showDebugInfo(`=== DÉTAILLAGE DES VALEURS ===`);
    showDebugInfo(`firstName: "${firstName}" (longueur: ${firstName.length})`);
    showDebugInfo(`lastName: "${lastName}" (longueur: ${lastName.length})`);
    showDebugInfo(`email: "${email}" (longueur: ${email.length})`);
    showDebugInfo(`phone: "${phone}" (longueur: ${phone.length})`);
    showDebugInfo(`programId: "${programId}" (longueur: ${programId.length})`);
    showDebugInfo(`level: "${level}" (longueur: ${level.length})`);
    showDebugInfo(`dob: "${dob}" (longueur: ${dob.length})`);
    showDebugInfo(`status: "${status}" (longueur: ${status.length})`);
    
    showDebugInfo(`=== VALIDATION ===`);
    showDebugInfo(`firstName valide: ${!!firstName} (${firstName.length > 0 ? '✅' : '❌'})`);
    showDebugInfo(`lastName valide: ${!!lastName} (${lastName.length > 0 ? '✅' : '❌'})`);
    showDebugInfo(`email valide: ${!!email} (${email.length > 0 ? '✅' : '❌'})`);
    showDebugInfo(`programId valide: ${!!programId} (${programId.length > 0 ? '✅' : '❌'})`);
    showDebugInfo(`level valide: ${!!level} (${level.length > 0 ? '✅' : '❌'})`);
    showDebugInfo(`dob valide: ${!!dob} (${dob.length > 0 ? '✅' : '❌'})`);
    
    // Validation
    const validationErrors = [];
    if (!firstName) validationErrors.push('Prénom');
    if (!lastName) validationErrors.push('Nom');
    if (!email) validationErrors.push('Email');
    if (!programId) validationErrors.push('Programme');
    if (!level) validationErrors.push('Niveau');
    if (!dob) validationErrors.push('Date de naissance');
    
    if (validationErrors.length > 0) {
        showDebugInfo(`ERREUR VALIDATION: Champs manquants - ${validationErrors.join(', ')}`);
        showNotification(`⚠️ Veuillez remplir: ${validationErrors.join(', ')}`, 'error');
        return;
    }
    
    // Validation email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showDebugInfo('ERREUR: Email invalide');
        showNotification('⚠️ Email invalide', 'error');
        return;
    }
    
    // Afficher le chargement
    btn.innerHTML = '⏳ Enregistrement en cours...';
    btn.disabled = true;
    
    showDebugInfo('Envoi de la requête à api_crud_students.php...');
    
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
        showDebugInfo('Réponse reçue, status: ' + response.status);
        return response.json();
    })
    .then(data => {
        showDebugInfo('Données JSON: ' + JSON.stringify(data, null, 2));
        
        if (data.success) {
            btn.style.background = '#10B981';
            btn.innerHTML = '✅ Étudiant ajouté avec succès !';
            showDebugInfo('✅ Succès: ' + data.message);
            showNotification('✅ Étudiant ajouté avec succès !', 'success');
            
            // Réinitialiser le formulaire
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.background = '';
                document.getElementById('studentForm').reset();
            }, 2000);
        } else {
            btn.style.background = '#EF4444';
            btn.innerHTML = '❌ ' + data.message;
            showDebugInfo('❌ Erreur serveur: ' + data.message);
            showNotification('❌ ' + data.message, 'error');
            
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 3000);
        }
    })
    .catch(error => {
        showDebugInfo('ERREUR RESEAU: ' + error.message);
        console.error('Erreur complète:', error);
        
        btn.style.background = '#EF4444';
        btn.innerHTML = '❌ Erreur de connexion';
        showNotification('❌ Erreur de connexion', 'error');
        
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 3000);
    });
}

// Test automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    showDebugInfo('Page chargée, test des éléments...');
    setTimeout(testElements, 1000);
});
</script>
</body>
</html>
