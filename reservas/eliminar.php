<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Verificar parámetro
if (!isset($_GET['id'])) {
    redirect('mis-reservas.php');
}

$reserva_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

try {
    if ($user_type === 'consumidor') {
        // Obtener ID del consumidor
        $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$user_id]);
        $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        $consumidor_id = $consumidor['id'];
        
        // Verificar que la reserva pertenece al consumidor y está cancelada
        $query = "SELECT r.*, a.estado as actividad_estado
                  FROM reservas r
                  JOIN actividades a ON r.actividad_id = a.id
                  WHERE r.id = ? AND r.consumidor_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$reserva_id, $consumidor_id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reserva) {
            showAlert('Reserva no encontrada o no tienes permiso', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Solo se pueden eliminar reservas canceladas
        if ($reserva['estado'] !== 'cancelada') {
            showAlert('Solo puedes eliminar reservas canceladas', 'warning');
            redirect('mis-reservas.php');
        }
        
    } elseif ($user_type === 'ofertante') {
        // Obtener ID del ofertante
        $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
        $ofertanteStmt = $db->prepare($ofertanteQuery);
        $ofertanteStmt->execute([$user_id]);
        $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
        $ofertante_id = $ofertante['id'];
        
        // Verificar que la reserva pertenece a una actividad del ofertante y está cancelada
        $query = "SELECT r.*, a.ofertante_id, a.estado as actividad_estado
                  FROM reservas r
                  JOIN actividades a ON r.actividad_id = a.id
                  WHERE r.id = ? AND a.ofertante_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$reserva_id, $ofertante_id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reserva) {
            showAlert('Reserva no encontrada o no tienes permiso', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Solo se pueden eliminar reservas canceladas
        if ($reserva['estado'] !== 'cancelada') {
            showAlert('Solo puedes eliminar reservas canceladas', 'warning');
            redirect('mis-reservas.php');
        }
    } else {
        showAlert('No tienes permiso para realizar esta acción', 'danger');
        redirect('mis-reservas.php');
    }
    
    // Eliminar la reserva (los participantes se eliminarán automáticamente por ON DELETE CASCADE)
    $deleteQuery = "DELETE FROM reservas WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$reserva_id]);
    
    showAlert('Reserva eliminada correctamente', 'success');
    redirect('mis-reservas.php');
    
} catch (Exception $e) {
    showAlert('Error al eliminar la reserva: ' . $e->getMessage(), 'danger');
    redirect('mis-reservas.php');
}
?>

