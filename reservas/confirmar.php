<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['accion'])) {
    redirect('mis-reservas.php');
}

$reserva_id = (int)$_GET['id'];
$accion = $_GET['accion']; // 'confirmar' o 'rechazar'
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
    $query = "SELECT r.*, a.ofertante_id 
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
    
    if ($reserva['estado'] !== 'pendiente') {
        showAlert('Esta reserva ya fue procesada', 'warning');
        redirect('mis-reservas.php');
    }
    
    if ($accion === 'confirmar') {
        // Obtener información del consumidor para el mensaje ANTES de actualizar
        $consumidorQuery = "SELECT c.usuario_id, u.nombre, u.apellidos, a.titulo as actividad_titulo, r.fecha_actividad, r.num_participantes, r.precio_total
                           FROM reservas r
                           JOIN consumidores c ON r.consumidor_id = c.id
                           JOIN usuarios u ON c.usuario_id = u.id
                           JOIN actividades a ON r.actividad_id = a.id
                           WHERE r.id = ? AND r.estado = 'pendiente'";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$reserva_id]);
        $consumidorInfo = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consumidorInfo) {
            showAlert('No se pudo obtener la información de la reserva o ya fue procesada', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Confirmar SOLO esta reserva específica - usar el ID de la reserva que ya validamos
        // Asegurar que solo se actualice la reserva con el ID exacto y estado pendiente
        $updateQuery = "UPDATE reservas SET estado = 'confirmada', fecha_confirmacion = NOW() WHERE id = ? AND estado = 'pendiente'";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$reserva_id]);
        
        // Verificar que solo se actualizó una fila
        $filasAfectadas = $updateStmt->rowCount();
        if ($filasAfectadas === 0) {
            showAlert('No se pudo confirmar la reserva. Puede que ya haya sido procesada.', 'warning');
            redirect('mis-reservas.php');
        } elseif ($filasAfectadas > 1) {
            // Esto no debería pasar nunca porque id es PRIMARY KEY, pero por seguridad
            showAlert('Error: Se detectó una actualización múltiple. Por favor, contacta con el administrador.', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Enviar mensaje al consumidor confirmando la reserva
        if ($consumidorInfo) {
            $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) VALUES (?, ?, ?, ?)";
            $mensajeStmt = $db->prepare($mensajeQuery);
            
            $asunto = "Reserva confirmada: " . $consumidorInfo['actividad_titulo'];
            $cuerpoMensaje = "Hola " . $consumidorInfo['nombre'] . " " . $consumidorInfo['apellidos'] . ",\n\n"
                . "¡Excelente noticia! Tu reserva para la actividad '" . $consumidorInfo['actividad_titulo'] . "' ha sido confirmada.\n\n"
                . "Detalles de tu reserva:\n"
                . "- Fecha de la actividad: " . formatDateTime($consumidorInfo['fecha_actividad']) . "\n"
                . "- Número de participantes: " . $consumidorInfo['num_participantes'] . "\n"
                . "- Precio total: " . formatPrice($consumidorInfo['precio_total']) . "\n\n"
                . "Te esperamos en la fecha y hora acordadas. Si tienes alguna pregunta o necesitas modificar algo, no dudes en contactarnos.\n\n"
                . "¡Que disfrutes de la actividad!\n\n"
                . "Gracias por confiar en ActividadesConnect.";
            
            $mensajeStmt->execute([
                $user_id, // ofertante que confirma
                $consumidorInfo['usuario_id'],
                $asunto,
                $cuerpoMensaje
            ]);
        }
        
        showAlert('Reserva confirmada correctamente', 'success');
        
    } elseif ($accion === 'rechazar') {
        // Obtener información del consumidor para el mensaje ANTES de actualizar
        $consumidorQuery = "SELECT c.usuario_id, u.nombre, u.apellidos, a.titulo as actividad_titulo, r.fecha_actividad
                           FROM reservas r
                           JOIN consumidores c ON r.consumidor_id = c.id
                           JOIN usuarios u ON c.usuario_id = u.id
                           JOIN actividades a ON r.actividad_id = a.id
                           WHERE r.id = ? AND r.estado = 'pendiente'";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$reserva_id]);
        $consumidorInfo = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consumidorInfo) {
            showAlert('No se pudo obtener la información de la reserva o ya fue procesada', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Rechazar SOLO esta reserva específica - usar el ID de la reserva que ya validamos
        // Asegurar que solo se actualice la reserva con el ID exacto y estado pendiente
        $updateQuery = "UPDATE reservas SET estado = 'rechazada' WHERE id = ? AND estado = 'pendiente'";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$reserva_id]);
        
        // Verificar que solo se actualizó una fila
        $filasAfectadas = $updateStmt->rowCount();
        if ($filasAfectadas === 0) {
            showAlert('No se pudo rechazar la reserva. Puede que ya haya sido procesada.', 'warning');
            redirect('mis-reservas.php');
        } elseif ($filasAfectadas > 1) {
            // Esto no debería pasar nunca porque id es PRIMARY KEY, pero por seguridad
            showAlert('Error: Se detectó una actualización múltiple. Por favor, contacta con el administrador.', 'danger');
            redirect('mis-reservas.php');
        }
        
        // Devolver las plazas
        $updateDispQuery = "UPDATE disponibilidad_actividades 
                           SET plazas_disponibles = plazas_disponibles + ?,
                               estado = 'disponible'
                           WHERE id = ?";
        $updateDispStmt = $db->prepare($updateDispQuery);
        $updateDispStmt->execute([$reserva['num_participantes'], $reserva['disponibilidad_id']]);
        
        // Enviar mensaje al consumidor informando del rechazo
        if ($consumidorInfo) {
            $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) VALUES (?, ?, ?, ?)";
            $mensajeStmt = $db->prepare($mensajeQuery);
            
            $asunto = "Reserva rechazada: " . $consumidorInfo['actividad_titulo'];
            $cuerpoMensaje = "Hola " . $consumidorInfo['nombre'] . " " . $consumidorInfo['apellidos'] . ",\n\n"
                . "Lamentamos informarte que tu reserva para la actividad '" . $consumidorInfo['actividad_titulo'] . "' "
                . "programada para el " . formatDateTime($consumidorInfo['fecha_actividad']) . " ha sido rechazada por el ofertante.\n\n"
                . "Las plazas han sido liberadas y puedes buscar otras actividades disponibles en la plataforma.\n\n"
                . "Si tienes alguna pregunta, no dudes en contactarnos.\n\n"
                . "Gracias por usar ActividadesConnect.";
            
            $mensajeStmt->execute([
                $user_id, // ofertante que rechaza
                $consumidorInfo['usuario_id'],
                $asunto,
                $cuerpoMensaje
            ]);
        }
        
        showAlert('Reserva rechazada', 'info');
    }
    
    redirect('mis-reservas.php');
    
} catch (Exception $e) {
    showAlert('Error al procesar la reserva: ' . $e->getMessage(), 'danger');
    redirect('mis-reservas.php');
}
?>
