<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

// Verificar parámetro
if (!isset($_GET['solicitud_id'])) {
    redirect('../solicitudes/buscar.php');
}

$solicitud_id = (int)$_GET['solicitud_id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$error = '';

// Obtener ID del ofertante
try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
    
    // Obtener datos de la solicitud
    $solicitudQuery = "SELECT s.*, u.nombre as consumidor_nombre, u.apellidos as consumidor_apellidos
                       FROM solicitudes_consumidores s
                       JOIN consumidores c ON s.consumidor_id = c.id
                       JOIN usuarios u ON c.usuario_id = u.id
                       WHERE s.id = ? AND s.estado = 'activa'";
    $solicitudStmt = $db->prepare($solicitudQuery);
    $solicitudStmt->execute([$solicitud_id]);
    $solicitud = $solicitudStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada o no está activa', 'danger');
        redirect('../solicitudes/buscar.php');
    }
    
    // Verificar si ya envió propuesta
    $checkQuery = "SELECT id FROM propuestas_ofertantes WHERE ofertante_id = ? AND solicitud_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$ofertante_id, $solicitud_id]);
    
    if ($checkStmt->rowCount() > 0) {
        showAlert('Ya enviaste una propuesta para esta solicitud', 'warning');
        redirect('../solicitudes/buscar.php');
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar la solicitud';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensaje = sanitizeInput($_POST['mensaje']);
    $precio_propuesto = $_POST['precio_propuesto'];
    
    if (empty($mensaje) || empty($precio_propuesto)) {
        $error = 'Completa todos los campos';
    } elseif ($precio_propuesto <= 0) {
        $error = 'El precio debe ser mayor que 0';
    } else {
        try {
            $insertQuery = "INSERT INTO propuestas_ofertantes (ofertante_id, solicitud_id, mensaje, precio_propuesto, estado)
                           VALUES (?, ?, ?, ?, 'pendiente')";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$ofertante_id, $solicitud_id, $mensaje, $precio_propuesto]);
            
            showAlert('Propuesta enviada correctamente', 'success');
            redirect('../solicitudes/buscar.php');
            
        } catch (Exception $e) {
            $error = 'Error al enviar propuesta: ' . $e->getMessage();
        }
    }
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Propuesta - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="../solicitudes/buscar.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a solicitudes
                </a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Formulario de propuesta -->
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-paper-plane"></i> Enviar Propuesta</h1>
                        <p>Haz tu mejor oferta al cliente</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label for="mensaje">
                                    <i class="fas fa-comment"></i> Tu Propuesta *
                                </label>
                                <textarea class="form-control" 
                                          id="mensaje" 
                                          name="mensaje" 
                                          rows="8"
                                          placeholder="Describe cómo puedes cumplir con esta solicitud, tu experiencia relevante, qué incluirías..."
                                          required><?php echo isset($_POST['mensaje']) ? htmlspecialchars($_POST['mensaje']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="precio_propuesto">
                                    <i class="fas fa-euro-sign"></i> Precio Propuesto (€) *
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="precio_propuesto" 
                                       name="precio_propuesto" 
                                       value="<?php echo isset($_POST['precio_propuesto']) ? $_POST['precio_propuesto'] : ''; ?>"
                                       min="0" 
                                       step="0.01"
                                       placeholder="150.00"
                                       required>
                                <?php if ($solicitud['presupuesto_max']): ?>
                                    <small style="color: #6c757d;">
                                        Presupuesto máximo del cliente: <?php echo formatPrice($solicitud['presupuesto_max']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb"></i>
                                <strong>Consejos:</strong>
                                <ul style="margin: 0.5rem 0 0 1.5rem;">
                                    <li>Sé específico sobre lo que ofreces</li>
                                    <li>Destaca tu experiencia relevante</li>
                                    <li>Sé competitivo con el precio</li>
                                    <li>Responde a todos los requisitos</li>
                                </ul>
                            </div>

                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-paper-plane"></i> Enviar Propuesta
                                </button>
                                <a href="../solicitudes/buscar.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Detalles de la solicitud -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3>Detalles de la Solicitud</h3>
                        </div>
                        <div class="card-body">
                            <h2 style="color: #2c3e50; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($solicitud['titulo']); ?>
                            </h2>

                            <div style="margin-bottom: 1rem;">
                                <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 15px; font-size: 0.9rem;">
                                    <?php echo $categorias[$solicitud['categoria']] ?? $solicitud['categoria']; ?>
                                </span>
                            </div>

                            <h4>Descripción:</h4>
                            <p style="line-height: 1.8; color: #2c3e50; margin-bottom: 1.5rem;">
                                <?php echo nl2br(htmlspecialchars($solicitud['descripcion'])); ?>
                            </p>

                            <h4>Detalles:</h4>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar" style="color: #667eea; width: 20px;"></i>
                                    <strong>Fecha:</strong> <?php echo formatDate($solicitud['fecha_deseada']); ?>
                                    <?php if ($solicitud['hora_deseada']): ?>
                                        a las <?php echo $solicitud['hora_deseada']; ?>
                                    <?php endif; ?>
                                </li>
                                <li style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-map-marker-alt" style="color: #667eea; width: 20px;"></i>
                                    <strong>Ubicación:</strong> <?php echo htmlspecialchars($solicitud['ubicacion']); ?>
                                </li>
                                <li style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-users" style="color: #667eea; width: 20px;"></i>
                                    <strong>Participantes:</strong> <?php echo $solicitud['participantes_estimados']; ?>
                                </li>
                                <?php if ($solicitud['duracion_estimada']): ?>
                                    <li style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-clock" style="color: #667eea; width: 20px;"></i>
                                        <strong>Duración:</strong> <?php echo $solicitud['duracion_estimada']; ?>h
                                    </li>
                                <?php endif; ?>
                                <?php if ($solicitud['presupuesto_max']): ?>
                                    <li style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-euro-sign" style="color: #667eea; width: 20px;"></i>
                                        <strong>Presupuesto:</strong> <?php echo formatPrice($solicitud['presupuesto_max']); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($solicitud['requisitos_especiales']): ?>
                                <h4 style="margin-top: 1.5rem;">Requisitos Especiales:</h4>
                                <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0;">
                                        <?php echo nl2br(htmlspecialchars($solicitud['requisitos_especiales'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <hr style="margin: 1.5rem 0;">

                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                                <h4>Solicitante:</h4>
                                <p><strong><?php echo htmlspecialchars($solicitud['consumidor_nombre'] . ' ' . $solicitud['consumidor_apellidos']); ?></strong></p>
                                <small style="color: #6c757d;">
                                    Publicado el: <?php echo formatDate($solicitud['fecha_creacion']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
