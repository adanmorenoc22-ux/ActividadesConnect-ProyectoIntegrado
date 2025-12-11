<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$mensaje_id = isset($_POST['mensaje_id']) ? (int)$_POST['mensaje_id'] : 0;
$es_remitente = isset($_POST['es_remitente']) ? (int)$_POST['es_remitente'] : 0;
$archivar = isset($_POST['archivar']) ? (int)$_POST['archivar'] : 1;

if ($mensaje_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de mensaje inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Verificar que el mensaje pertenece al usuario
    $verificarQuery = "SELECT id, remitente_id, destinatario_id FROM mensajes 
                       WHERE id = ? AND (remitente_id = ? OR destinatario_id = ?)";
    $verificarStmt = $db->prepare($verificarQuery);
    $verificarStmt->execute([$mensaje_id, $user_id, $user_id]);
    $mensaje = $verificarStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mensaje) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para archivar este mensaje']);
        exit;
    }
    
    // Determinar qué campo actualizar
    $campo = ($mensaje['remitente_id'] == $user_id) ? 'archivado_remitente' : 'archivado_destinatario';
    
    // Archivar o desarchivar
    $updateQuery = "UPDATE mensajes SET {$campo} = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $result = $updateStmt->execute([$archivar, $mensaje_id]);
    
    if ($result) {
        $accion = $archivar ? 'archivado' : 'desarchivado';
        echo json_encode(['success' => true, 'message' => "Mensaje {$accion} correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al archivar el mensaje']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>

