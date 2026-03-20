<?php
// Test direct de l'API avec debug
echo "<h1>TEST API DEBUG</h1>";

// Test 1: Appel direct
echo "<h2>1. Test direct de l'API</h2>";
echo "<pre>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents('http://localhost/Projet%20TATOU/api_crud_students_fixed.php', false, $context);
echo "Réponse brute: " . $response . "\n";

$data = json_decode($response, true);
echo "JSON valide: " . ($data !== null ? 'true' : 'false') . "\n";
if ($data) {
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    if (isset($data['debug_info'])) {
        echo "Debug info:\n";
        print_r($data['debug_info']);
    }
}
echo "</pre>";

// Test 2: Vérification de la session
echo "<h2>2. Vérification de la session</h2>";
echo "<pre>";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session data: ";
print_r($_SESSION);
echo "</pre>";

// Test 3: Test de connexion DB
echo "<h2>3. Test de connexion DB</h2>";
echo "<pre>";
try {
    include 'db/mysql_connection_gestion_inscription.php';
    echo "✅ Connexion DB réussie\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch()['count'];
    echo "✅ Nombre d'étudiants: $count\n";
    
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM students LIMIT 3");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Échantillon d'étudiants:\n";
    foreach ($students as $student) {
        echo "  - ID: {$student['id']}, Nom: {$student['first_name']} {$student['last_name']}, Email: {$student['email']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 4: Vérification des logs d'erreur PHP
echo "<h2>4. Vérification des erreurs PHP</h2>";
echo "<pre>";
$phpErrorLog = ini_get('error_log');
echo "Fichier de log d'erreurs: $phpErrorLog\n";
echo "Affichage des erreurs: " . (ini_get('display_errors') ? 'true' : 'false') . "\n";
echo "Niveau d'erreurs: " . ini_get('error_reporting') . "\n";
echo "</pre>";

// Test 5: Test avec cURL (simule le fetch JavaScript)
echo "<h2>5. Test avec cURL (simule fetch)</h2>";
echo "<pre>";
$ch = curl_init('http://localhost/Projet%20TATOU/api_crud_students_fixed.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$curlResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

echo "cURL HTTP Code: $httpCode\n";
echo "cURL Error: '$curlError'\n";
echo "cURL Response: '$curlResponse'\n";
echo "cURL Response Length: " . strlen($curlResponse) . "\n";

if ($curlResponse) {
    $curlData = json_decode($curlResponse, true);
    echo "cURL JSON valide: " . ($curlData !== null ? 'true' : 'false') . "\n";
    if ($curlData && isset($curlData['debug_info'])) {
        echo "cURL Debug info:\n";
        print_r($curlData['debug_info']);
    }
}
echo "</pre>";
?>
