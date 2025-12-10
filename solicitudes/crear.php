<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    showAlert('Debes ser un consumidor registrado para crear solicitudes', 'warning');
    redirect('../login.php');
}

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
} catch (Exception $e) {
    $error = 'Error al obtener datos del consumidor';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    $fecha_deseada = $_POST['fecha_deseada'];
    $hora_deseada = $_POST['hora_deseada'] ?? null;
    $duracion_estimada = $_POST['duracion_estimada'] ?? null;
    $presupuesto_max = $_POST['presupuesto_max'] ?? null;
    $ubicacion = sanitizeInput($_POST['ubicacion']);
    $participantes_estimados = $_POST['participantes_estimados'];
    $requisitos_especiales = sanitizeInput($_POST['requisitos_especiales']);
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria) || 
        empty($fecha_deseada) || empty($ubicacion) || empty($participantes_estimados)) {
        $error = 'Por favor, completa todos los campos obligatorios';
    } elseif (strtotime($fecha_deseada) < time()) {
        $error = 'La fecha deseada debe ser futura';
    } else {
        try {
            $insertQuery = "INSERT INTO solicitudes_consumidores (
                consumidor_id, titulo, descripcion, categoria, fecha_deseada, hora_deseada,
                duracion_estimada, presupuesto_max, ubicacion, participantes_estimados,
                requisitos_especiales, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')";
            
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                $consumidor_id, $titulo, $descripcion, $categoria, $fecha_deseada, $hora_deseada,
                $duracion_estimada, $presupuesto_max, $ubicacion, $participantes_estimados,
                $requisitos_especiales
            ]);
            
            showAlert('Solicitud creada correctamente. Los ofertantes podrán enviarte propuestas.', 'success');
            redirect('mis-solicitudes.php');
            
        } catch (Exception $e) {
            $error = 'Error al crear la solicitud: ' . $e->getMessage();
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
    <title>Crear Solicitud - ActividadesConnect</title>
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

            <div style="max-width: 900px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-bullhorn"></i> Crear Solicitud Personalizada</h1>
                        <p>Describe la actividad que buscas y los ofertantes te enviarán propuestas</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-left: 4px solid #17a2b8; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                            <h3 style="color: #0c5460; margin: 0 0 1rem 0;">
                                <i class="fas fa-lightbulb"></i> ¿Cómo funciona?
                            </h3>
                            <ul style="margin: 0; padding-left: 1.5rem; color: #0c5460; line-height: 1.8;">
                                <li>Publica tu solicitud con los detalles de lo que buscas</li>
                                <li>Los ofertantes verán tu solicitud y podrán enviarte propuestas</li>
                                <li>Recibirás notificaciones cuando recibas propuestas</li>
                                <li>Podrás comparar y aceptar la mejor propuesta</li>
                            </ul>
                        </div>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Información Básica -->
                            <h3 style="color: #667eea; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-info-circle"></i> ¿Qué Buscas?
                            </h3>

                            <div class="form-group">
                                <label for="titulo">
                                    <i class="fas fa-heading"></i> Título de la Solicitud *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="titulo" 
                                       name="titulo" 
                                       value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>"
                                       placeholder="Ej: Busco ruta de senderismo en Sierra Norte"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">
                                    <i class="fas fa-align-left"></i> Descripción Detallada *
                                </label>
                                <textarea class="form-control" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="6"
                                          placeholder="Describe lo que buscas: tipo de actividad, nivel, duración deseada, qué te gustaría hacer..."
                                          required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="categoria">
                                    <i class="fas fa-tag"></i> Categoría *
                                </label>
                                <select class="form-control" id="categoria" name="categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Detalles -->
                            <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-calendar-alt"></i> Detalles
                            </h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="fecha_deseada">
                                        <i class="fas fa-calendar"></i> Fecha Deseada *
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="fecha_deseada" 
                                           name="fecha_deseada" 
                                           value="<?php echo isset($_POST['fecha_deseada']) ? $_POST['fecha_deseada'] : ''; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="hora_deseada">
                                        <i class="fas fa-clock"></i> Hora Deseada
                                    </label>
                                    <input type="time" 
                                           class="form-control" 
                                           id="hora_deseada" 
                                           name="hora_deseada" 
                                           value="<?php echo isset($_POST['hora_deseada']) ? $_POST['hora_deseada'] : ''; ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="duracion_estimada">
                                        <i class="fas fa-hourglass-half"></i> Duración Estimada (h)
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="duracion_estimada" 
                                           name="duracion_estimada" 
                                           value="<?php echo isset($_POST['duracion_estimada']) ? $_POST['duracion_estimada'] : ''; ?>"
                                           min="0.5" 
                                           step="0.5"
                                           placeholder="3.0">
                                </div>

                                <div class="form-group">
                                    <label for="presupuesto_max">
                                        <i class="fas fa-euro-sign"></i> Presupuesto Máximo (€)
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="presupuesto_max" 
                                           name="presupuesto_max" 
                                           value="<?php echo isset($_POST['presupuesto_max']) ? $_POST['presupuesto_max'] : ''; ?>"
                                           min="0" 
                                           step="0.01"
                                           placeholder="100.00">
                                </div>

                                <div class="form-group">
                                    <label for="participantes_estimados">
                                        <i class="fas fa-users"></i> Participantes *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="participantes_estimados" 
                                           name="participantes_estimados" 
                                           value="<?php echo isset($_POST['participantes_estimados']) ? $_POST['participantes_estimados'] : '1'; ?>"
                                           min="1"
                                           required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="ubicacion">
                                    <i class="fas fa-map-marker-alt"></i> Ubicación Deseada *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ubicacion" 
                                       name="ubicacion" 
                                       value="<?php echo isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : ''; ?>"
                                       placeholder="Ej: Sevilla centro, Sierra Norte, etc."
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="requisitos_especiales">
                                    <i class="fas fa-list-ul"></i> Requisitos Especiales
                                </label>
                                <textarea class="form-control" 
                                          id="requisitos_especiales" 
                                          name="requisitos_especiales" 
                                          rows="4"
                                          placeholder="Necesidades específicas, preferencias, restricciones..."><?php echo isset($_POST['requisitos_especiales']) ? htmlspecialchars($_POST['requisitos_especiales']) : ''; ?></textarea>
                            </div>

                            <!-- Botones -->
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-paper-plane"></i> Publicar Solicitud
                                </button>
                                <a href="../dashboard.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
