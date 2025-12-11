<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    redirect('../dashboard.php');
}

// Verificar parámetro
if (!isset($_GET['id'])) {
    redirect('mis-reservas.php');
}

$reserva_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Obtener ID del consumidor
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Verificar que la reserva pertenece al consumidor
    $query = "SELECT * FROM reservas WHERE id = ? AND consumidor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$reserva_id, $consumidor_id]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        showAlert('Reserva no encontrada o no tienes permiso', 'danger');
        redirect('mis-reservas.php');
    }
    
    if ($reserva['estado'] === 'cancelada' || $reserva['estado'] === 'completada') {
        showAlert('Esta reserva no se puede cancelar', 'warning');
        redirect('mis-reservas.php');
    }
    
    // Verificar tiempo de cancelación (opcional - ajustar según política)
    $fecha_actividad = strtotime($reserva['fecha_actividad']);
    $ahora = time();
    $horas_diferencia = ($fecha_actividad - $ahora) / 3600;
    
    if ($horas_diferencia < 24) {
        showAlert('No se puede cancelar con menos de 24 horas de antelación', 'danger');
        redirect('mis-reservas.php');
    }
    
    // Obtener información del ofertante para el mensaje
    $ofertanteQuery = "SELECT o.usuario_id as ofertante_usuario_id, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos,
                      a.titulo as actividad_titulo, r.fecha_actividad, r.num_participantes
                      FROM reservas r
                      JOIN actividades a ON r.actividad_id = a.id
                      JOIN ofertantes o ON a.ofertante_id = o.id
                      JOIN usuarios u ON o.usuario_id = u.id
                      WHERE r.id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$reserva_id]);
    $ofertanteInfo = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    
    // Cancelar reserva
    $updateQuery = "UPDATE reservas SET estado = 'cancelada' WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$reserva_id]);
    
    // Devolver las plazas
    $updateDispQuery = "UPDATE disponibilidad_actividades 
                       SET plazas_disponibles = plazas_disponibles + ?,
                           estado = 'disponible'
                       WHERE id = ?";
    $updateDispStmt = $db->prepare($updateDispQuery);
    $updateDispStmt->execute([$reserva['num_participantes'], $reserva['disponibilidad_id']]);
    
    // Enviar mensaje al ofertante informando de la cancelación
    if ($ofertanteInfo) {
        $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) VALUES (?, ?, ?, ?)";
        $mensajeStmt = $db->prepare($mensajeQuery);
        
        $asunto = "Reserva cancelada: " . $ofertanteInfo['actividad_titulo'];
        $cuerpoMensaje = "Hola " . $ofertanteInfo['ofertante_nombre'] . " " . $ofertanteInfo['ofertante_apellidos'] . ",\n\n"
            . "Te informamos que un consumidor ha cancelado su reserva para la actividad '" . $ofertanteInfo['actividad_titulo'] . "'.\n\n"
            . "Detalles de la reserva cancelada:\n"
            . "- Fecha de la actividad: " . formatDateTime($ofertanteInfo['fecha_actividad']) . "\n"
            . "- Número de participantes: " . $ofertanteInfo['num_participantes'] . "\n\n"
            . "Las plazas han sido liberadas y están disponibles nuevamente para otras reservas.\n\n"
            . "Puedes revisar todas tus reservas desde tu panel en la sección 'Mis Reservas'.\n\n"
            . "Gracias por usar ActividadesConnect.";
        
        $mensajeStmt->execute([
            $user_id, // consumidor que cancela
            $ofertanteInfo['ofertante_usuario_id'],
            $asunto,
            $cuerpoMensaje
        ]);
    }
    
    showAlert('Reserva cancelada correctamente', 'success');
    redirect('mis-reservas.php');
    
} catch (Exception $e) {
    showAlert('Error al cancelar la reserva: ' . $e->getMessage(), 'danger');
    redirect('mis-reservas.php');
}
?>
