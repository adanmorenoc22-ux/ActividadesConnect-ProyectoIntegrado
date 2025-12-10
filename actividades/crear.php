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
$success = '';
$error = '';

// Obtener ID del ofertante
try {
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
} catch (Exception $e) {
    $error = 'Error al obtener datos del ofertante';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    $subcategoria = sanitizeInput($_POST['subcategoria']);
    $duracion_horas = $_POST['duracion_horas'];
    $dificultad = $_POST['dificultad'];
    $precio_persona = $_POST['precio_persona'];
    $lugar_inicio = sanitizeInput($_POST['lugar_inicio']);
    $lugar_fin = sanitizeInput($_POST['lugar_fin']);
    $material_requerido = sanitizeInput($_POST['material_requerido']);
    $material_incluido = sanitizeInput($_POST['material_incluido']);
    $preparacion_fisica = sanitizeInput($_POST['preparacion_fisica']);
    $requisitos_edad_min = $_POST['requisitos_edad_min'] ?? null;
    $requisitos_edad_max = $_POST['requisitos_edad_max'] ?? null;
    $restricciones = sanitizeInput($_POST['restricciones']);
    $incluye_transporte = isset($_POST['incluye_transporte']) ? 1 : 0;
    $incluye_comida = isset($_POST['incluye_comida']) ? 1 : 0;
    $incluye_seguro = isset($_POST['incluye_seguro']) ? 1 : 0;
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria) || empty($duracion_horas) || 
        empty($dificultad) || empty($precio_persona) || empty($lugar_inicio)) {
        $error = 'Por favor, completa todos los campos obligatorios';
    } elseif ($precio_persona <= 0) {
        $error = 'El precio debe ser mayor que 0';
    } else {
        try {
            $insertQuery = "INSERT INTO actividades (
                ofertante_id, titulo, descripcion, categoria, subcategoria, duracion_horas,
                dificultad, precio_persona, precio_grupo, min_participantes, max_participantes,
                lugar_inicio, lugar_fin, material_requerido, material_incluido, preparacion_fisica,
                requisitos_edad_min, requisitos_edad_max, restricciones, incluye_transporte,
                incluye_comida, incluye_seguro, estado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, NULL, 1, 10, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa'
            )";
            
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                $ofertante_id, $titulo, $descripcion, $categoria, $subcategoria, $duracion_horas,
                $dificultad, $precio_persona,
                $lugar_inicio, $lugar_fin, $material_requerido, $material_incluido, $preparacion_fisica,
                $requisitos_edad_min, $requisitos_edad_max, $restricciones, $incluye_transporte,
                $incluye_comida, $incluye_seguro
            ]);
            
            $actividad_id = $db->lastInsertId();
            
            showAlert('Actividad creada correctamente', 'success');
            redirect('mis-actividades.php');
            
        } catch (Exception $e) {
            $error = 'Error al crear la actividad: ' . $e->getMessage();
        }
    }
}

