<?php
// Funciones auxiliares del sistema
require_once __DIR__ . '/../config/database.php';

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{9}$/', $phone);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isOfertante() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ofertante';
}

function isConsumidor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'consumidor';
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirect($url) {
    // Asegurar que no se haya enviado output antes
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: $url");
    exit();
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo "<div class='alert alert-{$alert['type']}'>{$alert['message']}</div>";
        unset($_SESSION['alert']);
    }
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatPrice($price) {
    return number_format($price, 2) . ' €';
}

function getActivityCategories() {
    return [
        'turismo_urbano' => 'Turismo Urbano',
        'deporte_aventura' => 'Deporte Aventura',
        'rutas_btt' => 'Rutas BTT',
        'juegos_entretenimiento' => 'Juegos y Entretenimiento',
        'gastronomia' => 'Gastronomía',
        'naturaleza' => 'Naturaleza',
        'cultural' => 'Cultural',
        'deportivo' => 'Deportivo'
    ];
}

function getDifficultyLevels() {
    return [
        'facil' => 'Fácil',
        'medio' => 'Medio',
        'dificil' => 'Difícil',
        'experto' => 'Experto'
    ];
}

function getActivityStatus() {
    return [
        'activa' => 'Activa',
        'pausada' => 'Pausada',
        'completa' => 'Completa',
        'cancelada' => 'Cancelada'
    ];
}

function getRequestStatus() {
    return [
        'pendiente' => 'Pendiente',
        'aceptada' => 'Aceptada',
        'rechazada' => 'Rechazada',
        'completada' => 'Completada'
    ];
}

function getMensajesNoLeidos() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $user_id = $_SESSION['user_id'];
        
        $query = "SELECT COUNT(*) as total FROM mensajes WHERE destinatario_id = ? AND leido = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Actualiza el estado de una solicitud basado en sus propuestas y reservas
 */
function actualizarEstadoSolicitud($solicitud_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si hay intereses activos
        $interesQuery = "SELECT COUNT(*) as total FROM intereses_ofertantes 
                        WHERE solicitud_id = ? AND estado = 'activo'";
        $interesStmt = $db->prepare($interesQuery);
        $interesStmt->execute([$solicitud_id]);
        $tieneIntereses = $interesStmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        
        if ($tieneIntereses) {
            // Verificar si hay reservas completadas relacionadas con esta solicitud
            $reservaQuery = "SELECT COUNT(*) as total FROM reservas r
                            JOIN actividades a ON r.actividad_id = a.id
                            JOIN intereses_ofertantes i ON a.ofertante_id = i.ofertante_id
                            WHERE i.solicitud_id = ? AND i.estado = 'activo' AND r.estado = 'completada'";
            $reservaStmt = $db->prepare($reservaQuery);
            $reservaStmt->execute([$solicitud_id]);
            $reservaCompletada = $reservaStmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
            
            if ($reservaCompletada) {
                // Marcar solicitud como completada
                $updateQuery = "UPDATE solicitudes_consumidores SET estado = 'completada' WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$solicitud_id]);
            } else {
                // Marcar como en proceso si hay intereses pero no completada
                $updateQuery = "UPDATE solicitudes_consumidores SET estado = 'en_proceso' WHERE id = ? AND estado = 'activa'";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$solicitud_id]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Marca una reserva como completada y actualiza el estado de la solicitud relacionada
 */
function completarReserva($reserva_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener información de la reserva
        $query = "SELECT r.*, a.titulo as actividad_titulo
                  FROM reservas r
                  JOIN actividades a ON r.actividad_id = a.id
                  WHERE r.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$reserva_id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reserva) {
            return false;
        }
        
        // Marcar reserva como completada
        $updateQuery = "UPDATE reservas SET estado = 'completada' WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$reserva_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
