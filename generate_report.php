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
    
    switch ($reportType) {
        case 'students':
            // Rapport des étudiants
            $stmt = $pdo->query("
                SELECT s.*, COUNT(r.id) as registration_count,
                       SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_paid
                FROM students s
                LEFT JOIN registrations r ON s.id = r.student_id
                GROUP BY s.id
                ORDER BY s.first_name, s.last_name
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $title = 'Rapport des Étudiants';
            break;
            
        case 'registrations':
            // Rapport des inscriptions
            $stmt = $pdo->query("
                SELECT r.*, s.first_name, s.last_name, s.email, p.name as program_name
                FROM registrations r
                LEFT JOIN students s ON r.student_id = s.id
                LEFT JOIN programs p ON r.program_id = p.id
                ORDER BY r.created_at DESC
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
    
    if ($format === 'pdf') {
        // Vérifier si la bibliothèque Mpdf est disponible
        if (!file_exists('vendor/autoload.php')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'La génération PDF nécessite l\'installation de Composer. Veuillez exécuter: composer install'
            ]);
            exit;
        }
        
        // Générer le PDF
        require_once('vendor/autoload.php');
        
        // Créer le PDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        
        // HTML du rapport
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8"/>
            <title>' . $title . '</title>
            <style>
                @page {
                    margin: 20px;
                    @bottom-center {
                        content: "Page " counter(page) . " sur " counter(pages);
                        font-size: 10px;
                        color: #666;
                    }
                }
                
                body { 
                    font-family: "Helvetica Neue", Arial, sans-serif; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 0;
                    color: #333;
                    background: #fff;
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
                
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                }
                
                .info-card {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    border-left: 4px solid #667eea;
                }
                
                .info-card h3 {
                    margin: 0 0 10px 0;
                    color: #667eea;
                    font-size: 14px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                .info-card .value {
                    font-size: 24px;
                    font-weight: 700;
                    color: #333;
                }
                
                .info-card .description {
                    font-size: 11px;
                    color: #666;
                    margin-top: 5px;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 30px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                th { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white; 
                    padding: 15px 12px; 
                    text-align: left; 
                    font-weight: 600;
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                td { 
                    padding: 12px; 
                    border-bottom: 1px solid #e9ecef;
                    font-size: 11px;
                }
                
                tr:nth-child(even) {
                    background: #f8f9fa;
                }
                
                tr:hover {
                    background: #e3f2fd;
                }
                
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .badge {
                    display: inline-block;
                    padding: 4px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                .badge-success {
                    background: #d4edda;
                    color: #155724;
                }
                
                .badge-warning {
                    background: #fff3cd;
                    color: #856404;
                }
                
                .badge-danger {
                    background: #f8d7da;
                    color: #721c24;
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
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . $title . '</h1>
                <div class="subtitle">Généré le ' . date('d/m/Y à H:i') . ' | TAAJ Corp</div>
            </div>';
        
        if ($reportType === 'summary') {
            // Rapport résumé
            $html .= '
            <div class="info-grid">
                <div class="info-card">
                    <h3>Total Étudiants</h3>
                    <div class="value">' . number_format($data['total_students']) . '</div>
                    <div class="description">Inscrits dans la plateforme</div>
                </div>
                <div class="info-card">
                    <h3>Total Programmes</h3>
                    <div class="value">' . number_format($data['total_programs']) . '</div>
                    <div class="description">Programmes académiques disponibles</div>
                </div>
                <div class="info-card">
                    <h3>Total Inscriptions</h3>
                    <div class="value">' . number_format($data['total_registrations']) . '</div>
                    <div class="description">Inscriptions actives</div>
                </div>
                <div class="info-card">
                    <h3>Inscriptions Payées</h3>
                    <div class="value">' . number_format($data['paid_registrations']) . '</div>
                    <div class="description">Paiements confirmés</div>
                </div>
                <div class="info-card">
                    <h3>Revenus Totaux</h3>
                    <div class="value">' . number_format($data['total_revenue'], 0, ',', ' ') . '</div>
                    <div class="description">FCFA</div>
                </div>
            </div>';
        } else {
            // Tableau de données avec design amélioré
            $html .= '<table>';
            
            // En-têtes du tableau
            if (!empty($data)) {
                $html .= '<thead><tr>';
                foreach (array_keys($data[0]) as $key) {
                    $headerText = ucwords(str_replace('_', ' ', $key));
                    // Traduire certains en-têtes
                    switch($headerText) {
                        case 'Id': $headerText = 'ID'; break;
                        case 'First name': $headerText = 'Prénom'; break;
                        case 'Last name': $headerText = 'Nom'; break;
                        case 'Email': $headerText = 'Email'; break;
                        case 'Phone': $headerText = 'Téléphone'; break;
                        case 'Created at': $headerText = 'Date création'; break;
                        case 'Updated at': $headerText = 'Date mise à jour'; break;
                        case 'Payment status': $headerText = 'Statut paiement'; break;
                        case 'Payment date': $headerText = 'Date paiement'; break;
                        case 'Current students': $headerText = 'Étudiants actuels'; break;
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
                            $displayValue = number_format($value, 0, ',', ' ');
                        } else {
                            $displayValue = htmlspecialchars($value);
                        }
                        
                        $html .= '<td>' . $displayValue . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
            }
            
            $html .= '</table>';
        }
        
        // Ajouter un footer professionnel
        $html .= '
            <div class="footer">
                <p><strong>TAAJ Corp - Système de Gestion Académique</strong></p>
                <p>Rapport généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
                <p>Pour toute question, contactez l\'administration</p>
            </div>
        </body>
        </html>';
        
        // Générer le PDF
        $mpdf->WriteHTML($html);
        
        // Nom du fichier
        $filename = $title . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Sortie du PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $mpdf->Output($filename, 'D');
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
?>
