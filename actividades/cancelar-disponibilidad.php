<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('mis-actividades.php');
}

$disp_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar permisos
    $query = "SELECT d.*, a.ofertante_id, a.id as actividad_id
              FROM disponibilidad_actividades d
              JOIN actividades a ON d.actividad_id = a.id
              WHERE d.id = ? AND a.ofertante_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$disp_id, $ofertante['id']]);
    $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disponibilidad) {
        showAlert('Disponibilidad no encontrada', 'danger');
        redirect('mis-actividades.php');
    }
    
    // Verificar si hay reservas para informar al usuario
    $reservasQuery = "SELECT COUNT(*) as total FROM reservas 
                      WHERE disponibilidad_id = ? AND estado IN ('pendiente', 'confirmada')";
    $reservasStmt = $db->prepare($reservasQuery);
    $reservasStmt->execute([$disp_id]);
    $reservas = $reservasStmt->fetch(PDO::FETCH_ASSOC);
    
    // Si hay reservas, cancelarlas automáticamente y enviar notificaciones
    if ($reservas['total'] > 0) {
        // Obtener detalles de las reservas para las notificaciones ANTES de cancelarlas
        $reservasDetalleQuery = "SELECT r.*, c.usuario_id as consumidor_usuario_id, u.nombre, u.apellidos, u.email,
                                        a.titulo as actividad_titulo, o.usuario_id as ofertante_usuario_id,
                                        of.nombre as ofertante_nombre, of.apellidos as ofertante_apellidos
                                 FROM reservas r
                                 JOIN consumidores c ON r.consumidor_id = c.id
                                 JOIN usuarios u ON c.usuario_id = u.id
                                 JOIN actividades a ON r.actividad_id = a.id
                                 JOIN ofertantes o ON a.ofertante_id = o.id
                                 JOIN usuarios of ON o.usuario_id = of.id
                                 WHERE r.disponibilidad_id = ? AND r.estado IN ('pendiente', 'confirmada')";
        $reservasDetalleStmt = $db->prepare($reservasDetalleQuery);
        $reservasDetalleStmt->execute([$disp_id]);
        $reservasDetalle = $reservasDetalleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cancelar reservas
        $cancelarReservasQuery = "UPDATE reservas SET estado = 'cancelada' 
                                 WHERE disponibilidad_id = ? AND estado IN ('pendiente', 'confirmada')";
        $cancelarReservasStmt = $db->prepare($cancelarReservasQuery);
        $cancelarReservasStmt->execute([$disp_id]);
        
        // Enviar mensajes directos a los consumidores afectados
        $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) 
                         VALUES (?, ?, ?, ?)";
        $mensajeStmt = $db->prepare($mensajeQuery);
        
        $notificacionesCreadas = 0;
        foreach ($reservasDetalle as $reserva) {
            $asunto = "Fecha cancelada: " . $reserva['actividad_titulo'];
            $cuerpoMensaje = "Hola " . $reserva['nombre'] . " " . $reserva['apellidos'] . ",\n\n"
                . "Lamentamos informarte que una de las fechas de la actividad '" . $reserva['actividad_titulo'] . "' ha sido cancelada por el ofertante "
                . $reserva['ofertante_nombre'] . " " . $reserva['ofertante_apellidos'] . ".\n"
                . "Tu reserva asociada a esa fecha ha sido marcada como cancelada automáticamente.\n\n"
                . "Puedes buscar otras fechas disponibles para esta misma actividad o actividades similares desde la plataforma.\n\n"
                . "Gracias por confiar en ActividadesConnect.";
            
            try {
                $mensajeStmt->execute([
                    $reserva['ofertante_usuario_id'],
                    $reserva['consumidor_usuario_id'],
                    $asunto,
                    $cuerpoMensaje
                ]);
                $notificacionesCreadas++;
            } catch (Exception $e) {
                error_log("Error creando mensaje para usuario " . $reserva['consumidor_usuario_id'] . ": " . $e->getMessage());
            }
        }
    }
    
    // Cancelar disponibilidad
    $updateQuery = "UPDATE disponibilidad_actividades SET estado = 'cancelado' WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$disp_id]);
    
    if ($reservas['total'] > 0) {
        $mensaje = 'Fecha cancelada correctamente. Se han cancelado ' . $reservas['total'] . ' reserva(s) asociada(s).';
        if (isset($notificacionesCreadas) && $notificacionesCreadas > 0) {
            $mensaje .= ' Se han enviado ' . $notificacionesCreadas . ' notificación(es) a los consumidores afectados.';
        }
        showAlert($mensaje, 'success');
    } else {
        showAlert('Fecha cancelada correctamente', 'success');
    }
    redirect('disponibilidad.php?actividad_id=' . $disponibilidad['actividad_id']);
    
} catch (Exception $e) {
    showAlert('Error: ' . $e->getMessage(), 'danger');
    redirect('mis-actividades.php');
}
?>
