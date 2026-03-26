<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Récupérer le type de rapport demandé
$reportType = $_GET['type'] ?? 'summary';

try {
    $data = [];
    $title = '';
    
    switch ($reportType) {
        case 'students':
            // Rapport des étudiants
            $stmt = $pdo->query("
                SELECT s.*, COUNT(r.id) as registration_count,
                       SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_paid,
                       p.name as program_name
                FROM students s
                LEFT JOIN registrations r ON s.id = r.student_id
                LEFT JOIN programs p ON s.program_id = p.id
                GROUP BY s.id
                ORDER BY s.last_name, s.first_name
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $title = 'Rapport des Étudiants';
            break;
            
        case 'registrations':
            // Rapport des inscriptions
            $stmt = $pdo->query("
                SELECT r.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
                       p.name as program_name
                FROM registrations r
                LEFT JOIN students s ON r.student_id = s.id
                LEFT JOIN programs p ON r.program_id = p.id
                ORDER BY r.registration_date DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $title = 'Rapport des Inscriptions';
            break;
            
        case 'payments':
            // Rapport des paiements
            $stmt = $pdo->query("
                SELECT r.reference, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
                       p.name as program_name, r.amount, r.payment_status, r.payment_date
                FROM registrations r
                LEFT JOIN students s ON r.student_id = s.id
                LEFT JOIN programs p ON r.program_id = p.id
                WHERE r.payment_status = 'paid'
                ORDER BY r.payment_date DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $title = 'Rapport des Paiements';
            break;
            
        case 'programs':
            // Rapport des programmes
            $stmt = $pdo->query("
                SELECT p.*, COUNT(r.id) as registration_count,
                       SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_revenue
                FROM programs p
                LEFT JOIN registrations r ON p.id = r.program_id
                GROUP BY p.id
                ORDER BY p.name
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $title = 'Rapport des Programmes';
            break;
            
        default:
            // Rapport résumé
            $stmt = $pdo->query("
                SELECT 
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT p.id) as total_programs,
                    COUNT(r.id) as total_registrations,
                    COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_registrations,
                    SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_revenue
                FROM students s
                CROSS JOIN programs p
                LEFT JOIN registrations r ON (s.id = r.student_id OR p.id = r.program_id)
            ");
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $title = 'Rapport Général';
            break;
    }
    
    // Générer le HTML optimisé pour PDF
    $html = generatePDFHTML($title, $data, $reportType);
    
    // Afficher le HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

function generatePDFHTML($title, $data, $reportType) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8"/>
        <title>' . $title . '</title>
        <style>
            @page {
                size: A4;
                margin: 20mm;
                @bottom-center {
                    content: "Page " counter(page) . " sur " counter(pages);
                    font-size: 10px;
                    color: #666;
                    font-family: Arial, sans-serif;
                }
            }
            
            * {
                box-sizing: border-box;
            }
            
            body { 
                font-family: "Helvetica Neue", Arial, sans-serif; 
                font-size: 12px; 
                margin: 0; 
                padding: 0;
                color: #333;
                background: #fff;
                line-height: 1.4;
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 10px;
                margin-bottom: 30px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .header .subtitle {
                margin: 10px 0 0 0;
                font-size: 14px;
                opacity: 0.9;
            }
            
            .info-section {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 20px;
                margin: 20px 0;
                border-radius: 0 8px 8px 0;
            }
            
            .summary-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-left: 4px solid #667eea;
                text-align: center;
            }
            
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 5px;
            }
            
            .stat-label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
                overflow: hidden;
            }
            
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e9ecef;
            }
            
            th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
            }
            
            tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            tr:hover {
                background: #e9ecef;
            }
            
            .badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .badge-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .badge-warning {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }
            
            .badge-danger {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #e9ecef;
                text-align: center;
                color: #666;
                font-size: 10px;
            }
            
            .highlight {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2px 6px;
                border-radius: 4px;
                font-weight: 600;
            }
            
            .no-data {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }
            
            .amount {
                font-weight: 600;
                color: #28a745;
            }
            
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .header {
                    background: #667eea !important;
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                th {
                    background: #667eea !important;
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .badge-success, .badge-warning, .badge-danger {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .stat-card {
                    page-break-inside: avoid;
                }
                
                table {
                    page-break-inside: auto;
                }
                
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . $title . '</h1>
            <div class="subtitle">Généré le ' . date('d/m/Y à H:i') . ' | TAAJ Corp - Système de Gestion Académique</div>
        </div>';
    
    if ($reportType === 'summary') {
        // Afficher les statistiques résumées
        $html .= '<div class="summary-stats">';
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $label = '';
                switch ($key) {
                    case 'total_students': $label = 'Total Étudiants'; break;
                    case 'total_programs': $label = 'Total Programmes'; break;
                    case 'total_registrations': $label = 'Total Inscriptions'; break;
                    case 'paid_registrations': $label = 'Inscriptions Payées'; break;
                    case 'total_revenue': $label = 'Revenus Totaux'; break;
                    default: $label = ucfirst(str_replace('_', ' ', $key));
                }
                
                $displayValue = is_numeric($value) && $key !== 'id' ? number_format($value, 0, ',', ' ') : $value;
                if ($key === 'total_revenue') {
                    $displayValue = number_format($value, 2, ',', ' ') . ' FCFA';
                }
                
                $html .= '
                <div class="stat-card">
                    <div class="stat-number">' . $displayValue . '</div>
                    <div class="stat-label">' . $label . '</div>
                </div>';
            }
        }
        $html .= '</div>';
    } else {
        // Afficher le tableau de données
        $html .= '<table>';
        
        // En-têtes
        if (!empty($data)) {
            $html .= '<thead><tr>';
            foreach (array_keys($data[0]) as $key) {
                $headerText = ucfirst(str_replace('_', ' ', $key));
                switch ($headerText) {
                    case 'First name': $headerText = 'Prénom'; break;
                    case 'Last name': $headerText = 'Nom'; break;
                    case 'Student name': $headerText = 'Étudiant'; break;
                    case 'Program name': $headerText = 'Programme'; break;
                    case 'Payment status': $headerText = 'Statut paiement'; break;
                    case 'Payment date': $headerText = 'Date paiement'; break;
                    case 'Registration date': $headerText = 'Date inscription'; break;
                    case 'Date of birth': $headerText = 'Date naissance'; break;
                    case 'Student id card': $headerText = 'Carte étudiant'; break;
                    case 'Registration count': $headerText = 'Nb. inscriptions'; break;
                    case 'Total paid': $headerText = 'Total payé'; break;
                    case 'Total revenue': $headerText = 'Revenus totaux'; break;
                    case 'Max students': $headerText = 'Capacité max'; break;
                    case 'Duration months': $headerText = 'Durée (mois)'; break;
                    case 'Description': $headerText = 'Description'; break;
                    case 'Requirements': $headerText = 'Prérequis'; break;
                    case 'Objectives': $headerText = 'Objectifs'; break;
                    case 'Level': $headerText = 'Niveau'; break;
                    case 'Status': $headerText = 'Statut'; break;
                    case 'Active': $headerText = 'Actif'; break;
                    case 'Reference': $headerText = 'Référence'; break;
                    case 'Amount': $headerText = 'Montant'; break;
                }
                $html .= '<th>' . htmlspecialchars($headerText) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            // Données
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $key => $value) {
                    $displayValue = $value;
                    
                    // Formatter certaines valeurs
                    if ($key === 'payment_status') {
                        switch($value) {
                            case 'paid': 
                                $displayValue = '<span class="badge badge-success">Payé</span>'; 
                                break;
                            case 'pending': 
                                $displayValue = '<span class="badge badge-warning">En attente</span>'; 
                                break;
                            case 'unpaid': 
                                $displayValue = '<span class="badge badge-danger">Non payé</span>'; 
                                break;
                            default: 
                                $displayValue = htmlspecialchars($value);
                        }
                    } elseif ($key === 'status') {
                        switch($value) {
                            case 'active': 
                                $displayValue = '<span class="badge badge-success">Actif</span>'; 
                                break;
                            case 'inactive': 
                                $displayValue = '<span class="badge badge-danger">Inactif</span>'; 
                                break;
                            case 'pending': 
                                $displayValue = '<span class="badge badge-warning">En attente</span>'; 
                                break;
                            default: 
                                $displayValue = htmlspecialchars($value);
                        }
                    } elseif ($key === 'active') {
                        $displayValue = $value ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-danger">Non</span>';
                    } elseif (is_numeric($value) && $key !== 'id') {
                        if (strpos($key, 'amount') !== false || strpos($key, 'revenue') !== false || strpos($key, 'paid') !== false || strpos($key, 'price') !== false) {
                            $displayValue = '<span class="amount">' . number_format($value, 2, ',', ' ') . ' FCFA</span>';
                        } else {
                            $displayValue = number_format($value, 0, ',', ' ');
                        }
                    } elseif (in_array($key, ['description', 'requirements', 'objectives'])) {
                        $displayValue = htmlspecialchars(substr($value, 0, 100) . (strlen($value) > 100 ? '...' : ''));
                    } else {
                        $displayValue = htmlspecialchars($value);
                    }
                    
                    $html .= '<td>' . $displayValue . '</td>';
                }
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="100%" class="no-data">Aucune donnée disponible</td></tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    $html .= '
        <div class="footer">
            <p><strong>TAAJ Corp - Système de Gestion Académique</strong></p>
            <p>Rapport généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
            <p>Pour toute question, contactez l\'administration</p>
            <p>Page ' . date('d/m/Y') . ' - Document confidentiel</p>
        </div>
        
        <script>
            // Auto-print et close après impression
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                    
                    // Fermer la fenêtre après impression
                    window.addEventListener(\'afterprint\', function() {
                        window.close();
                    });
                    
                    // Fallback pour les navigateurs qui ne supportent pas afterprint
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                }, 500);
            };
        </script>
    </body>
    </html>';
    
    return $html;
}
?>
