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
$format = $_GET['format'] ?? 'html';

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
    
    if ($format === 'html') {
        // Générer le HTML
        $html = generateHTMLReport($title, $data, $reportType);
        
        // Afficher le HTML
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
        
    } elseif ($format === 'csv') {
        // Générer le CSV
        $csv = generateCSVReport($title, $data, $reportType);
        
        // Afficher le CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d_H-i-s') . '.csv"');
        echo $csv;
        exit;
        
    } else {
        // Export JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'title' => $title,
            'data' => $data,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

function generateHTMLReport($title, $data, $reportType) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8"/>
        <title>' . $title . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                font-size: 12px; 
                margin: 20px; 
                color: #333;
            }
            
            .header {
                background: #667eea;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            
            .header .subtitle {
                margin: 5px 0 0 0;
                opacity: 0.9;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            
            th {
                background: #f8f9fa;
                font-weight: bold;
            }
            
            tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
            }
            
            .badge-success { background: #d4edda; color: #155724; }
            .badge-warning { background: #fff3cd; color: #856404; }
            .badge-danger { background: #f8d7da; color: #721c24; }
            
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #666;
                font-size: 10px;
            }
            
            .summary-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            
            .stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border-left: 4px solid #667eea;
            }
            
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
            }
            
            .stat-label {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . $title . '</h1>
            <div class="subtitle">Généré le ' . date('d/m/Y à H:i') . ' | TAAJ Corp</div>
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
                            default: 
                                $displayValue = htmlspecialchars($value);
                        }
                    } elseif (is_numeric($value) && $key !== 'id') {
                        if (strpos($key, 'amount') !== false || strpos($key, 'revenue') !== false || strpos($key, 'paid') !== false) {
                            $displayValue = number_format($value, 2, ',', ' ') . ' FCFA';
                        } else {
                            $displayValue = number_format($value, 0, ',', ' ');
                        }
                    } else {
                        $displayValue = htmlspecialchars($value);
                    }
                    
                    $html .= '<td>' . $displayValue . '</td>';
                }
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="100%">Aucune donnée disponible</td></tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    $html .= '
        <div class="footer">
            <p><strong>TAAJ Corp - Système de Gestion Académique</strong></p>
            <p>Rapport généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
            <p>Pour toute question, contactez l\'administration</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function generateCSVReport($title, $data, $reportType) {
    $csv = "\xEF\xBB\xBF"; // BOM pour UTF-8
    $csv .= $title . "\n";
    $csv .= "Généré le " . date('d/m/Y à H:i:s') . "\n\n";
    
    if ($reportType === 'summary') {
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
                $csv .= $label . ';' . $value . "\n";
            }
        }
    } else {
        // En-têtes
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            foreach ($headers as $header) {
                $headerText = ucfirst(str_replace('_', ' ', $header));
                switch ($headerText) {
                    case 'First name': $headerText = 'Prénom'; break;
                    case 'Last name': $headerText = 'Nom'; break;
                    case 'Student name': $headerText = 'Étudiant'; break;
                    case 'Program name': $headerText = 'Programme'; break;
                    case 'Payment status': $headerText = 'Statut paiement'; break;
                    case 'Payment date': $headerText = 'Date paiement'; break;
                    case 'Registration date': $headerText = 'Date inscription'; break;
                }
                $csv .= '"' . $headerText . '";';
            }
            $csv .= "\n";
            
            // Données
            foreach ($data as $row) {
                foreach ($row as $value) {
                    $csv .= '"' . str_replace('"', '""', $value) . '";';
                }
                $csv .= "\n";
            }
        }
    }
    
    return $csv;
}
?>
