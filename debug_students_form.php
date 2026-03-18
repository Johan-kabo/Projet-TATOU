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
<title>Debug Formulaire Étudiants - TAAJ Corp</title>
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
.form-group {
    margin-bottom: 20px;
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
        <h1 class="title">🐛 Debug Formulaire Étudiants</h1>
        <p>Test simplifié du formulaire d'ajout d'étudiant avec debug détaillé</p>
    </div>

    <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <form id="studentForm">
            <div class="form-group">
                <label class="form-label">Prénom *</label>
                <input type="text" class="form-input" id="firstName" placeholder="Prénom" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nom *</label>
                <input type="text" class="form-input" id="lastName" placeholder="Nom" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" class="form-input" id="email" placeholder="Email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="tel" class="form-input" id="phone" placeholder="+237 123456789">
            </div>
            
            <div class="form-group">
                <label class="form-label">Programme *</label>
                <select class="form-select" id="programId" required>
                    <option value="">Sélectionner un programme</option>
                    <?php foreach ($programs as $program): ?>
                        <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Niveau *</label>
                <select class="form-select" id="level" required>
                    <option value="">Sélectionner un niveau</option>
                    <option value="L1">L1</option>
                    <option value="L2">L2</option>
                    <option value="L3">L3</option>
                    <option value="M1">M1</option>
                    <option value="M2">M2</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date de naissance *</label>
                <input type="date" class="form-input" id="dateOfBirth" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Statut</label>
                <select class="form-select" id="status">
                    <option value="pending">En attente</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
            </div>
            
            <button type="button" class="btn" onclick="testFormValidation()">
                🧪 Tester la validation
            </button>
            
            <button type="button" class="btn" onclick="saveStudent()">
                💾 Enregistrer l'étudiant
            </button>
            
            <button type="button" class="btn" onclick="loadStudents()">
                🔄 Recharger la liste
            </button>
        </form>
        
        <div id="debugInfo" class="debug-info" style="display: none;"></div>
        
        <div id="studentsList" style="margin-top: 30px;">
            <h3 style="margin-bottom: 15px; color: #1a202c;">📋 Liste des étudiants actuels:</h3>
            <div id="studentsListContent">Chargement...</div>
        </div>
    </div>
</div>

<script>
// Variables globales
let students = [];

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

// Fonction pour tester la validation du formulaire
function testFormValidation() {
    showDebugInfo('=== Test de validation du formulaire ===');
    
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const programId = document.getElementById('programId').value;
    const level = document.getElementById('level').value;
    const dateOfBirth = document.getElementById('dateOfBirth').value;
    
    showDebugInfo(`Prénom: "${firstName}"`);
    showDebugInfo(`Nom: "${lastName}"`);
    showDebugInfo(`Email: "${email}"`);
    showDebugInfo(`Programme ID: "${programId}"`);
    showDebugInfo(`Niveau: "${level}"`);
    showDebugInfo(`Date de naissance: "${dateOfBirth}"`);
    
    // Validation
    const errors = [];
    
    if (!firstName) errors.push('Le prénom est requis');
    if (!lastName) errors.push('Le nom est requis');
    if (!email) errors.push('L\'email est requis');
    if (!programId) errors.push('Le programme est requis');
    if (!level) errors.push('Le niveau est requis');
    if (!dateOfBirth) errors.push('La date de naissance est requise');
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        errors.push('L\'email n\'est pas valide');
    }
    
    if (errors.length > 0) {
        showDebugInfo('ERREURS: ' + errors.join(', '));
        showNotification('❌ ' + errors[0], 'error');
    } else {
        showDebugInfo('✅ Validation réussie !');
        showNotification('✅ Formulaire valide', 'success');
    }
}

// Fonction pour sauvegarder un étudiant
function saveStudent() {
    showDebugInfo('=== Début de saveStudent() ===');
    
    const btn = event.target;
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
    
    showDebugInfo(`Données collectées: ${firstName} ${lastName}, ${email}, Programme: ${programId}`);
    
    // Validation
    if (!firstName || !lastName || !email || !programId || !level || !dateOfBirth) {
        showDebugInfo('ERREUR: Champs obligatoires manquants');
        showNotification('⚠️ Veuillez remplir tous les champs obligatoires', 'error');
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
    
    // Préparer les données
    const studentData = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        program_id: programId,
        level: level,
        date_of_birth: dateOfBirth,
        status: status
    };
    
    showDebugInfo('Données envoyées: ' + JSON.stringify(studentData, null, 2));
    
    // Envoyer les données au serveur
    fetch('api_crud_students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(studentData)
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
            
            // Recharger la liste
            loadStudents();
            
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

// Fonction pour charger les étudiants
function loadStudents() {
    showDebugInfo('=== Chargement des étudiants ===');
    
    fetch('api_crud_students.php')
        .then(response => {
            showDebugInfo('Réponse reçue, status: ' + response.status);
            return response.json();
        })
        .then(data => {
            showDebugInfo('Données reçues: ' + JSON.stringify(data, null, 2));
            
            if (data.success) {
                students = data.students;
                showDebugInfo('✅ ' + students.length + ' étudiants chargés');
                
                // Afficher la liste
                const listContent = document.getElementById('studentsListContent');
                if (students.length === 0) {
                    listContent.innerHTML = '<p style="color: #64748b;">Aucun étudiant trouvé</p>';
                } else {
                    listContent.innerHTML = students.map(student => `
                        <div style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px;">
                            <strong>${student.name}</strong> - ${student.email}<br>
                            Programme: ${student.program_name} - Niveau: ${student.level} - Statut: ${student.status}
                        </div>
                    `).join('');
                }
            } else {
                showDebugInfo('❌ Erreur: ' + data.message);
                document.getElementById('studentsListContent').innerHTML = '<p style="color: #dc2626;">Erreur: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            showDebugInfo('ERREUR: ' + error.message);
            console.error('Erreur:', error);
            document.getElementById('studentsListContent').innerHTML = '<p style="color: #dc2626;">Erreur de chargement</p>';
        });
}

// Charger les étudiants au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    showDebugInfo('Page chargée, début du chargement des étudiants...');
    loadStudents();
});
</script>
</body>
</html>
