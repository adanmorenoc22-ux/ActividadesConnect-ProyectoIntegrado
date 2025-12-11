<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['solicitud_id'])) {
    redirect('buscar.php');
}

$solicitud_id = (int)$_GET['solicitud_id'];
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
    
    // Verificar que la solicitud existe y está activa
    $solicitudQuery = "SELECT s.*, u.nombre as consumidor_nombre, u.apellidos as consumidor_apellidos, u.id as consumidor_usuario_id
                       FROM solicitudes_consumidores s
                       JOIN consumidores c ON s.consumidor_id = c.id
                       JOIN usuarios u ON c.usuario_id = u.id
                       WHERE s.id = ? AND s.estado = 'activa'";
    $solicitudStmt = $db->prepare($solicitudQuery);
    $solicitudStmt->execute([$solicitud_id]);
    $solicitud = $solicitudStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada o no está activa', 'danger');
        redirect('buscar.php');
    }
    
    // Verificar si ya mostró interés
    $checkQuery = "SELECT id FROM intereses_ofertantes WHERE ofertante_id = ? AND solicitud_id = ? AND estado = 'activo'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$ofertante_id, $solicitud_id]);
    
    if ($checkStmt->rowCount() > 0) {
        showAlert('Ya mostraste interés en esta solicitud', 'warning');
        redirect('buscar.php');
    }
    
    // Mostrar interés
    $insertQuery = "INSERT INTO intereses_ofertantes (ofertante_id, solicitud_id) VALUES (?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([$ofertante_id, $solicitud_id]);

    // Enviar mensaje directo al consumidor informando del interés
    $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) 
                     VALUES (?, ?, ?, ?)";
    $mensajeStmt = $db->prepare($mensajeQuery);

    $asunto = "Nuevo interés en tu solicitud: " . $solicitud['titulo'];
    $cuerpoMensaje = "Hola " . $solicitud['consumidor_nombre'] . " " . $solicitud['consumidor_apellidos'] . ",\n\n"
        . "Un ofertante ha mostrado interés en tu solicitud '" . $solicitud['titulo'] . "'. "
        . "Puedes revisar los detalles de la solicitud y los intereses recibidos desde tu panel de usuario en la sección de solicitudes.\n\n"
        . "Gracias por usar ActividadesConnect.";

    $mensajeStmt->execute([
        $user_id, // ofertante (usuario actual)
        $solicitud['consumidor_usuario_id'],
        $asunto,
        $cuerpoMensaje
    ]);
    
    showAlert('¡Interés mostrado correctamente! El consumidor recibirá un mensaje directo.', 'success');
    redirect('buscar.php');
    
} catch (Exception $e) {
    showAlert('Error al mostrar interés: ' . $e->getMessage(), 'danger');
    redirect('buscar.php');
}
?>
