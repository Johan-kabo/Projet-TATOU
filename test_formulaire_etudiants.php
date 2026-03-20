<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Formulaire Étudiants</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .btn-primary { background: #3B82F6; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 10px; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-input, .form-select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px; border-radius: 8px; color: white; display: none; }
        .notification.success { background: #10B981; }
        .notification.error { background: #EF4444; }
    </style>
</head>
<body>
    <h1>Test Formulaire d'Ajout d'Étudiant</h1>
    
    <button class="btn-primary" onclick="openModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nouvel Étudiant
    </button>

    <div id="notification" class="notification"></div>

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
                            <option value="1">Gestion</option>
                            <option value="2">Médecine</option>
                            <option value="3">Informatique</option>
                            <option value="4">Économie</option>
                            <option value="5">Droit</option>
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
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Fonctions du modal
        function openModal() { 
            console.log('Ouverture du modal');
            document.getElementById('modalOverlay').classList.add('open'); 
        }
        
        function closeModal() { 
            console.log('Fermeture du modal');
            document.getElementById('modalOverlay').classList.remove('open'); 
        }
        
        function handleOverlayClick(e) { 
            if(e.target===document.getElementById('modalOverlay')) closeModal(); 
        }

        // Fonction de notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Fonction saveStudent
        function saveStudent() {
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            
            try {
                // Récupérer les valeurs du formulaire par ID
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
                
                // Validation des champs obligatoires
                if (!firstName || !lastName || !email) {
                    showNotification('⚠️ Veuillez remplir les champs obligatoires (Prénom, Nom, Email)', 'error');
                    return;
                }
                
                // Validation de l'email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showNotification('⚠️ Veuillez entrer une adresse email valide', 'error');
                    return;
                }
                
                // Désactiver le bouton et montrer le chargement
                btn.disabled = true;
                btn.innerHTML = 'Enregistrement...';
                
                // Envoyer les données au serveur
                fetch('api_students_simple.php', {
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
                .then(response => response.json())
                .then(data => {
                    console.log('Réponse saveStudent:', data);
                    
                    if (data.success) {
                        showNotification('✅ ' + data.message, 'success');
                        closeModal();
                        
                        // Réinitialiser le formulaire
                        document.getElementById('firstName').value = '';
                        document.getElementById('lastName').value = '';
                        document.getElementById('email').value = '';
                        document.getElementById('phone').value = '';
                        document.getElementById('programId').value = '';
                        document.getElementById('level').value = '';
                        document.getElementById('dateOfBirth').value = '';
                        document.getElementById('status').value = 'pending';
                    } else {
                        showNotification('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur saveStudent:', error);
                    showNotification('❌ Erreur de connexion: ' + error.message, 'error');
                })
                .finally(() => {
                    // Réactiver le bouton
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
                
            } catch (error) {
                console.error('Erreur dans saveStudent:', error);
                showNotification('❌ Erreur: ' + error.message, 'error');
                
                // Réactiver le bouton en cas d'erreur
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>
