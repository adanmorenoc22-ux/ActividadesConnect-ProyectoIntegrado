<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Solo ofertantes pueden gestionar sus propias actividades
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('mis-actividades.php');
}

$disp_id = (int) $_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener el ID del ofertante actual
    $ofertanteStmt = $db->prepare("SELECT id FROM ofertantes WHERE usuario_id = ?");
    $ofertanteStmt->execute([$_SESSION['user_id']]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);

    if (!$ofertante) {
        showAlert('No se ha encontrado el perfil de ofertante.', 'danger');
        redirect('../dashboard.php');
    }

    // Verificar que la disponibilidad pertenezca a una actividad del ofertante
    $query = "SELECT d.id, d.estado, d.actividad_id, a.ofertante_id
              FROM disponibilidad_actividades d
              JOIN actividades a ON d.actividad_id = a.id
              WHERE d.id = ? AND a.ofertante_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$disp_id, $ofertante['id']]);
    $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$disponibilidad) {
        showAlert('La fecha de disponibilidad no existe o no tienes permisos para gestionarla.', 'danger');
        redirect('mis-actividades.php');
    }

    // Solo se pueden eliminar fechas que ya han sido canceladas
    if ($disponibilidad['estado'] !== 'cancelado') {
        showAlert('Solo puedes eliminar fechas que ya han sido canceladas.', 'warning');
        redirect('disponibilidad.php?actividad_id=' . $disponibilidad['actividad_id']);
    }

    // Eliminar la disponibilidad (las reservas asociadas se eliminarán por la relación ON DELETE CASCADE)
    $deleteStmt = $db->prepare("DELETE FROM disponibilidad_actividades WHERE id = ?");
    $deleteStmt->execute([$disponibilidad['id']]);

    showAlert('La fecha cancelada ha sido eliminada correctamente.', 'success');
    redirect('disponibilidad.php?actividad_id=' . $disponibilidad['actividad_id']);

} catch (Exception $e) {
    showAlert('Error al eliminar la fecha: ' . $e->getMessage(), 'danger');
    redirect('mis-actividades.php');
}


