<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['exists' => false, 'error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['email']) || empty($_GET['email'])) {
    echo json_encode(['exists' => false, 'error' => 'Email no proporcionado']);
    exit;
}

$email = sanitizeInput($_GET['email']);
$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por email (excluyendo al usuario actual)
    $query = "SELECT id, nombre, apellidos, tipo, activo FROM usuarios WHERE email = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email, $user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && $usuario['activo']) {
        echo json_encode([
            'exists' => true,
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellidos'],
            'tipo' => ucfirst($usuario['tipo'])
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => 'Error en el servidor']);
}
?>
