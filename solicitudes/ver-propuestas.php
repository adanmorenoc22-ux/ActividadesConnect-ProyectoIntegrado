<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['solicitud_id'])) {
    redirect('mis-solicitudes.php');
}

$solicitud_id = (int)$_GET['solicitud_id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener ID del consumidor y verificar que la solicitud le pertenece
try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Obtener solicitud
    $solicitudQuery = "SELECT * FROM solicitudes_consumidores WHERE id = ? AND consumidor_id = ?";
    $solicitudStmt = $db->prepare($solicitudQuery);
    $solicitudStmt->execute([$solicitud_id, $consumidor_id]);
    $solicitud = $solicitudStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada', 'danger');
        redirect('mis-solicitudes.php');
    }
    
    // Obtener propuestas
    $propuestasQuery = "SELECT p.*, o.verificado,
                        u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
                        FROM propuestas_ofertantes p
                        JOIN ofertantes o ON p.ofertante_id = o.id
                        JOIN usuarios u ON o.usuario_id = u.id
                        WHERE p.solicitud_id = ?
                        ORDER BY p.fecha_propuesta DESC";
    $propuestasStmt = $db->prepare($propuestasQuery);
    $propuestasStmt->execute([$solicitud_id]);
    $propuestas = $propuestasStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error al cargar propuestas';
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuestas Recibidas - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="mis-solicitudes.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a mis solicitudes
                </a>
            </div>

            <!-- Detalles de la solicitud -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($solicitud['titulo']); ?></h2>
                    <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 15px;">
                        <?php echo $categorias[$solicitud['categoria']] ?? $solicitud['categoria']; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <i class="fas fa-calendar"></i>
                            <strong>Fecha:</strong> <?php echo formatDate($solicitud['fecha_deseada']); ?>
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Ubicación:</strong> <?php echo htmlspecialchars($solicitud['ubicacion']); ?>
                        </div>
                        <div>
                            <i class="fas fa-users"></i>
                            <strong>Personas:</strong> <?php echo $solicitud['participantes_estimados']; ?>
                        </div>
                        <?php if ($solicitud['presupuesto_max']): ?>
                            <div>
                                <i class="fas fa-euro-sign"></i>
                                <strong>Presupuesto:</strong> <?php echo formatPrice($solicitud['presupuesto_max']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Propuestas -->
            <h2><i class="fas fa-envelope-open"></i> Propuestas Recibidas (<?php echo count($propuestas); ?>)</h2>

            <?php if (empty($propuestas)): ?>
                <div class="card" style="text-align: center; padding: 3rem; margin-top: 2rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>Aún no has recibido propuestas</h3>
                    <p style="color: #6c757d;">Los ofertantes verán tu solicitud y pronto comenzarán a enviarte propuestas</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 2rem; margin-top: 2rem;">
                    <?php foreach ($propuestas as $propuesta): ?>
                        <div class="card">
                            <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 style="margin: 0;">
                                        <?php echo htmlspecialchars($propuesta['ofertante_nombre'] . ' ' . $propuesta['ofertante_apellidos']); ?>
                                    </h3>
                                    <?php if ($propuesta['verificado']): ?>
                                        <span style="background: rgba(255,255,255,0.3); padding: 5px 15px; border-radius: 15px; font-size: 0.9rem;">
                                            <i class="fas fa-check-circle"></i> Verificado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">

                                <!-- Precio propuesto -->
                                <div style="background: #e8f4fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                                    <div style="color: #6c757d; margin-bottom: 0.5rem;">Precio Propuesto</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: #2ecc71;">
                                        <?php echo formatPrice($propuesta['precio_propuesto']); ?>
                                    </div>
                                </div>

                                <!-- Mensaje -->
                                <h4><i class="fas fa-comment"></i> Propuesta:</h4>
                                <p style="line-height: 1.8; color: #2c3e50; background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                                    <?php echo nl2br(htmlspecialchars($propuesta['mensaje'])); ?>
                                </p>

                                <small style="color: #6c757d;">
                                    Enviada el: <?php echo formatDateTime($propuesta['fecha_propuesta']); ?>
                                </small>

                                <!-- Estado -->
                                <div style="margin-top: 1rem;">
                                    <?php
                                    $estadoColors = [
                                        'pendiente' => '#f39c12',
                                        'aceptada' => '#2ecc71',
                                        'rechazada' => '#e74c3c'
                                    ];
                                    $color = $estadoColors[$propuesta['estado']] ?? '#6c757d';
                                    ?>
                                    <span style="background: <?php echo $color; ?>; color: white; padding: 8px 20px; border-radius: 20px; font-size: 0.9rem;">
                                        <?php echo ucfirst($propuesta['estado']); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($propuesta['estado'] === 'pendiente'): ?>
                                <div class="card-footer">
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="../propuestas/aceptar.php?id=<?php echo $propuesta['id']; ?>" 
                                           class="btn btn-success" 
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Aceptar esta propuesta? Se rechazarán las demás.');">
                                            <i class="fas fa-check"></i> Aceptar
                                        </a>
                                        <a href="../propuestas/rechazar.php?id=<?php echo $propuesta['id']; ?>" 
                                           class="btn btn-danger" 
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Rechazar esta propuesta?');">
                                            <i class="fas fa-times"></i> Rechazar
                                        </a>
                                        <a href="../ofertantes/ver.php?id=<?php echo $propuesta['ofertante_id']; ?>" 
                                           class="btn btn-info">
                                            <i class="fas fa-user"></i> Ver Perfil
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
