<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('mis-actividades.php');
}

$actividad_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Obtener ID del ofertante
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
    
    // Verificar que la actividad pertenece al ofertante
    $query = "SELECT * FROM actividades WHERE id = ? AND ofertante_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$actividad_id, $ofertante_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        showAlert('Actividad no encontrada o no tienes permiso para eliminarla', 'danger');
        redirect('mis-actividades.php');
    }
    
    // Verificar si hay reservas activas
    $reservasQuery = "SELECT COUNT(*) as total FROM reservas 
                      WHERE actividad_id = ? AND estado IN ('pendiente', 'confirmada')";
    $reservasStmt = $db->prepare($reservasQuery);
    $reservasStmt->execute([$actividad_id]);
    $reservas = $reservasStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reservas['total'] > 0) {
        showAlert('No se puede eliminar la actividad porque tiene reservas activas. Cancela las reservas primero o marca la actividad como "Cancelada".', 'danger');
        redirect('mis-actividades.php');
    }
    
    // Eliminar la actividad (esto eliminará en cascada: disponibilidad, reservas completadas/canceladas, reseñas, imágenes)
    $deleteQuery = "DELETE FROM actividades WHERE id = ? AND ofertante_id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$actividad_id, $ofertante_id]);
    
    showAlert('Actividad eliminada correctamente', 'success');
    redirect('mis-actividades.php');
    
} catch (Exception $e) {
    showAlert('Error al eliminar la actividad: ' . $e->getMessage(), 'danger');
    redirect('mis-actividades.php');
}
?>
