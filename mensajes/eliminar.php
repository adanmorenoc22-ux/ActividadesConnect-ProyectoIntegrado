<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Configurar headers para JSON
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener ID del mensaje
$mensaje_id = isset($_POST['mensaje_id']) ? (int)$_POST['mensaje_id'] : 0;

if ($mensaje_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de mensaje inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    
    $es_remitente = isset($_POST['es_remitente']) ? (int)$_POST['es_remitente'] : 0;
    $eliminar_permanente = isset($_POST['eliminar_permanente']) ? (int)$_POST['eliminar_permanente'] : 0;
    
    // Verificar que el mensaje pertenece al usuario
    $verificarQuery = "SELECT id, remitente_id, destinatario_id FROM mensajes 
                       WHERE id = ? AND (remitente_id = ? OR destinatario_id = ?)";
    $verificarStmt = $db->prepare($verificarQuery);
    $verificarStmt->execute([$mensaje_id, $user_id, $user_id]);
    $mensaje = $verificarStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mensaje) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este mensaje']);
        exit;
    }
    
    // Si es eliminación permanente, borrar físicamente el mensaje
    if ($eliminar_permanente) {
        $eliminarQuery = "DELETE FROM mensajes WHERE id = ?";
        $eliminarStmt = $db->prepare($eliminarQuery);
        $result = $eliminarStmt->execute([$mensaje_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Mensaje eliminado permanentemente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el mensaje']);
        }
    } else {
        // Marcar como eliminado (mover a papelera)
        $campo = ($mensaje['remitente_id'] == $user_id) ? 'eliminado_remitente' : 'eliminado_destinatario';
        $updateQuery = "UPDATE mensajes SET {$campo} = 1 WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $result = $updateStmt->execute([$mensaje_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Mensaje movido a papelera']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el mensaje']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
