<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

// Verificar que se recibió el ID de actividad
if (!isset($_GET['actividad_id']) || empty($_GET['actividad_id'])) {
    redirect('mis-actividades.php');
}

$actividad_id = (int)$_GET['actividad_id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Obtener ID del ofertante y verificar permisos
try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
    
    // Verificar que la actividad pertenece al ofertante
    $actividadQuery = "SELECT * FROM actividades WHERE id = ? AND ofertante_id = ?";
    $actividadStmt = $db->prepare($actividadQuery);
    $actividadStmt->execute([$actividad_id, $ofertante_id]);
$actividad = $actividadStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        showAlert('Actividad no encontrada o no tienes permiso', 'danger');
        redirect('mis-actividades.php');
    }
    
    // Obtener disponibilidades existentes
    $dispQuery = "SELECT * FROM disponibilidad_actividades 
                  WHERE actividad_id = ?
                  ORDER BY fecha_inicio ASC";
    $dispStmt = $db->prepare($dispQuery);
    $dispStmt->execute([$actividad_id]);
    $disponibilidades = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error al cargar datos';
}

// Procesar formulario de nueva disponibilidad
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $fecha_inicio = $_POST['fecha_inicio'];
    $hora_inicio = $_POST['hora_inicio'];
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $plazas_disponibles = (int)$_POST['plazas_disponibles'];
    $precio_especial = !empty($_POST['precio_especial']) && (float)$_POST['precio_especial'] > 0 ? (float)$_POST['precio_especial'] : null;
    $notas = sanitizeInput($_POST['notas']);
    
    // Validaciones
    if (empty($fecha_inicio) || empty($hora_inicio) || empty($plazas_disponibles)) {
        $error = 'Completa los campos obligatorios';
    } elseif ($plazas_disponibles < 1) {
        $error = 'Las plazas deben ser al menos 1';
    } else {
        try {
            // Combinar fecha y hora
            $fecha_inicio_completa = $fecha_inicio . ' ' . $hora_inicio;
            
            if ($fecha_fin && $hora_fin) {
                $fecha_fin_completa = $fecha_fin . ' ' . $hora_fin;
            } else {
                // Calcular automáticamente sumando la duración de la actividad en minutos
                // para evitar problemas con formatos decimales y cambios de horario.
                $duracionHoras = (float)$actividad['duracion_horas'];
                $minutos = (int)round($duracionHoras * 60);

                $inicioDateTime = new DateTime($fecha_inicio_completa);
                $inicioDateTime->modify('+' . $minutos . ' minutes');
                $fecha_fin_completa = $inicioDateTime->format('Y-m-d H:i:s');
            }
            
            // Verificar que la fecha sea futura
            if (strtotime($fecha_inicio_completa) < time()) {
                $error = 'La fecha de inicio debe ser futura';
            } else {
                // Insertar disponibilidad
                $insertQuery = "INSERT INTO disponibilidad_actividades 
                               (actividad_id, fecha_inicio, fecha_fin, plazas_disponibles, precio_especial, notas, estado)
                               VALUES (?, ?, ?, ?, ?, ?, 'disponible')";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    $actividad_id, $fecha_inicio_completa, $fecha_fin_completa, 
                    $plazas_disponibles, $precio_especial, $notas
                ]);
                
                $success = 'Fecha agregada correctamente';
                
                // Recargar disponibilidades
                $dispStmt->execute([$actividad_id]);
                $disponibilidades = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Limpiar formulario
                $_POST = array();
            }
            
        } catch (Exception $e) {
            $error = 'Error al agregar fecha: ' . $e->getMessage();
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
    <title>Gestionar Disponibilidad - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="mis-actividades.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a mis actividades
                </a>
            </div>

            <!-- Header -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h1><i class="fas fa-calendar-alt"></i> Gestionar Disponibilidad</h1>
                    <p><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <div>
                            <i class="fas fa-tag"></i>
                            <strong>Categoría:</strong> <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i>
                            <strong>Duración:</strong> <?php echo $actividad['duracion_horas']; ?>h
                        </div>
                        <div>
                            <i class="fas fa-euro-sign"></i>
                            <strong>Precio:</strong> <?php echo formatPrice($actividad['precio_persona']); ?>/persona
                        </div>
                        <div>
                            <i class="fas fa-users"></i>
                            <strong>Plazas:</strong> Se definen en cada fecha disponible
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php displayAlert(); ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Formulario para agregar nueva fecha -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Agregar Nueva Fecha</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="agregar">

                            <div class="form-group">
                                <label for="fecha_inicio">
                                    <i class="fas fa-calendar"></i> Fecha de Inicio *
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="hora_inicio">
                                    <i class="fas fa-clock"></i> Hora de Inicio *
                                </label>
                                <input type="time" 
                                       class="form-control" 
                                       id="hora_inicio" 
                                       name="hora_inicio" 
                                       required>
                            </div>

                            <div class="alert alert-info" style="font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i>
                                La hora de fin se calculará automáticamente según la duración de la actividad (<?php echo $actividad['duracion_horas']; ?>h)
                            </div>

                            <div class="form-group">
                                <label for="plazas_disponibles">
                                    <i class="fas fa-users"></i> Plazas Disponibles *
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="plazas_disponibles" 
                                       name="plazas_disponibles" 
                                       min="1"
                                       value="1"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="precio_especial">
                                    <i class="fas fa-tag"></i> Precio Especial (€)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="precio_especial" 
                                       name="precio_especial" 
                                       min="0" 
                                       step="0.01"
                                       placeholder="Dejar vacío para usar precio normal">
                                <small style="color: #6c757d;">
                                    Precio normal: <?php echo formatPrice($actividad['precio_persona']); ?>/persona
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="notas">
                                    <i class="fas fa-comment"></i> Notas
                                </label>
                                <textarea class="form-control" 
                                          id="notas" 
                                          name="notas" 
                                          rows="2"
                                          placeholder="Información adicional sobre esta fecha..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-plus"></i> Agregar Fecha
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de disponibilidades -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Fechas Programadas (<?php echo count($disponibilidades); ?>)</h3>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if (empty($disponibilidades)): ?>
                                <div style="text-align: center; padding: 2rem; color: #6c757d;">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                    <p>No hay fechas programadas</p>
                                    <p style="font-size: 0.9rem;">Agrega tu primera fecha disponible usando el formulario</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($disponibilidades as $disp): ?>
                                    <?php
                                    $estadoColors = [
                                        'disponible' => '#2ecc71',
                                        'completo' => '#e74c3c',
                                        'cancelado' => '#6c757d'
                                    ];
                                    $color = $estadoColors[$disp['estado']] ?? '#6c757d';
                                    $isPast = strtotime($disp['fecha_inicio']) < time();
                                    ?>
                                    <div style="position: relative; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; <?php echo $isPast ? 'opacity: 0.6;' : ''; ?>">
                                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.5rem; padding-right: 32px;">
                                            <div style="flex: 1;">
                                                <strong style="color: #2c3e50; font-size: 1.1rem;">
                                                    <i class="fas fa-calendar"></i> 
                                                    <?php echo formatDate($disp['fecha_inicio']); ?>
                                                </strong>
                                                <div style="color: #6c757d; margin-top: 0.25rem;">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('H:i', strtotime($disp['fecha_inicio'])); ?> - 
                                                    <?php echo date('H:i', strtotime($disp['fecha_fin'])); ?>
                                                </div>
                                            </div>
                                            <span style="background: <?php echo $color; ?>; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem;">
                                                <?php echo ucfirst($disp['estado']); ?>
                                            </span>
                                        </div>

                                        <div style="margin-top: 1rem;">
                                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <div>
                                                    <i class="fas fa-users" style="color: #667eea;"></i>
                                                    <strong>Plazas:</strong> <?php echo $disp['plazas_disponibles']; ?>
                                                </div>
                                                <?php if ($disp['precio_especial']): ?>
                                                    <div>
                                                        <i class="fas fa-tag" style="color: #e74c3c;"></i>
                                                        <strong>Precio especial:</strong> <?php echo formatPrice($disp['precio_especial']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($disp['notas']): ?>
                                                <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 5px; margin-top: 0.5rem;">
                                                    <small><i class="fas fa-comment"></i> <?php echo htmlspecialchars($disp['notas']); ?></small>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($isPast): ?>
                                                <small style="color: #6c757d; margin-top: 0.5rem; display: block;">
                                                    <i class="fas fa-info-circle"></i> Fecha pasada
                                                </small>
                                            <?php elseif ($disp['estado'] === 'completo'): ?>
                                                <small style="color: #e74c3c; margin-top: 0.5rem; display: block;">
                                                    <i class="fas fa-exclamation-triangle"></i> Todas las plazas están ocupadas
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!$isPast && in_array($disp['estado'], ['disponible', 'completo'])): ?>
                                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef;">
                                                <a href="editar-disponibilidad.php?id=<?php echo $disp['id']; ?>" 
                                                   class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <a href="cancelar-disponibilidad.php?id=<?php echo $disp['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('¿Cancelar esta fecha? Se cancelarán todas las reservas asociadas y se enviará un mensaje a los consumidores afectados.');">
                                                    <i class="fas fa-ban"></i> Cancelar
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($disp['estado'] === 'cancelado'): ?>
                                            <a href="eliminar-disponibilidad.php?id=<?php echo $disp['id']; ?>"
                                               class="btn btn-outline-danger btn-sm"
                                               title="Eliminar definitivamente esta fecha"
                                               style="position: absolute; top: 8px; right: 8px; padding: 4px 8px;"
                                               onclick="return confirm('Esta acción eliminará definitivamente la fecha cancelada y todas sus reservas asociadas. ¿Continuar?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Consejos -->
                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header">
                            <h4><i class="fas fa-lightbulb"></i> Consejos</h4>
                        </div>
                        <div class="card-body">
                            <ul style="line-height: 1.8; color: #6c757d; font-size: 0.9rem;">
                                <li>Agrega fechas con suficiente antelación</li>
                                <li>Actualiza las plazas si necesitas cambiarlas</li>
                                <li>Usa precios especiales para fechas menos demandadas</li>
                                <li>Cancela fechas con tiempo si hay imprevistos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <style>
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
