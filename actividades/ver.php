<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('../actividades.php');
}

$actividad_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Obtener datos de la actividad
try {
    $query = "SELECT a.id, a.titulo, a.descripcion, a.precio_persona, a.precio_grupo, a.duracion_horas, a.categoria, a.estado, a.fecha_creacion,
                     a.lugar_inicio, a.dificultad, a.requisitos_edad_min, a.requisitos_edad_max, a.incluye_transporte, a.incluye_comida, a.incluye_seguro,
                     a.material_requerido, a.material_incluido, a.preparacion_fisica, a.restricciones,
                     o.id as ofertante_id, o.descripcion as ofertante_descripcion, o.verificado,
                     u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos, u.telefono as ofertante_telefono, u.id as usuario_id
              FROM actividades a
              JOIN ofertantes o ON a.ofertante_id = o.id
              JOIN usuarios u ON o.usuario_id = u.id
              WHERE a.id = ? AND a.estado = 'activa'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$actividad) {
        redirect('../actividades.php');
    }
    
    // Obtener disponibilidad
    $dispQuery = "SELECT * FROM disponibilidad_actividades 
                  WHERE actividad_id = ? AND estado = 'disponible' AND fecha_inicio >= NOW()
                  ORDER BY fecha_inicio ASC";
    $dispStmt = $db->prepare($dispQuery);
    $dispStmt->execute([$actividad_id]);
    $disponibilidades = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
    
    
} catch (Exception $e) {
    $error = 'Error al cargar la actividad';
}

