<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isConsumidor()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('mis-solicitudes.php');
}

$solicitud_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar permisos
    $query = "SELECT * FROM solicitudes_consumidores WHERE id = ? AND consumidor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$solicitud_id, $consumidor['id']]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada', 'danger');
        redirect('mis-solicitudes.php');
    }
    
    // Cancelar solicitud
    $updateQuery = "UPDATE solicitudes_consumidores SET estado = 'cancelada' WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$solicitud_id]);
    
    showAlert('Solicitud cancelada correctamente', 'success');
    redirect('mis-solicitudes.php');
    
} catch (Exception $e) {
    showAlert('Error: ' . $e->getMessage(), 'danger');
    redirect('mis-solicitudes.php');
}
?>