$categorias = getActivityCategories();
$niveles_dificultad = getDifficultyLevels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Actividad - ActividadesConnect</title>
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

            <div style="max-width: 900px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-plus-circle"></i> Crear Nueva Actividad</h1>
                        <p>Completa todos los detalles de tu actividad</p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Información Básica -->
                            <h3 style="color: #667eea; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-info-circle"></i> Información Básica
                            </h3>

                            <div class="form-group">
                                <label for="titulo">
                                    <i class="fas fa-heading"></i> Título de la Actividad *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="titulo" 
                                       name="titulo" 
                                       value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>"
                                       placeholder="Ej: Ruta por la Sevilla Medieval"
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
                                          placeholder="Describe tu actividad en detalle: qué harán los participantes, qué verán, qué aprenderán..."
                                          required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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

                                <div class="form-group">
                                    <label for="subcategoria">
                                        <i class="fas fa-tags"></i> Subcategoría
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="subcategoria" 
                                           name="subcategoria" 
                                           value="<?php echo isset($_POST['subcategoria']) ? htmlspecialchars($_POST['subcategoria']) : ''; ?>"
                                           placeholder="Ej: Histórico, Medieval, etc.">
                                </div>
                            </div>

                            <!-- Detalles de la Actividad -->
                            <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-cogs"></i> Detalles de la Actividad
                            </h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="duracion_horas">
                                        <i class="fas fa-clock"></i> Duración (horas) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="duracion_horas" 
                                           name="duracion_horas" 
                                           value="<?php echo isset($_POST['duracion_horas']) ? $_POST['duracion_horas'] : ''; ?>"
                                           min="0.5" 
                                           step="0.5"
                                           placeholder="3.5"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="dificultad">
                                        <i class="fas fa-signal"></i> Dificultad *
                                    </label>
                                    <select class="form-control" id="dificultad" name="dificultad" required>
                                        <option value="">Selecciona nivel</option>
                                        <?php foreach ($niveles_dificultad as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['dificultad']) && $_POST['dificultad'] === $key) ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="precio_persona">
                                        <i class="fas fa-euro-sign"></i> Precio/Persona (€) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="precio_persona" 
                                           name="precio_persona" 
                                           value="<?php echo isset($_POST['precio_persona']) ? $_POST['precio_persona'] : ''; ?>"
                                           min="0" 
                                           step="0.01"
                                           placeholder="25.00"
                                           required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                <!-- Eliminados: precio de grupo, mínimo y máximo de participantes en la creación -->
                            </div>

                            <!-- Ubicación -->
                            <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-map-marker-alt"></i> Ubicación
                            </h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="lugar_inicio">
                                        <i class="fas fa-map-marked-alt"></i> Lugar de Inicio *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="lugar_inicio" 
                                           name="lugar_inicio" 
                                           value="<?php echo isset($_POST['lugar_inicio']) ? htmlspecialchars($_POST['lugar_inicio']) : ''; ?>"
                                           placeholder="Ej: Plaza del Triunfo, Sevilla"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="lugar_fin">
                                        <i class="fas fa-flag-checkered"></i> Lugar de Fin
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="lugar_fin" 
                                           name="lugar_fin" 
                                           value="<?php echo isset($_POST['lugar_fin']) ? htmlspecialchars($_POST['lugar_fin']) : ''; ?>"
                                           placeholder="Mismo lugar de inicio o diferente">
                                </div>
                            </div>

                            <!-- Material y Requisitos -->
                            <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-box"></i> Material y Requisitos
                            </h3>

                            <div class="form-group">
                                <label for="material_requerido">
                                    <i class="fas fa-backpack"></i> Material Requerido
                                </label>
                                <textarea class="form-control" 
                                          id="material_requerido" 
                                          name="material_requerido" 
                                          rows="3"
                                          placeholder="Qué debe traer el participante: ropa cómoda, calzado deportivo, agua..."><?php echo isset($_POST['material_requerido']) ? htmlspecialchars($_POST['material_requerido']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="material_incluido">
                                    <i class="fas fa-gift"></i> Material Incluido
                                </label>
                                <textarea class="form-control" 
                                          id="material_incluido" 
                                          name="material_incluido" 
                                          rows="3"
                                          placeholder="Qué proporcionas tú: casco, arnés, herramientas..."><?php echo isset($_POST['material_incluido']) ? htmlspecialchars($_POST['material_incluido']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="preparacion_fisica">
                                    <i class="fas fa-heartbeat"></i> Preparación Física Necesaria
                                </label>
                                <textarea class="form-control" 
                                          id="preparacion_fisica" 
                                          name="preparacion_fisica" 
                                          rows="2"
                                          placeholder="Nivel de condición física requerido..."><?php echo isset($_POST['preparacion_fisica']) ? htmlspecialchars($_POST['preparacion_fisica']) : ''; ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="requisitos_edad_min">
                                        <i class="fas fa-child"></i> Edad Mínima
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="requisitos_edad_min" 
                                           name="requisitos_edad_min" 
                                           value="<?php echo isset($_POST['requisitos_edad_min']) ? $_POST['requisitos_edad_min'] : ''; ?>"
                                           min="0"
                                           placeholder="18">
                                </div>

                                <div class="form-group">
                                    <label for="requisitos_edad_max">
                                        <i class="fas fa-user"></i> Edad Máxima
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="requisitos_edad_max" 
                                           name="requisitos_edad_max" 
                                           value="<?php echo isset($_POST['requisitos_edad_max']) ? $_POST['requisitos_edad_max'] : ''; ?>"
                                           min="0"
                                           placeholder="65">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="restricciones">
                                    <i class="fas fa-exclamation-triangle"></i> Restricciones y Advertencias
                                </label>
                                <textarea class="form-control" 
                                          id="restricciones" 
                                          name="restricciones" 
                                          rows="3"
                                          placeholder="Restricciones médicas, contraindicaciones, advertencias importantes..."><?php echo isset($_POST['restricciones']) ? htmlspecialchars($_POST['restricciones']) : ''; ?></textarea>
                            </div>

                            <!-- Servicios Incluidos -->
                            <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-check-circle"></i> Servicios Incluidos
                            </h3>

                            <div class="form-group">
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" 
                                               name="incluye_transporte"
                                               <?php echo (isset($_POST['incluye_transporte'])) ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-bus"></i> Incluye transporte</span>
                                    </label>
                                </div>

                                <div style="margin-bottom: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" 
                                               name="incluye_comida"
                                               <?php echo (isset($_POST['incluye_comida'])) ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-utensils"></i> Incluye comida</span>
                                    </label>
                                </div>

                                <div style="margin-bottom: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" 
                                               name="incluye_seguro"
                                               <?php echo (isset($_POST['incluye_seguro'])) ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-shield-alt"></i> Incluye seguro</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-save"></i> Crear Actividad
                                </button>
                                <a href="mis-actividades.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
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
