<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isConsumidor()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('../solicitudes/mis-solicitudes.php');
}

$propuesta_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar propuesta y permisos
    $query = "SELECT p.*, s.consumidor_id, s.id as solicitud_id
              FROM propuestas_ofertantes p
              JOIN solicitudes_consumidores s ON p.solicitud_id = s.id
              WHERE p.id = ? AND s.consumidor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$propuesta_id, $consumidor['id']]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        showAlert('Propuesta no encontrada', 'danger');
        redirect('../solicitudes/mis-solicitudes.php');
    }
    
    // Rechazar propuesta
    $updateQuery = "UPDATE propuestas_ofertantes SET estado = 'rechazada' WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$propuesta_id]);
    
    showAlert('Propuesta rechazada', 'info');
    redirect('../solicitudes/ver-propuestas.php?solicitud_id=' . $propuesta['solicitud_id']);
    
} catch (Exception $e) {
    showAlert('Error: ' . $e->getMessage(), 'danger');
    redirect('../solicitudes/mis-solicitudes.php');
}
?>
