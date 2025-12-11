<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener ID del ofertante
try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
    
    // Obtener actividades del ofertante
    $query = "SELECT a.*, 
              (SELECT COUNT(*) FROM reservas r WHERE r.actividad_id = a.id) as total_reservas,
              (SELECT COUNT(*) FROM reservas r WHERE r.actividad_id = a.id AND r.estado IN ('pendiente', 'confirmada')) as reservas_activas
              FROM actividades a
              WHERE a.ofertante_id = ?
              ORDER BY a.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$ofertante_id]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error al cargar las actividades';
}

$categorias = getActivityCategories();
$niveles_dificultad = getDifficultyLevels();
$estados = getActivityStatus();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - ActividadesConnect</title>
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
                    <h1><i class="fas fa-list"></i> Mis Actividades</h1>
                    <p style="color: #6c757d;">Gestiona todas tus actividades publicadas</p>
                </div>
                <a href="crear.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Crear Nueva Actividad
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php displayAlert(); ?>

            <!-- Estadísticas rápidas -->
            <div class="dashboard-stats" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($actividades); ?></div>
                    <div class="stat-label">Total Actividades</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($actividades, fn($a) => $a['estado'] === 'activa')); ?></div>
                    <div class="stat-label">Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($actividades, 'total_reservas')); ?></div>
                    <div class="stat-label">Total Reservas</div>
                </div>
            </div>

            <?php if (empty($actividades)): ?>
                <!-- Sin actividades -->
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-mountain" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>Aún no tienes actividades</h3>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        Crea tu primera actividad y comienza a recibir reservas
                    </p>
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Mi Primera Actividad
                    </a>
                </div>
            <?php else: ?>
                <!-- Lista de actividades -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Lista de Actividades</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Actividad</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Categoría</th>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #dee2e6;">Precio</th>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #dee2e6;">Reservas</th>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #dee2e6;">Estado</th>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #dee2e6;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($actividades as $actividad): ?>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 1rem;">
                                                <strong style="color: #2c3e50;"><?php echo htmlspecialchars($actividad['titulo']); ?></strong>
                                                <br>
                                                <small style="color: #6c757d;">
                                                    <i class="fas fa-clock"></i> <?php echo $actividad['duracion_horas']; ?>h
                                                    | <i class="fas fa-signal"></i> <?php echo $niveles_dificultad[$actividad['dificultad']] ?? $actividad['dificultad']; ?>
                                                </small>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <span style="background: #667eea; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem;">
                                                    <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <strong style="color: #2ecc71;"><?php echo formatPrice($actividad['precio_persona']); ?></strong>
                                                <br>
                                                <small style="color: #6c757d;">/persona</small>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <strong><?php echo $actividad['total_reservas']; ?></strong>
                                                <br>
                                                <small style="color: #6c757d;">reservas</small>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <?php
                                                $estadoColors = [
                                                    'activa' => '#2ecc71',
                                                    'pausada' => '#f39c12',
                                                    'completa' => '#3498db',
                                                    'cancelada' => '#e74c3c'
                                                ];
                                                $color = $estadoColors[$actividad['estado']] ?? '#6c757d';
                                                ?>
                                                <div>
                                                    <span style="background: <?php echo $color; ?>; color: white; padding: 5px 15px; border-radius: 15px; font-size: 0.85rem;">
                                                        <?php echo $estados[$actividad['estado']] ?? $actividad['estado']; ?>
                                                    </span>
                                                    <?php if ($actividad['reservas_activas'] > 0): ?>
                                                        <br>
                                                        <small style="color: #f39c12; font-size: 0.75rem;">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            <?php echo $actividad['reservas_activas']; ?> reserva(s) activa(s)
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                                    <a href="ver.php?id=<?php echo $actividad['id']; ?>" 
                                                       class="btn btn-info btn-sm"
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="disponibilidad.php?actividad_id=<?php echo $actividad['id']; ?>" 
                                                       class="btn btn-success btn-sm"
                                                       title="Gestionar fechas">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $actividad['id']; ?>" 
                                                       class="btn btn-warning btn-sm"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="eliminar.php?id=<?php echo $actividad['id']; ?>" 
                                                       class="btn btn-danger btn-sm btn-delete"
                                                       title="Eliminar"
                                                       onclick="return confirm('¿Estás seguro de que quieres eliminar esta actividad?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cards adicionales con información -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lightbulb"></i> Consejos</h3>
                        </div>
                        <div class="card-body">
                            <ul style="line-height: 2;">
                                <li>Mantén tus actividades actualizadas</li>
                                <li>Responde rápido a las reservas</li>
                                <li>Actualiza la disponibilidad regularmente</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Estadísticas</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Actividad más popular:</strong></p>
                            <?php
                            $masPopular = array_reduce($actividades, function($carry, $item) {
                                return ($item['total_reservas'] > ($carry['total_reservas'] ?? 0)) ? $item : $carry;
                            }, []);
                            ?>
                            <?php if (!empty($masPopular)): ?>
                                <p style="color: #667eea;">
                                    <?php echo htmlspecialchars($masPopular['titulo']); ?>
                                    (<?php echo $masPopular['total_reservas']; ?> reservas)
                                </p>
                            <?php else: ?>
                                <p style="color: #6c757d;">Aún no hay datos</p>
                            <?php endif; ?>
                        </div>
                    </div>
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
        
        @media (max-width: 768px) {
            table {
                font-size: 0.875rem;
            }
            th, td {
                padding: 0.5rem !important;
            }
        }
    </style>
</body>
</html>
