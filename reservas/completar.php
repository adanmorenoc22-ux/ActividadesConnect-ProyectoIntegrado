<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

// Verificar parámetros
if (!isset($_GET['id'])) {
    redirect('mis-reservas.php');
}

$reserva_id = (int)$_GET['id'];
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
    
    // Verificar que la reserva pertenece a una actividad del ofertante
    $query = "SELECT r.*, a.ofertante_id, a.titulo as actividad_titulo
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
    
    if ($reserva['estado'] !== 'confirmada') {
        showAlert('Solo puedes completar reservas confirmadas', 'warning');
        redirect('mis-reservas.php');
    }
    
    // Obtener información del consumidor para el mensaje
    $consumidorQuery = "SELECT c.usuario_id, u.nombre, u.apellidos, a.titulo as actividad_titulo, r.fecha_actividad
                       FROM reservas r
                       JOIN consumidores c ON r.consumidor_id = c.id
                       JOIN usuarios u ON c.usuario_id = u.id
                       JOIN actividades a ON r.actividad_id = a.id
                       WHERE r.id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$reserva_id]);
    $consumidorInfo = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    
    // Completar reserva usando la función helper
    if (completarReserva($reserva_id)) {
        // Enviar mensaje al consumidor informando que la actividad fue completada
        if ($consumidorInfo) {
            $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) VALUES (?, ?, ?, ?)";
            $mensajeStmt = $db->prepare($mensajeQuery);
            
            $asunto = "Actividad completada: " . $consumidorInfo['actividad_titulo'];
            $cuerpoMensaje = "Hola " . $consumidorInfo['nombre'] . " " . $consumidorInfo['apellidos'] . ",\n\n"
                . "Te informamos que la actividad '" . $consumidorInfo['actividad_titulo'] . "' "
                . "del " . formatDateTime($consumidorInfo['fecha_actividad']) . " ha sido marcada como completada.\n\n"
                . "Esperamos que hayas disfrutado de la experiencia. Si tienes alguna sugerencia o comentario, "
                . "no dudes en compartirlo con nosotros.\n\n"
                . "¡Gracias por participar y esperamos verte pronto en otra actividad!\n\n"
                . "ActividadesConnect.";
            
            $mensajeStmt->execute([
                $user_id, // ofertante que completa
                $consumidorInfo['usuario_id'],
                $asunto,
                $cuerpoMensaje
            ]);
        }
        
        showAlert('Reserva marcada como completada correctamente', 'success');
    } else {
        showAlert('Error al completar la reserva', 'danger');
    }
    
    redirect('mis-reservas.php');
    
} catch (Exception $e) {
    showAlert('Error al procesar la reserva: ' . $e->getMessage(), 'danger');
    redirect('mis-reservas.php');
}
?>
