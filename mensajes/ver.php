<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('bandeja.php');
}

$mensaje_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Obtener mensaje
    $query = "SELECT m.*, 
              ur.nombre as remitente_nombre, ur.apellidos as remitente_apellidos,
              ud.nombre as destinatario_nombre, ud.apellidos as destinatario_apellidos
              FROM mensajes m
              JOIN usuarios ur ON m.remitente_id = ur.id
              JOIN usuarios ud ON m.destinatario_id = ud.id
              WHERE m.id = ? AND (m.remitente_id = ? OR m.destinatario_id = ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$mensaje_id, $user_id, $user_id]);
    $mensaje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mensaje) {
        showAlert('Mensaje no encontrado', 'danger');
        redirect('bandeja.php');
    }
    
    // Marcar como leído si es el destinatario
    if ($mensaje['destinatario_id'] == $user_id && !$mensaje['leido']) {
        $updateQuery = "UPDATE mensajes SET leido = 1 WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$mensaje_id]);
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar el mensaje';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mensaje['asunto']); ?> - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="bandeja.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a mensajes
                </a>
            </div>

            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><?php echo htmlspecialchars($mensaje['asunto']); ?></h1>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                            <div>
                                <strong>De:</strong> <?php echo htmlspecialchars($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellidos']); ?>
                            </div>
                            <div style="color: #6c757d; font-size: 0.9rem;">
                                <?php echo formatDateTime($mensaje['fecha_envio']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px; line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="nuevo.php?destinatario_id=<?php echo $mensaje['remitente_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-reply"></i> Responder
                        </a>
                        <a href="bandeja.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
