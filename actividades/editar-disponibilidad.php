<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('mis-actividades.php');
}

$disp_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener datos de la disponibilidad
try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar permisos y obtener datos
    $query = "SELECT d.*, a.ofertante_id, a.id as actividad_id, a.titulo as actividad_titulo
              FROM disponibilidad_actividades d
              JOIN actividades a ON d.actividad_id = a.id
              WHERE d.id = ? AND a.ofertante_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$disp_id, $ofertante['id']]);
    $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disponibilidad) {
        showAlert('Disponibilidad no encontrada', 'danger');
        redirect('mis-actividades.php');
    }
    
    // Verificar si hay reservas
    $reservasQuery = "SELECT COUNT(*) as total FROM reservas 
                      WHERE disponibilidad_id = ? AND estado IN ('pendiente', 'confirmada')";
    $reservasStmt = $db->prepare($reservasQuery);
    $reservasStmt->execute([$disp_id]);
    $reservas = $reservasStmt->fetch(PDO::FETCH_ASSOC);
    
    $error = '';
    $success = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        } elseif ($plazas_disponibles < $reservas['total']) {
            $error = 'Las plazas no pueden ser menores que las reservas existentes (' . $reservas['total'] . ')';
        } else {
            try {
                // Combinar fecha y hora
                $fecha_inicio_completa = $fecha_inicio . ' ' . $hora_inicio;
                $fecha_fin_completa = null;
                
                if ($fecha_fin && $hora_fin) {
                    $fecha_fin_completa = $fecha_fin . ' ' . $hora_fin;
                }
                
                // Verificar que la fecha sea futura
                if (strtotime($fecha_inicio_completa) <= time()) {
                    $error = 'La fecha de inicio debe ser futura';
                } else {
                    // Actualizar disponibilidad
                    $updateQuery = "UPDATE disponibilidad_actividades 
                                   SET fecha_inicio = ?, fecha_fin = ?, plazas_disponibles = ?, 
                                       precio_especial = ?, notas = ?
                                   WHERE id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([
                        $fecha_inicio_completa, $fecha_fin_completa, $plazas_disponibles, 
                        $precio_especial, $notas, $disp_id
                    ]);
                    
                    $success = 'Fecha actualizada correctamente';
                    
                    // Recargar datos actualizados
                    $stmt->execute([$disp_id, $ofertante['id']]);
                    $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
    
    // Preparar datos para el formulario
    $fecha_inicio_form = $disponibilidad['fecha_inicio'] ? date('Y-m-d', strtotime($disponibilidad['fecha_inicio'])) : '';
    $hora_inicio_form = $disponibilidad['fecha_inicio'] ? date('H:i', strtotime($disponibilidad['fecha_inicio'])) : '';
    $fecha_fin_form = $disponibilidad['fecha_fin'] ? date('Y-m-d', strtotime($disponibilidad['fecha_fin'])) : '';
    $hora_fin_form = $disponibilidad['fecha_fin'] ? date('H:i', strtotime($disponibilidad['fecha_fin'])) : '';
    
} catch (Exception $e) {
    showAlert('Error: ' . $e->getMessage(), 'danger');
    redirect('mis-actividades.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fecha - <?php echo htmlspecialchars($disponibilidad['actividad_titulo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Botón de volver atrás -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="disponibilidad.php?actividad_id=<?php echo $disponibilidad['actividad_id']; ?>" 
                   class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Disponibilidad
                </a>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Editar Fecha</h3>
                        <p class="mb-0 text-muted">Actividad: <?php echo htmlspecialchars($disponibilidad['actividad_titulo']); ?></p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($reservas['total'] > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Atención:</strong> Esta fecha tiene <?php echo $reservas['total']; ?> reserva(s) activa(s). 
                                Ten cuidado al modificar las plazas disponibles.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="fecha_inicio">
                                            <i class="fas fa-calendar"></i> Fecha de Inicio *
                                        </label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_inicio" 
                                               name="fecha_inicio" 
                                               value="<?php echo $fecha_inicio_form; ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="hora_inicio">
                                            <i class="fas fa-clock"></i> Hora de Inicio *
                                        </label>
                                        <input type="time" 
                                               class="form-control" 
                                               id="hora_inicio" 
                                               name="hora_inicio" 
                                               value="<?php echo $hora_inicio_form; ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="fecha_fin">
                                            <i class="fas fa-calendar"></i> Fecha de Fin (Opcional)
                                        </label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_fin" 
                                               name="fecha_fin" 
                                               value="<?php echo $fecha_fin_form; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="hora_fin">
                                            <i class="fas fa-clock"></i> Hora de Fin (Opcional)
                                        </label>
                                        <input type="time" 
                                               class="form-control" 
                                               id="hora_fin" 
                                               name="hora_fin" 
                                               value="<?php echo $hora_fin_form; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="plazas_disponibles">
                                            <i class="fas fa-users"></i> Plazas Disponibles *
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="plazas_disponibles" 
                                               name="plazas_disponibles" 
                                               min="<?php echo max(1, $reservas['total']); ?>"
                                               value="<?php echo $disponibilidad['plazas_disponibles']; ?>"
                                               required>
                                        <small class="form-text text-muted">
                                            Mínimo: <?php echo max(1, $reservas['total']); ?> (reservas existentes)
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="precio_especial">
                                            <i class="fas fa-tag"></i> Precio Especial (€)
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="precio_especial" 
                                               name="precio_especial" 
                                               min="0" 
                                               step="0.01"
                                               value="<?php echo $disponibilidad['precio_especial'] ? $disponibilidad['precio_especial'] : ''; ?>"
                                               placeholder="Dejar vacío para usar precio normal">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="notas">
                                    <i class="fas fa-comment"></i> Notas Adicionales
                                </label>
                                <textarea class="form-control" 
                                          id="notas" 
                                          name="notas" 
                                          rows="3" 
                                          placeholder="Información adicional sobre esta fecha..."><?php echo htmlspecialchars($disponibilidad['notas']); ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2 justify-content-between">
                                <a href="disponibilidad.php?actividad_id=<?php echo $disponibilidad['actividad_id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
