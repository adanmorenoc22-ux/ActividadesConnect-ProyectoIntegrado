<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('../actividades.php');
}

$ofertante_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Obtener datos del ofertante
try {
    $query = "SELECT o.*, u.nombre, u.apellidos, u.telefono, u.fecha_registro
              FROM ofertantes o
              JOIN usuarios u ON o.usuario_id = u.id
              WHERE o.id = ? AND u.activo = 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$ofertante_id]);
    $ofertante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ofertante) {
        redirect('../actividades.php');
    }
    
    // Obtener actividades del ofertante
    $actividadesQuery = "SELECT * FROM actividades 
                        WHERE ofertante_id = ? AND estado = 'activa'
                        ORDER BY fecha_creacion DESC
                        LIMIT 6";
    $actividadesStmt = $db->prepare($actividadesQuery);
    $actividadesStmt->execute([$ofertante_id]);
    $actividades = $actividadesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    
} catch (Exception $e) {
    $error = 'Error al cargar el ofertante';
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ofertante['nombre'] . ' ' . $ofertante['apellidos']); ?> - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="../actividades.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a actividades
                </a>
            </div>

            <!-- Perfil del ofertante -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 3rem 2rem; color: white;">
                    <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                        <div style="width: 120px; height: 120px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #667eea; font-size: 4rem;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div style="flex: 1;">
                            <h1 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($ofertante['nombre'] . ' ' . $ofertante['apellidos']); ?></h1>
                            <?php if ($ofertante['verificado']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <span style="background: rgba(255,255,255,0.3); padding: 8px 20px; border-radius: 20px;">
                                        <i class="fas fa-check-circle"></i> Ofertante Verificado
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo count($actividades); ?></div>
                                    <div style="font-size: 0.9rem; opacity: 0.9;">Actividades Activas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Descripción -->
                    <?php if ($ofertante['descripcion']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3><i class="fas fa-info-circle"></i> Sobre mí</h3>
                            <p style="line-height: 1.8; color: #2c3e50;">
                                <?php echo nl2br(htmlspecialchars($ofertante['descripcion'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Experiencia -->
                    <?php if ($ofertante['experiencia']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3><i class="fas fa-award"></i> Experiencia</h3>
                            <p style="line-height: 1.8; color: #2c3e50;">
                                <?php echo nl2br(htmlspecialchars($ofertante['experiencia'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Certificaciones -->
                    <?php if ($ofertante['certificaciones']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3><i class="fas fa-certificate"></i> Certificaciones y Títulos</h3>
                            <p style="line-height: 1.8; color: #2c3e50;">
                                <?php echo nl2br(htmlspecialchars($ofertante['certificaciones'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Información adicional -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                        <?php if ($ofertante['disponibilidad_general']): ?>
                            <div>
                                <h4><i class="fas fa-clock"></i> Disponibilidad</h4>
                                <p style="color: #6c757d;"><?php echo nl2br(htmlspecialchars($ofertante['disponibilidad_general'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($ofertante['precio_hora']): ?>
                            <div>
                                <h4><i class="fas fa-euro-sign"></i> Precio</h4>
                                <p style="color: #2ecc71; font-size: 1.2rem; font-weight: 600;">
                                    Desde <?php echo formatPrice($ofertante['precio_hora']); ?>/hora
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actividades del ofertante -->
            <?php if (!empty($actividades)): ?>
                <div style="margin-bottom: 2rem;">
                    <h2><i class="fas fa-list"></i> Actividades de <?php echo htmlspecialchars($ofertante['nombre']); ?></h2>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        <?php echo count($actividades); ?> actividades activas
                    </p>

                    <div class="activities-grid">
                        <?php foreach ($actividades as $actividad): ?>
                            <div class="activity-card">
                                <div class="activity-image">
                                    <i class="fas fa-mountain"></i>
                                </div>
                                <div class="activity-content">
                                    <h3 class="activity-title"><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
                                    <div class="activity-category">
                                        <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                                    </div>
                                    <p class="activity-description">
                                        <?php echo htmlspecialchars(substr($actividad['descripcion'], 0, 100)) . '...'; ?>
                                    </p>
                                    <div class="activity-meta">
                                        <div>
                                            <strong><?php echo formatPrice($actividad['precio_persona']); ?></strong>
                                            <small>/persona</small>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i> <?php echo $actividad['duracion_horas']; ?>h
                                        </div>
                                    </div>
                                </div>
                                <div class="activity-footer">
                                    <a href="../actividades/ver.php?id=<?php echo $actividad['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="../actividades.php?ofertante_id=<?php echo $ofertante_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Ver Todas las Actividades
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
