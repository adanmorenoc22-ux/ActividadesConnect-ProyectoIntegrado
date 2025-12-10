<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    redirect('../dashboard.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener ID del consumidor
try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Obtener solicitudes del consumidor
    $query = "SELECT s.*,
              (SELECT COUNT(*) FROM intereses_ofertantes WHERE solicitud_id = s.id AND estado = 'activo') as total_intereses,
              (SELECT COUNT(*) FROM intereses_ofertantes WHERE solicitud_id = s.id AND estado = 'activo' AND visto = FALSE) as intereses_nuevos
              FROM solicitudes_consumidores s
              WHERE s.consumidor_id = ?
              ORDER BY s.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$consumidor_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error al cargar las solicitudes';
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="../dashboard.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver al panel
                </a>
            </div>

            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1><i class="fas fa-bullhorn"></i> Mis Solicitudes</h1>
                    <p style="color: #6c757d;">Gestiona tus solicitudes y propuestas recibidas</p>
                </div>
                <a href="crear.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nueva Solicitud
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php displayAlert(); ?>

            <!-- Estadísticas -->
            <div class="dashboard-stats" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($solicitudes); ?></div>
                    <div class="stat-label">Total Solicitudes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($solicitudes, fn($s) => $s['estado'] === 'activa')); ?></div>
                    <div class="stat-label">Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($solicitudes, 'total_propuestas')); ?></div>
                    <div class="stat-label">Propuestas Recibidas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($solicitudes, 'propuestas_pendientes')); ?></div>
                    <div class="stat-label">Sin Revisar</div>
                </div>
            </div>

            <?php if (empty($solicitudes)): ?>
                <!-- Sin solicitudes -->
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-bullhorn" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No tienes solicitudes</h3>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        Crea una solicitud y los ofertantes te enviarán propuestas personalizadas
                    </p>
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Mi Primera Solicitud
                    </a>
                </div>
            <?php else: ?>
                <!-- Lista de solicitudes -->
                <div class="activities-grid">
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <div class="card">
                            <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($solicitud['titulo']); ?></h3>
                            </div>
                            <div class="card-body">
                                <div style="margin-bottom: 1rem;">
                                    <span style="background: rgba(102, 126, 234, 0.1); color: #667eea; padding: 5px 15px; border-radius: 15px; font-size: 0.85rem;">
                                        <?php echo $categorias[$solicitud['categoria']] ?? $solicitud['categoria']; ?>
                                    </span>
                                </div>

                                <p style="color: #6c757d; margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 150)) . '...'; ?>
                                </p>

                                <div style="margin-bottom: 1rem;">
                                    <p><i class="fas fa-calendar"></i> <strong>Fecha:</strong> <?php echo formatDate($solicitud['fecha_deseada']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <strong>Ubicación:</strong> <?php echo htmlspecialchars($solicitud['ubicacion']); ?></p>
                                    <p><i class="fas fa-users"></i> <strong>Participantes:</strong> <?php echo $solicitud['participantes_estimados']; ?></p>
                                    <?php if ($solicitud['presupuesto_max']): ?>
                                        <p><i class="fas fa-euro-sign"></i> <strong>Presupuesto máx:</strong> <?php echo formatPrice($solicitud['presupuesto_max']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Estado y propuestas -->
                                <div style="margin-bottom: 1rem;">
                                    <?php
                                    $estadoColors = [
                                        'activa' => '#2ecc71',
                                        'en_proceso' => '#f39c12',
                                        'completada' => '#3498db',
                                        'cancelada' => '#e74c3c'
                                    ];
                                    $color = $estadoColors[$solicitud['estado']] ?? '#6c757d';
                                    ?>
                                    <span style="background: <?php echo $color; ?>; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;">
                                        Estado: <?php echo ucfirst($solicitud['estado']); ?>
                                    </span>
                                </div>

                                <?php if ($solicitud['intereses_nuevos'] > 0): ?>
                                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                                        <i class="fas fa-heart"></i>
                                        <strong><?php echo $solicitud['intereses_nuevos']; ?> interés(es) nuevo(s)</strong>
                                        <?php if ($solicitud['total_intereses'] > $solicitud['intereses_nuevos']): ?>
                                            <br><small style="color: #6c757d;">Total: <?php echo $solicitud['total_intereses']; ?> interesados</small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($solicitud['total_intereses'] > 0): ?>
                                    <div class="alert alert-info" style="margin-bottom: 1rem;">
                                        <i class="fas fa-heart"></i>
                                        <strong><?php echo $solicitud['total_intereses']; ?> ofertante(s) interesado(s)</strong>
                                        <br><small style="color: #6c757d;">Ya vistos</small>
                                    </div>
                                <?php endif; ?>

                                <small style="color: #6c757d;">
                                    Creada el: <?php echo formatDate($solicitud['fecha_creacion']); ?>
                                </small>
                            </div>

                            <div class="card-footer">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="ver.php?id=<?php echo $solicitud['id']; ?>" 
                                       class="btn btn-info btn-sm" 
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                    <a href="ver-intereses.php?solicitud_id=<?php echo $solicitud['id']; ?>" 
                                       class="btn btn-success btn-sm" 
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-heart"></i> 
                                        Ver Intereses (<?php echo $solicitud['total_intereses']; ?>)
                                        <?php if ($solicitud['intereses_nuevos'] > 0): ?>
                                            <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; margin-left: 5px;">
                                                <?php echo $solicitud['intereses_nuevos']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($solicitud['estado'] === 'activa'): ?>
                                        <a href="editar.php?id=<?php echo $solicitud['id']; ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="cancelar-solicitud.php?id=<?php echo $solicitud['id']; ?>" 
                                           class="btn btn-warning btn-sm"
                                           onclick="return confirm('¿Cancelar esta solicitud?');">
                                            <i class="fas fa-ban"></i> Cancelar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <style>
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
