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
<title>Debug Validation - TAAJ Corp</title>
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
.debug-info {
    background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px;
    font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0;
    white-space: pre-wrap; overflow-x: auto;
    max-height: 300px; overflow-y: auto;
}
.validation-status {
    padding: 10px; margin: 10px 0; border-radius: 8px;
    font-weight: 600;
}
.valid { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
.invalid { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🔍 Debug Validation Formulaire</h1>
        <p>Test ultra-détaillé de la validation des champs</p>
    </div>

    <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <form id="studentForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prénom *</label>
                    <input class="form-input" placeholder="Prénom" id="firstName">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom *</label>
                    <input class="form-input" placeholder="Nom" id="lastName">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Adresse email *</label>
                <input class="form-input" type="email" placeholder="Email" id="email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input class="form-input" placeholder="Téléphone" id="phone">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Programme *</label>
                    <select class="form-select" id="programId">
                        <option value="">Sélectionner un programme...</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Niveau *</label>
                    <select class="form-select" id="level">
                        <option value="">Sélectionner un niveau...</option>
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
                    <label class="form-label">Date de naissance *</label>
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
            
            <button type="button" class="btn" onclick="debugValidation()">
                🔍 Debug la validation
            </button>
            
            <button type="button" class="btn" onclick="saveStudent()">
                💾 Enregistrer l'étudiant
            </button>
        </form>
        
        <div id="validationStatus"></div>
        <div id="debugInfo" class="debug-info" style="display: none;"></div>
    </div>
</div>

<script>
// Fonction pour afficher les infos de debug
function showDebugInfo(message) {
    const debugDiv = document.getElementById('debugInfo');
    debugDiv.style.display = 'block';
    debugDiv.textContent += new Date().toLocaleTimeString() + ': ' + message + '\n';
    debugDiv.scrollTop = debugDiv.scrollHeight;
}

// Fonction pour afficher le statut de validation
function showValidationStatus(isValid, message) {
    const statusDiv = document.getElementById('validationStatus');
    statusDiv.className = `validation-status ${isValid ? 'valid' : 'invalid'}`;
    statusDiv.textContent = message;
}

// Fonction pour debugger la validation
function debugValidation() {
    // Vider le debug
    document.getElementById('debugInfo').textContent = '';
    showDebugInfo('=== DÉBUT DU DEBUG VALIDATION ===');
    
    // Récupérer les éléments
    const elements = {
        firstName: document.getElementById('firstName'),
        lastName: document.getElementById('lastName'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        programId: document.getElementById('programId'),
        level: document.getElementById('level'),
        dateOfBirth: document.getElementById('dateOfBirth'),
        status: document.getElementById('status')
    };
    
    showDebugInfo('1. Vérification des éléments:');
    let allElementsFound = true;
    for (const [name, element] of Object.entries(elements)) {
        const found = !!element;
        showDebugInfo(`   ${name}: ${found ? '✅' : '❌'} ${found ? '(trouvé)' : '(NON TROUVÉ)'}`);
        if (!found) allElementsFound = false;
    }
    
    if (!allElementsFound) {
        showValidationStatus(false, '❌ Certains éléments du formulaire ne sont pas trouvés');
        return;
    }
    
    showDebugInfo('\n2. Récupération des valeurs:');
    const values = {};
    for (const [name, element] of Object.entries(elements)) {
        const value = element.value;
        const trimmed = value.trim();
        values[name] = trimmed;
        showDebugInfo(`   ${name}: "${value}" -> "${trimmed}" (longueur: ${trimmed.length})`);
    }
    
    showDebugInfo('\n3. Validation des champs obligatoires:');
    const requiredFields = ['firstName', 'lastName', 'email', 'programId', 'level', 'dateOfBirth'];
    let allValid = true;
    const invalidFields = [];
    
    for (const field of requiredFields) {
        const value = values[field];
        const isValid = value.length > 0;
        showDebugInfo(`   ${field}: ${isValid ? '✅' : '❌'} "${value}" (${value.length > 0 ? 'valide' : 'VIDE'})`);
        
        if (!isValid) {
            allValid = false;
            invalidFields.push(field);
        }
    }
    
    // Validation email spécifique
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const emailValid = emailRegex.test(values.email);
    showDebugInfo(`   Email format: ${emailValid ? '✅' : '❌'} "${values.email}"`);
    
    if (!emailValid) {
        allValid = false;
        invalidFields.push('email (format)');
    }
    
    showDebugInfo('\n4. Résultat final:');
    showDebugInfo(`   Validation globale: ${allValid ? '✅ VALIDE' : '❌ INVALIDE'}`);
    
    if (!allValid) {
        showDebugInfo(`   Champs invalides: ${invalidFields.join(', ')}`);
        showValidationStatus(false, `❌ Champs invalides: ${invalidFields.join(', ')}`);
    } else {
        showValidationStatus(true, '✅ Tous les champs sont valides !');
    }
    
    showDebugInfo('\n=== FIN DU DEBUG VALIDATION ===');
}

// Fonction pour sauvegarder un étudiant
function saveStudent() {
    showDebugInfo('\n=== TENTATIVE D\'ENREGISTREMENT ===');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    // D'abord, debugger la validation
    debugValidation();
    
    // Récupérer les éléments
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const programSelect = document.getElementById('programId');
    const levelSelect = document.getElementById('level');
    const dobInput = document.getElementById('dateOfBirth');
    const statusSelect = document.getElementById('status');
    
    // Vérifier que tous les éléments existent
    if (!firstNameInput || !lastNameInput || !emailInput || !programSelect || !levelSelect || !dobInput || !statusSelect) {
        showDebugInfo('ERREUR: Éléments du formulaire non trouvés');
        return;
    }
    
    // Récupérer et nettoyer les valeurs
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();
    const programId = programSelect.value;
    const level = levelSelect.value;
    const dob = dobInput.value;
    const status = statusSelect.value;
    
    showDebugInfo('Valeurs finales:');
    showDebugInfo(`  firstName: "${firstName}" (${firstName.length})`);
    showDebugInfo(`  lastName: "${lastName}" (${lastName.length})`);
    showDebugInfo(`  email: "${email}" (${email.length})`);
    showDebugInfo(`  programId: "${programId}" (${programId.length})`);
    showDebugInfo(`  level: "${level}" (${level.length})`);
    showDebugInfo(`  dob: "${dob}" (${dob.length})`);
    
    // Validation stricte
    const validationErrors = [];
    if (firstName.length === 0) validationErrors.push('Prénom vide');
    if (lastName.length === 0) validationErrors.push('Nom vide');
    if (email.length === 0) validationErrors.push('Email vide');
    if (programId.length === 0) validationErrors.push('Programme non sélectionné');
    if (level.length === 0) validationErrors.push('Niveau non sélectionné');
    if (dob.length === 0) validationErrors.push('Date de naissance vide');
    
    // Validation email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length > 0 && !emailRegex.test(email)) {
        validationErrors.push('Email format invalide');
    }
    
    if (validationErrors.length > 0) {
        showDebugInfo(`ERREUR VALIDATION: ${validationErrors.join(', ')}`);
        btn.style.background = '#EF4444';
        btn.innerHTML = '❌ ' + validationErrors[0];
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 3000);
        return;
    }
    
    showDebugInfo('✅ Validation réussie, envoi au serveur...');
    
    // Afficher le chargement
    btn.innerHTML = '⏳ Enregistrement en cours...';
    btn.disabled = true;
    
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
        showDebugInfo(`Réponse serveur: ${response.status}`);
        return response.json();
    })
    .then(data => {
        showDebugInfo(`Réponse JSON: ${JSON.stringify(data, null, 2)}`);
        
        if (data.success) {
            btn.style.background = '#10B981';
            btn.innerHTML = '✅ Étudiant ajouté avec succès !';
            showDebugInfo('✅ Succès: ' + data.message);
            
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
            
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 3000);
        }
    })
    .catch(error => {
        showDebugInfo('ERREUR RESEAU: ' + error.message);
        btn.style.background = '#EF4444';
        btn.innerHTML = '❌ Erreur de connexion';
        
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 3000);
    });
}

// Auto-debug au chargement
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(debugValidation, 500);
});
</script>
</body>
</html>
