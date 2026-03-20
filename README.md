<<<<<<< HEAD
# Projet TATOU - Gestion des Inscriptions

Application web de gestion des inscriptions étudiantes pour TAAJ Corp.

## Fonctionnalités

- ✅ Gestion des étudiants
- ✅ Gestion des programmes académiques
- ✅ Suivi des inscriptions
- ✅ Système de paiement
- ✅ Statistiques et rapports
- ✅ Export des données (CSV)

## Technologies

- **Backend** : PHP 7.4+
- **Base de données** : MySQL 5.7+
- **Frontend** : HTML5, CSS3, JavaScript
- **Serveur** : WAMP/Apache

## Installation

### Prérequis
- WAMP64 avec Apache et MySQL
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur

### Configuration

1. Clonez le repository
```bash
git clone https://github.com/Johan-kabo/Projet-TATOU.git
cd Projet-TATOU
```

2. Configurez la base de données dans `db/mysql_connection.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion inscription');
define('DB_USER', 'root');
define('DB_PASS', '');
```

3. Exécutez le script SQL pour créer les tables
```bash
mysql -u root "gestion inscription" < database_setup.sql
```

4. L'application est prête dès le premier chargement !

## Identifiants par défaut

- **Utilisateur** : admin
- **Mot de passe** : (fourni lors de votre setup)

## Structure du projet

```
Projet TATOU/
├── assets/              # CSS, JS, fonts
├── db/                  # Connexion et initialisation BD
├── includes/            # Header, footer, fonctions
├── data/                # Données d'exemple
├── dashboard.php        # Page d'accueil
├── students.php         # Gestion des étudiants
├── programs.php         # Gestion des programmes
├── registrations.php    # Gestion des inscriptions
├── stats.php            # Statistiques
├── export.php           # Export CSV
└── login.php            # Page de connexion
```

## Développé par

**Johan Kabo** - TAAJ Corp

## Licence

Propriété de TAAJ Corp
=======
# Projet-TAAJ
Plateforme de gestion des inscription d'un etablissement
>>>>>>> beb5c4ee53bd57aa66e90a936f46d5b2073d5e88
