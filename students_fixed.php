<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Plateforme TATOU</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-bg: #1e293b;
            --sidebar-text: #f1f5f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg-secondary); color: var(--text-primary); }
        .layout { display: flex; height: 100vh; }
        .sidebar { width: 250px; background: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { flex: 1; padding: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--sidebar-text); text-decoration: none; border-radius: 8px; transition: all 0.2s; margin-bottom: 4px; }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
        .nav-item.active { background: var(--primary); }
        .content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .page-header { padding: 20px 24px; background: var(--bg-primary); border-bottom: 1px solid var(--border); }
        .page-title { font-size: 24px; font-weight: 700; color: var(--text-primary); }
        .page-content { flex: 1; padding: 24px; overflow-y: auto; }
        .card { background: var(--bg-primary); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-primary); border-radius: 12px; width: 500px; max-width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px; border-bottom: 1px solid var(--border); }
        .modal-title { font-size: 18px; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); }
        .modal-body { padding: 20px; }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding: 20px; border-top: 1px solid var(--border); }
        .form-group { margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-primary); }
        .form-input, .form-select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn { padding: 10px 16px; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-cancel { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border); }
        .btn-cancel:hover { background: var(--bg-secondary); }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; z-index: 9999; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .notification.success { background: #10b981; }
        .notification.error { background: #ef4444; }
        .notification.info { background: #3b82f6; }
        .notification.warning { background: #f59e0b; }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 style="color: white; font-size: 20px;">TATOU</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Tableau de bord
                </a>
                <a href="students.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    Étudiants
                </a>
                <a href="registrations.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    Inscriptions
                </a>
                <a href="programs.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    Programmes
                </a>
                <a href="stats.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Statistiques
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Gestion des Étudiants</h1>
                    <p style="color: var(--text-secondary); margin-top: 4px;">Ajouter, modifier et gérer les étudiants</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Nouvel Étudiant
                </button>
            </header>

            <div class="page-content">
                <div class="card">
                    <div style="padding: 20px;">
                        <div id="studentList"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Ajouter un étudiant</div>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-input" id="firstName" placeholder="Prénom">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-input" id="lastName" placeholder="Nom">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="email" placeholder="Email">
                </div>
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-input" id="phone" placeholder="Téléphone">
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
                    <input type="date" class="form-input" id="dateOfBirth">
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
                <button class="btn btn-cancel" onclick="closeModal()">Annuler</button>
                <button class="btn btn-primary" onclick="saveStudent()">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        // Variables globales
        let students = [];

        // Fonctions du modal
        function openModal() { 
            console.log('openModal() appelé');
            document.getElementById('modalOverlay').classList.add('open'); 
        }

        function closeModal() { 
            console.log('closeModal() appelé');
            document.getElementById('modalOverlay').classList.remove('open'); 
        }

        function handleOverlayClick(e) { 
            if(e.target===document.getElementById('modalOverlay')) closeModal(); 
        }

        // Fonction de notification
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Charger les étudiants
        async function loadStudents() {
            try {
                const response = await fetch('api_students_simple.php');
                const text = await response.text();
                console.log('Réponse API:', text);
                
                if (!text) {
                    throw new Error('Réponse vide du serveur');
                }
                
                const data = JSON.parse(text);
                console.log('Données parsées:', data);
                
                if (data.success) {
                    students = data.students || [];
                    renderStudentList();
                    showNotification('✅ Étudiants chargés avec succès', 'success');
                } else {
                    showNotification('❌ Erreur: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erreur de chargement:', error);
                showNotification('❌ Erreur de chargement: ' + error.message, 'error');
            }
        }

        // Afficher la liste des étudiants
        function renderStudentList() {
            const listContainer = document.getElementById('studentList');
            
            if (students.length === 0) {
                listContainer.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Aucun étudiant trouvé</p>';
                return;
            }

            let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--bg-secondary);">';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border);">ID</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border);">Nom</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border);">Email</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border);">Téléphone</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border);">Statut</th>';
            html += '</tr></thead><tbody>';

            students.forEach(student => {
                const statusColor = student.status === 'active' ? '#10b981' : 
                                   student.status === 'pending' ? '#f59e0b' : '#ef4444';
                const statusText = student.status === 'active' ? 'Actif' : 
                                  student.status === 'pending' ? 'En attente' : 'Inactif';
                
                html += '<tr style="border-bottom: 1px solid var(--border);">';
                html += `<td style="padding: 12px;">${student.id}</td>`;
                html += `<td style="padding: 12px;">${student.name}</td>`;
                html += `<td style="padding: 12px;">${student.email}</td>`;
                html += `<td style="padding: 12px;">${student.phone}</td>`;
                html += `<td style="padding: 12px;"><span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">${statusText}</span></td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            listContainer.innerHTML = html;
        }

        // Sauvegarder un étudiant
        function saveStudent() {
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            
            try {
                // Récupérer les valeurs
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
                
                // Validation
                if (!firstName || !lastName || !email) {
                    showNotification('⚠️ Veuillez remplir les champs obligatoires', 'error');
                    return;
                }
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showNotification('⚠️ Veuillez entrer une adresse email valide', 'error');
                    return;
                }
                
                // Désactiver le bouton
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                
                // Envoyer les données
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
                        loadStudents(); // Recharger la liste
                        
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
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
                
            } catch (error) {
                console.error('Erreur dans saveStudent:', error);
                showNotification('❌ Erreur: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Charger les étudiants au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadStudents();
        });
    </script>
</body>
</html>
