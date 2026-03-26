// Script global pour synchroniser les dropdowns de programmes sur toutes les pages

// Fonction pour synchroniser toutes les dropdowns de programmes
function syncAllProgramDropdowns() {
  // Récupérer la liste à jour des programmes
  fetch('api_get_programs.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const programs = data.programs;
        
        // Synchroniser les dropdowns sur la page actuelle
        const dropdowns = document.querySelectorAll('select.form-select');
        
        dropdowns.forEach(dropdown => {
          // Vérifier si c'est une dropdown de programmes (basé sur le contexte)
          const label = dropdown.closest('.form-group')?.querySelector('label')?.textContent.toLowerCase() || '';
          const placeholder = dropdown.querySelector('option')?.textContent.toLowerCase() || '';
          const id = dropdown.id?.toLowerCase() || '';
          
          if (label.includes('programme') || placeholder.includes('programme') || 
              label.includes('program') || placeholder.includes('program') ||
              id.includes('program')) {
            
            // Sauvegarder la sélection actuelle
            const currentValue = dropdown.value;
            
            // Vider la dropdown
            dropdown.innerHTML = '<option value="">Sélectionner...</option>';
            
            // Ajouter les programmes à jour
            programs.forEach(program => {
              const option = document.createElement('option');
              option.value = program.id;
              option.textContent = program.name;
              if (program.active === false) {
                option.style.color = '#999';
                option.textContent += ' (inactif)';
              }
              dropdown.appendChild(option);
            });
            
            // Restaurer la sélection si elle existe toujours
            if (currentValue) {
              dropdown.value = currentValue;
            }
            
            // Déclencher l'événement change pour les frameworks qui écoutent
            dropdown.dispatchEvent(new Event('change'));
          }
        });
        
        console.log('Dropdowns de programmes synchronisées avec succès');
        
        // Afficher une notification discrète
        showSyncNotification('Liste des programmes mise à jour');
      }
    })
    .catch(error => {
      console.error('Erreur lors de la synchronisation des dropdowns:', error);
    });
}

// Fonction pour afficher une notification de synchronisation
function showSyncNotification(message) {
  // Vérifier si une notification existe déjà
  let notification = document.getElementById('sync-notification');
  if (!notification) {
    notification = document.createElement('div');
    notification.id = 'sync-notification';
    notification.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 8px 16px;
      background: linear-gradient(135deg, #10B981, #059669);
      color: white;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      z-index: 9999;
      opacity: 0;
      transition: opacity 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    document.body.appendChild(notification);
  }
  
  notification.textContent = message;
  notification.style.opacity = '1';
  
  setTimeout(() => {
    notification.style.opacity = '0';
  }, 2000);
}

// Synchroniser automatiquement au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  // Attendre un peu pour que les autres scripts soient chargés
  setTimeout(syncAllProgramDropdowns, 500);
});

// Synchroniser toutes les 30 secondes (optionnel)
setInterval(syncAllProgramDropdowns, 30000);

// Écouter les événements personnalisés de mise à jour
document.addEventListener('programsUpdated', syncAllProgramDropdowns);

// Exporter les fonctions pour utilisation globale
window.syncPrograms = {
  syncAll: syncAllProgramDropdowns,
  showNotification: showSyncNotification
};
