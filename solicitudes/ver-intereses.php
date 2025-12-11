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

try {
    // Obtener ID del consumidor
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Verificar que la solicitud le pertenece
    $solicitudQuery = "SELECT * FROM solicitudes_consumidores WHERE id = ? AND consumidor_id = ?";
    $solicitudStmt = $db->prepare($solicitudQuery);
    $solicitudStmt->execute([$solicitud_id, $consumidor_id]);
    $solicitud = $solicitudStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada', 'danger');
        redirect('mis-solicitudes.php');
    }
    
    // Obtener intereses
    $interesesQuery = "SELECT i.*, o.verificado,
                      u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos, u.id as ofertante_usuario_id
                      FROM intereses_ofertantes i
                      JOIN ofertantes o ON i.ofertante_id = o.id
                      JOIN usuarios u ON o.usuario_id = u.id
                      WHERE i.solicitud_id = ? AND i.estado = 'activo'
                      ORDER BY i.fecha_interes DESC";
    $interesesStmt = $db->prepare($interesesQuery);
    $interesesStmt->execute([$solicitud_id]);
    $intereses = $interesesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar todos los intereses de esta solicitud como vistos
    $marcarVistosQuery = "UPDATE intereses_ofertantes 
                         SET visto = TRUE, fecha_visto = NOW() 
                         WHERE solicitud_id = ? AND estado = 'activo' AND visto = FALSE";
    $marcarVistosStmt = $db->prepare($marcarVistosQuery);
    $marcarVistosStmt->execute([$solicitud_id]);
    
} catch (Exception $e) {
    $error = 'Error al cargar los intereses';
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intereses en mi Solicitud - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Navegación -->
            <div style="margin-bottom: 2rem;">
                <a href="mis-solicitudes.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a mis solicitudes
                </a>
            </div>

            <!-- Detalles de la solicitud -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                    <h2 style="margin: 0; color: white;"><?php echo htmlspecialchars($solicitud['titulo']); ?></h2>
                </div>
                <div class="card-body">
                    <p style="color: #6c757d; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 200)) . '...'; ?>
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Fecha deseada:</strong> <?php echo formatDate($solicitud['fecha_deseada']); ?>
                        </div>
                        <div>
                            <strong>Ubicación:</strong> <?php echo htmlspecialchars($solicitud['ubicacion']); ?>
                        </div>
                        <div>
                            <strong>Participantes:</strong> <?php echo $solicitud['participantes_estimados']; ?>
                        </div>
                        <?php if ($solicitud['presupuesto_max']): ?>
                            <div>
                                <strong>Presupuesto:</strong> <?php echo formatPrice($solicitud['presupuesto_max']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Intereses recibidos -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-heart"></i> Ofertantes Interesados (<?php echo count($intereses); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($intereses)): ?>
                        <div style="text-align: center; padding: 3rem; color: #6c757d;">
                            <i class="fas fa-heart-broken" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <h4 style="margin: 0 0 0.5rem 0;">Aún no hay ofertantes interesados</h4>
                            <p style="margin: 0;">Los ofertantes verán tu solicitud y podrán mostrar interés cuando les guste</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1.5rem;">
                            <?php foreach ($intereses as $interes): ?>
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; background: #f8f9fa;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <h4 style="margin: 0;">
                                            <?php echo htmlspecialchars($interes['ofertante_nombre'] . ' ' . $interes['ofertante_apellidos']); ?>
                                            <?php if ($interes['verificado']): ?>
                                                <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;">
                                                    <i class="fas fa-check-circle"></i> Verificado
                                                </span>
                                            <?php endif; ?>
                                        </h4>
                                        <span style="color: #6c757d; font-size: 0.9rem;">
                                            <i class="fas fa-clock"></i> <?php echo formatDateTime($interes['fecha_interes']); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <p style="margin: 0; color: #495057;">
                                                Este ofertante se ha interesado en tu solicitud. Revisa su perfil para estar atento a cuando cree esta actividad.
                                            </p>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="../ofertantes/ver.php?id=<?php echo $interes['ofertante_id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> Ver Perfil
                                            </a>
                                            <a href="../mensajes/nuevo.php?destinatario_id=<?php echo $interes['ofertante_usuario_id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-envelope"></i> Contactar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