$categorias = getActivityCategories();
$niveles_dificultad = getDifficultyLevels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($actividad['titulo']); ?> - ActividadesConnect</title>
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

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Columna principal -->
                <div>
                    <!-- Imagen y título -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 300px; display: flex; align-items: center; justify-content: center; color: white; font-size: 5rem; border-radius: 15px 15px 0 0;">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 1rem;">
                                <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem;">
                                    <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                                </span>
                            </div>
                            <h1 style="color: #2c3e50; margin-bottom: 1rem;"><?php echo htmlspecialchars($actividad['titulo']); ?></h1>
                            
                            <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 1.5rem;">
                                <div>
                                    <i class="fas fa-map-marker-alt" style="color: #667eea;"></i>
                                    <strong>Inicio:</strong> <?php echo htmlspecialchars($actividad['lugar_inicio']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-clock" style="color: #667eea;"></i>
                                    <strong>Duración:</strong> <?php echo $actividad['duracion_horas']; ?> horas
                                </div>
                                <div>
                                    <i class="fas fa-signal" style="color: #667eea;"></i>
                                    <strong>Dificultad:</strong> <?php echo $niveles_dificultad[$actividad['dificultad']] ?? $actividad['dificultad']; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Descripción</h3>
                        </div>
                        <div class="card-body">
                            <p style="line-height: 1.8; color: #2c3e50;">
                                <?php echo nl2br(htmlspecialchars($actividad['descripcion'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Detalles de la actividad -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Detalles de la Actividad</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div>
                                    <strong><i class="fas fa-users"></i> Plazas:</strong>
                                    <p>Se definen en cada fecha disponible (consulta las próximas fechas más abajo)</p>
                                </div>

                                <?php if ($actividad['requisitos_edad_min'] || $actividad['requisitos_edad_max']): ?>
                                <div>
                                    <strong><i class="fas fa-birthday-cake"></i> Edad requerida:</strong>
                                    <p>
                                        <?php 
                                        if ($actividad['requisitos_edad_min'] && $actividad['requisitos_edad_max']) {
                                            echo $actividad['requisitos_edad_min'] . ' - ' . $actividad['requisitos_edad_max'] . ' años';
                                        } elseif ($actividad['requisitos_edad_min']) {
                                            echo 'Mínimo ' . $actividad['requisitos_edad_min'] . ' años';
                                        } else {
                                            echo 'Máximo ' . $actividad['requisitos_edad_max'] . ' años';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <strong><i class="fas fa-<?php echo $actividad['incluye_transporte'] ? 'check-circle' : 'times-circle'; ?>"></i> Transporte:</strong>
                                    <p><?php echo $actividad['incluye_transporte'] ? 'Incluido' : 'No incluido'; ?></p>
                                </div>

                                <div>
                                    <strong><i class="fas fa-<?php echo $actividad['incluye_comida'] ? 'check-circle' : 'times-circle'; ?>"></i> Comida:</strong>
                                    <p><?php echo $actividad['incluye_comida'] ? 'Incluida' : 'No incluida'; ?></p>
                                </div>

                                <div>
                                    <strong><i class="fas fa-<?php echo $actividad['incluye_seguro'] ? 'check-circle' : 'times-circle'; ?>"></i> Seguro:</strong>
                                    <p><?php echo $actividad['incluye_seguro'] ? 'Incluido' : 'No incluido'; ?></p>
                                </div>
                            </div>

                            <?php if ($actividad['material_requerido']): ?>
                                <div style="margin-top: 1.5rem;">
                                    <strong><i class="fas fa-backpack"></i> Material Requerido:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($actividad['material_requerido'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($actividad['material_incluido']): ?>
                                <div style="margin-top: 1.5rem;">
                                    <strong><i class="fas fa-box"></i> Material Incluido:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($actividad['material_incluido'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($actividad['preparacion_fisica']): ?>
                                <div style="margin-top: 1.5rem;">
                                    <strong><i class="fas fa-heartbeat"></i> Preparación Física:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($actividad['preparacion_fisica'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($actividad['restricciones']): ?>
                                <div style="margin-top: 1.5rem; background: #fff3cd; padding: 1rem; border-radius: 5px; border-left: 4px solid #ffc107;">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Restricciones:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($actividad['restricciones'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Columna lateral -->
                <div>
                    <!-- Precio y reserva -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-body">
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #2ecc71;">
                                    <?php echo formatPrice($actividad['precio_persona']); ?>
                                </div>
                                <div style="color: #6c757d;">por persona</div>
                                <?php if ($actividad['precio_grupo']): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <small style="color: #6c757d;">
                                            o <?php echo formatPrice($actividad['precio_grupo']); ?> por grupo
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (isLoggedIn() && isConsumidor()): ?>
                                <a href="../reservas/crear.php?actividad_id=<?php echo $actividad['id']; ?>" 
                                   class="btn btn-primary" 
                                   style="width: 100%; margin-bottom: 1rem; text-align: center;">
                                    <i class="fas fa-calendar-check"></i> Reservar Ahora
                                </a>
                            <?php elseif (isLoggedIn()): ?>
                                <div class="alert alert-info">
                                    Solo los consumidores pueden hacer reservas
                                </div>
                            <?php else: ?>
                                <a href="../login.php" class="btn btn-primary" style="width: 100%; text-align: center;">
                                    <i class="fas fa-sign-in-alt"></i> Inicia sesión para reservar
                                </a>
                            <?php endif; ?>

                            <a href="#disponibilidad" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                <i class="fas fa-calendar"></i> Ver Disponibilidad
                            </a>
                        </div>
                    </div>

                    <!-- Información del ofertante -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Ofertante</h3>
                        </div>
                        <div class="card-body">
                            <h4><?php echo htmlspecialchars($actividad['ofertante_nombre'] . ' ' . $actividad['ofertante_apellidos']); ?></h4>
                            
                            <?php if ($actividad['verificado']): ?>
                                <div style="color: #2ecc71; margin: 0.5rem 0;">
                                    <i class="fas fa-check-circle"></i> Verificado
                                </div>
                            <?php endif; ?>


                            <?php if ($actividad['descripcion']): ?>
                                <p style="margin-top: 1rem; color: #6c757d; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars(substr($actividad['descripcion'], 0, 150)) . '...'; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (isLoggedIn()): ?>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                    <a href="../mensajes/nuevo.php?destinatario_id=<?php echo $actividad['usuario_id']; ?>" 
                                       class="btn btn-info" 
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-envelope"></i> Enviar Mensaje
                                    </a>
                                    <a href="../ofertantes/ver.php?id=<?php echo $actividad['ofertante_id']; ?>" 
                                       class="btn btn-primary" 
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-user"></i> Ver Perfil
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Disponibilidad -->
                    <div class="card" id="disponibilidad">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Próximas Fechas</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($disponibilidades)): ?>
                                <?php foreach (array_slice($disponibilidades, 0, 5) as $disp): ?>
                                    <div style="padding: 1rem; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 1rem;">
                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem;">
                                            <?php echo formatDate($disp['fecha_inicio']); ?>
                                        </div>
                                        <div style="color: #6c757d; font-size: 0.9rem;">
                                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($disp['fecha_inicio'])); ?>
                                        </div>
                                        <div style="color: #2ecc71; font-size: 0.9rem; margin-top: 0.5rem;">
                                            <i class="fas fa-users"></i> <?php echo $disp['plazas_disponibles']; ?> plazas disponibles
                                        </div>
                                        <?php if ($disp['precio_especial']): ?>
                                            <div style="color: #e74c3c; font-weight: 600; margin-top: 0.5rem;">
                                                Precio especial: <?php echo formatPrice($disp['precio_especial']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: #6c757d;">
                                    No hay fechas disponibles en este momento
                                </p>
                            <?php endif; ?>
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
