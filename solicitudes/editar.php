<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    showAlert('Debes ser un consumidor registrado para editar solicitudes', 'warning');
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('mis-solicitudes.php');
}

$solicitud_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Obtener ID del consumidor
try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Obtener solicitud y verificar que le pertenece
    $solicitudQuery = "SELECT * FROM solicitudes_consumidores WHERE id = ? AND consumidor_id = ?";
    $solicitudStmt = $db->prepare($solicitudQuery);
    $solicitudStmt->execute([$solicitud_id, $consumidor_id]);
    $solicitud = $solicitudStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada', 'danger');
        redirect('mis-solicitudes.php');
    }
    
    // Verificar que esté activa
    if ($solicitud['estado'] !== 'activa') {
        showAlert('Solo puedes editar solicitudes activas', 'warning');
        redirect('mis-solicitudes.php');
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar la solicitud';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $fecha_deseada = $_POST['fecha_deseada'] ?? '';
    $hora_deseada = $_POST['hora_deseada'] ?? '';
    $duracion_estimada = $_POST['duracion_estimada'] ?? '';
    $presupuesto_max = $_POST['presupuesto_max'] ?? '';
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $participantes_estimados = (int)($_POST['participantes_estimados'] ?? 1);
    $requisitos_especiales = trim($_POST['requisitos_especiales'] ?? '');
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria) || empty($fecha_deseada) || empty($ubicacion)) {
        $error = 'Todos los campos obligatorios deben ser completados';
    } elseif (strlen($titulo) < 5) {
        $error = 'El título debe tener al menos 5 caracteres';
    } elseif (strlen($descripcion) < 20) {
        $error = 'La descripción debe tener al menos 20 caracteres';
    } elseif (strlen($ubicacion) < 5) {
        $error = 'La ubicación debe tener al menos 5 caracteres';
    } elseif ($participantes_estimados < 1) {
        $error = 'Debe haber al menos 1 participante';
    } elseif ($duracion_estimada && ($duracion_estimada < 0.5 || $duracion_estimada > 24)) {
        $error = 'La duración debe estar entre 0.5 y 24 horas';
    } elseif ($presupuesto_max && ($presupuesto_max < 0)) {
        $error = 'El presupuesto no puede ser negativo';
    } elseif (strtotime($fecha_deseada) < strtotime('today')) {
        $error = 'La fecha deseada debe ser futura';
    } else {
        try {
            $updateQuery = "UPDATE solicitudes_consumidores SET 
                titulo = ?, descripcion = ?, categoria = ?, fecha_deseada = ?, 
                hora_deseada = ?, duracion_estimada = ?, presupuesto_max = ?, 
                ubicacion = ?, participantes_estimados = ?, requisitos_especiales = ?,
                fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = ? AND consumidor_id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $titulo, $descripcion, $categoria, $fecha_deseada,
                $hora_deseada ?: null, $duracion_estimada ?: null, $presupuesto_max ?: null,
                $ubicacion, $participantes_estimados, $requisitos_especiales ?: null,
                $solicitud_id, $consumidor_id
            ]);
            
            showAlert('Solicitud actualizada correctamente', 'success');
            redirect('ver.php?id=' . $solicitud_id);
            
        } catch (Exception $e) {
            $error = 'Error al actualizar la solicitud: ' . $e->getMessage();
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
    <title>Editar Solicitud - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Navegación -->
            <div style="margin-bottom: 2rem;">
                <a href="ver.php?id=<?php echo $solicitud_id; ?>" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a la solicitud
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 style="margin: 0;">
                                <i class="fas fa-edit"></i> Editar Solicitud
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="titulo">Título de la solicitud *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="titulo" 
                                           name="titulo" 
                                           value="<?php echo htmlspecialchars($solicitud['titulo']); ?>"
                                           placeholder="Ej: Busco guía para ruta BTT por Sierra Nevada"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción detallada *</label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="5" 
                                              placeholder="Describe qué tipo de actividad buscas, nivel de dificultad, preferencias, etc."
                                              required><?php echo htmlspecialchars($solicitud['descripcion']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria">Categoría *</label>
                                            <select class="form-control" id="categoria" name="categoria" required>
                                                <option value="">Selecciona una categoría</option>
                                                <?php foreach ($categorias as $key => $nombre): ?>
                                                    <option value="<?php echo $key; ?>" 
                                                            <?php echo $solicitud['categoria'] === $key ? 'selected' : ''; ?>>
                                                        <?php echo $nombre; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_deseada">Fecha deseada *</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_deseada" 
                                                   name="fecha_deseada" 
                                                   value="<?php echo $solicitud['fecha_deseada']; ?>"
                                                   min="<?php echo date('Y-m-d'); ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hora_deseada">Hora deseada</label>
                                            <input type="time" 
                                                   class="form-control" 
                                                   id="hora_deseada" 
                                                   name="hora_deseada" 
                                                   value="<?php echo $solicitud['hora_deseada'] ? date('H:i', strtotime($solicitud['hora_deseada'])) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="duracion_estimada">Duración estimada (horas)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="duracion_estimada" 
                                                   name="duracion_estimada" 
                                                   value="<?php echo $solicitud['duracion_estimada']; ?>"
                                                   min="0.5" 
                                                   max="24" 
                                                   step="0.5">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="presupuesto_max">Presupuesto máximo (€)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="presupuesto_max" 
                                                   name="presupuesto_max" 
                                                   value="<?php echo $solicitud['presupuesto_max']; ?>"
                                                   min="0" 
                                                   step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="participantes_estimados">Número de participantes *</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="participantes_estimados" 
                                                   name="participantes_estimados" 
                                                   value="<?php echo $solicitud['participantes_estimados']; ?>"
                                                   min="1" 
                                                   max="50" 
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="ubicacion">Ubicación *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="ubicacion" 
                                           name="ubicacion" 
                                           value="<?php echo htmlspecialchars($solicitud['ubicacion']); ?>"
                                           placeholder="Ciudad, provincia o lugar específico"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="requisitos_especiales">Requisitos especiales</label>
                                    <textarea class="form-control" 
                                              id="requisitos_especiales" 
                                              name="requisitos_especiales" 
                                              rows="3" 
                                              placeholder="Nivel físico requerido, material necesario, restricciones de edad, etc."><?php echo htmlspecialchars($solicitud['requisitos_especiales']); ?></textarea>
                                </div>

                                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                    <a href="ver.php?id=<?php echo $solicitud_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin: 0;">
                                <i class="fas fa-lightbulb"></i> Consejos
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul style="margin: 0; padding-left: 1.5rem; color: #495057; line-height: 1.8;">
                                <li>Describe claramente qué tipo de actividad buscas</li>
                                <li>Especifica el nivel de dificultad que prefieres</li>
                                <li>Menciona si necesitas material o transporte</li>
                                <li>Indica cualquier restricción o preferencia especial</li>
                                <li>Un presupuesto aproximado ayuda a los ofertantes</li>
                                <li>La ubicación debe ser específica y clara</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header">
                            <h3 style="margin: 0;">
                                <i class="fas fa-info-circle"></i> Estado Actual
                            </h3>
                        </div>
                        <div class="card-body">
                            <p style="margin: 0;">
                                <strong>Estado:</strong> 
                                <span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.9rem;">
                                    <?php echo ucfirst($solicitud['estado']); ?>
                                </span>
                            </p>
                            <p style="margin: 0.5rem 0 0 0;">
                                <strong>Publicada:</strong> <?php echo formatDate($solicitud['fecha_creacion']); ?>
                            </p>
                            <p style="margin: 0.5rem 0 0 0;">
                                <strong>Última actualización:</strong> <?php echo formatDate($solicitud['fecha_actualizacion']); ?>
                            </p>
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
