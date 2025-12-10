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
    $query = "SELECT a.*, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
              FROM actividades a
              JOIN ofertantes o ON a.ofertante_id = o.id
              JOIN usuarios u ON o.usuario_id = u.id
              WHERE a.id = ? AND a.ofertante_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$actividad_id, $ofertante_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        showAlert('Actividad no encontrada o no tienes permiso para cancelarla', 'danger');
        redirect('mis-actividades.php');
    }
    
    if ($actividad['estado'] === 'cancelada') {
        showAlert('Esta actividad ya está cancelada', 'warning');
        redirect('mis-actividades.php');
    }
    
    // Obtener reservas activas
    $reservasQuery = "SELECT r.*, c.usuario_id as consumidor_usuario_id, u.nombre, u.apellidos, u.email
                      FROM reservas r
                      JOIN consumidores c ON r.consumidor_id = c.id
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE r.actividad_id = ? AND r.estado IN ('pendiente', 'confirmada')";
    $reservasStmt = $db->prepare($reservasQuery);
    $reservasStmt->execute([$actividad_id]);
    $reservas = $reservasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Cancelar todas las reservas activas
        if (!empty($reservas)) {
            $cancelarReservasQuery = "UPDATE reservas SET estado = 'cancelada' 
                                     WHERE actividad_id = ? AND estado IN ('pendiente', 'confirmada')";
            $cancelarReservasStmt = $db->prepare($cancelarReservasQuery);
            $cancelarReservasStmt->execute([$actividad_id]);
            
            // Devolver plazas a la disponibilidad
            $devolverPlazasQuery = "UPDATE disponibilidad_actividades da
                                   SET plazas_disponibles = plazas_disponibles + (
                                       SELECT COALESCE(SUM(r.num_participantes), 0)
                                       FROM reservas r
                                       WHERE r.disponibilidad_id = da.id 
                                       AND r.actividad_id = ?
                                       AND r.estado = 'cancelada'
                                   )
                                   WHERE da.actividad_id = ?";
            $devolverPlazasStmt = $db->prepare($devolverPlazasQuery);
            $devolverPlazasStmt->execute([$actividad_id, $actividad_id]);
        }
        
        // Cancelar la actividad
        $updateActividadQuery = "UPDATE actividades SET estado = 'cancelada' WHERE id = ?";
        $updateActividadStmt = $db->prepare($updateActividadQuery);
        $updateActividadStmt->execute([$actividad_id]);
        
        // Cancelar todas las fechas de disponibilidad
        $cancelarDisponibilidadQuery = "UPDATE disponibilidad_actividades SET estado = 'cancelado' WHERE actividad_id = ?";
        $cancelarDisponibilidadStmt = $db->prepare($cancelarDisponibilidadQuery);
        $cancelarDisponibilidadStmt->execute([$actividad_id]);
        
        // Enviar mensajes directos a los consumidores afectados
        if (!empty($reservas)) {
            $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) 
                             VALUES (?, ?, ?, ?)";
            $mensajeStmt = $db->prepare($mensajeQuery);
            
            foreach ($reservas as $reserva) {
                $asunto = "Actividad cancelada: " . $actividad['titulo'];
                $cuerpoMensaje = "Hola " . $reserva['nombre'] . " " . $reserva['apellidos'] . ",\n\n"
                    . "Lamentamos informarte que la actividad '" . $actividad['titulo'] . "' ha sido cancelada por el ofertante "
                    . $actividad['ofertante_nombre'] . " " . $actividad['ofertante_apellidos'] . ".\n"
                    . "Tu reserva ha sido marcada como cancelada automáticamente. Si corresponde algún reembolso, "
                    . "se gestionará según las condiciones acordadas.\n\n"
                    . "Puedes buscar otras actividades similares en la plataforma desde el listado de actividades o desde tu panel.\n\n"
                    . "Gracias por confiar en ActividadesConnect.";
                
                $mensajeStmt->execute([
                    $user_id, // ofertante que cancela
                    $reserva['consumidor_usuario_id'],
                    $asunto,
                    $cuerpoMensaje
                ]);
            }
        }
        
        // Confirmar transacción
        $db->commit();
        
        if (!empty($reservas)) {
            showAlert('Actividad cancelada correctamente. Se han cancelado ' . count($reservas) . ' reserva(s) y se han enviado notificaciones a los consumidores afectados.', 'success');
        } else {
            showAlert('Actividad cancelada correctamente', 'success');
        }
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        throw $e;
    }
    
    redirect('mis-actividades.php');
    
} catch (Exception $e) {
    showAlert('Error al cancelar la actividad: ' . $e->getMessage(), 'danger');
    redirect('mis-actividades.php');
}
?>
