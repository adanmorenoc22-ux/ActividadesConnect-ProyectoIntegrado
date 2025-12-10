<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$success = '';
$error = '';

// Obtener datos actuales del usuario
try {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener datos específicos según el tipo
    if ($user_type === 'ofertante') {
        $queryEsp = "SELECT * FROM ofertantes WHERE usuario_id = ?";
        $stmtEsp = $db->prepare($queryEsp);
        $stmtEsp->execute([$user_id]);
        $datosEspecificos = $stmtEsp->fetch(PDO::FETCH_ASSOC);
    } elseif ($user_type === 'consumidor') {
        $queryEsp = "SELECT * FROM consumidores WHERE usuario_id = ?";
        $stmtEsp = $db->prepare($queryEsp);
        $stmtEsp->execute([$user_id]);
        $datosEspecificos = $stmtEsp->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar los datos del perfil';
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitizeInput($_POST['nombre']);
    $apellidos = sanitizeInput($_POST['apellidos']);
    $telefono = sanitizeInput($_POST['telefono']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    
    // Datos específicos
    if ($user_type === 'ofertante') {
        $descripcion = sanitizeInput($_POST['descripcion']);
        $experiencia = sanitizeInput($_POST['experiencia']);
        $certificaciones = sanitizeInput($_POST['certificaciones']);
        $disponibilidad_general = sanitizeInput($_POST['disponibilidad_general']);
    } elseif ($user_type === 'consumidor') {
        $preferencias = sanitizeInput($_POST['preferencias']);
        $nivel_experiencia = $_POST['nivel_experiencia'];
        $restricciones_medicas = sanitizeInput($_POST['restricciones_medicas']);
        $alergias = sanitizeInput($_POST['alergias']);
    }
    
    // Validaciones
    if (empty($nombre) || empty($apellidos)) {
        $error = 'El nombre y apellidos son obligatorios';
    } elseif (!empty($telefono) && !validatePhone($telefono)) {
        $error = 'Por favor, ingresa un teléfono válido (9 dígitos)';
    } else {
        try {
            // Actualizar datos básicos
            $updateQuery = "UPDATE usuarios SET nombre = ?, apellidos = ?, telefono = ?, fecha_nacimiento = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$nombre, $apellidos, $telefono ?: null, $fecha_nacimiento ?: null, $user_id]);
            
            // Actualizar datos específicos
            if ($user_type === 'ofertante') {
                $updateEspQuery = "UPDATE ofertantes SET 
                                  descripcion = ?, experiencia = ?, certificaciones = ?, 
                                  disponibilidad_general = ?
                                  WHERE usuario_id = ?";
                $updateEspStmt = $db->prepare($updateEspQuery);
                $updateEspStmt->execute([
                    $descripcion, $experiencia, $certificaciones,
                    $disponibilidad_general, $user_id
                ]);
            } elseif ($user_type === 'consumidor') {
                $updateEspQuery = "UPDATE consumidores SET 
                                  preferencias = ?, nivel_experiencia = ?, 
                                  restricciones_medicas = ?, alergias = ?
                                  WHERE usuario_id = ?";
                $updateEspStmt = $db->prepare($updateEspQuery);
                $updateEspStmt->execute([
                    $preferencias, $nivel_experiencia, 
                    $restricciones_medicas, $alergias, $user_id
                ]);
            }
            
            // Actualizar sesión
            $_SESSION['user_name'] = $nombre . ' ' . $apellidos;
            
            $success = 'Perfil actualizado correctamente';
            
            // Recargar datos
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmtEsp->execute([$user_id]);
            $datosEspecificos = $stmtEsp->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el perfil: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - ActividadesConnect</title>
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

            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-user-edit"></i> Editar Mi Perfil</h1>
                        <p>Tipo de usuario: <strong><?php echo ucfirst($user_type); ?></strong></p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Datos Básicos -->
                            <h3 style="color: #667eea; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                <i class="fas fa-user"></i> Información Personal
                            </h3>

                            <div class="form-group">
                                <label for="nombre">
                                    <i class="fas fa-user"></i> Nombre *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="apellidos">
                                    <i class="fas fa-user"></i> Apellidos *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="apellidos" 
                                       name="apellidos" 
                                       value="<?php echo htmlspecialchars($usuario['apellidos']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                       disabled
                                       style="background: #f8f9fa;">
                                <small style="color: #6c757d;">El email no se puede cambiar</small>
                            </div>

                            <div class="form-group">
                                <label for="telefono">
                                    <i class="fas fa-phone"></i> Teléfono
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono" 
                                       value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                       placeholder="123456789">
                            </div>

                            <div class="form-group">
                                <label for="fecha_nacimiento">
                                    <i class="fas fa-calendar"></i> Fecha de Nacimiento
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_nacimiento" 
                                       name="fecha_nacimiento" 
                                       value="<?php echo $usuario['fecha_nacimiento'] ?? ''; ?>">
                            </div>

                            <?php if ($user_type === 'ofertante'): ?>
                                <!-- Datos de Ofertante -->
                                <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-briefcase"></i> Información Profesional
                                </h3>

                                <div class="form-group">
                                    <label for="descripcion">
                                        <i class="fas fa-align-left"></i> Descripción
                                    </label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="4"
                                              placeholder="Cuéntanos sobre ti y tu experiencia..."><?php echo htmlspecialchars($datosEspecificos['descripcion'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="experiencia">
                                        <i class="fas fa-award"></i> Experiencia
                                    </label>
                                    <textarea class="form-control" 
                                              id="experiencia" 
                                              name="experiencia" 
                                              rows="3"
                                              placeholder="Años de experiencia, trabajos anteriores, etc."><?php echo htmlspecialchars($datosEspecificos['experiencia'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="certificaciones">
                                        <i class="fas fa-certificate"></i> Certificaciones y Títulos
                                    </label>
                                    <textarea class="form-control" 
                                              id="certificaciones" 
                                              name="certificaciones" 
                                              rows="3"
                                              placeholder="Certificaciones, licencias, títulos relevantes..."><?php echo htmlspecialchars($datosEspecificos['certificaciones'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="disponibilidad_general">
                                        <i class="fas fa-clock"></i> Disponibilidad General
                                    </label>
                                    <textarea class="form-control" 
                                              id="disponibilidad_general" 
                                              name="disponibilidad_general" 
                                              rows="2"
                                              placeholder="Ej: Fines de semana, tardes entre semana..."><?php echo htmlspecialchars($datosEspecificos['disponibilidad_general'] ?? ''); ?></textarea>
                                </div>



                            <?php elseif ($user_type === 'consumidor'): ?>
                                <!-- Datos de Consumidor -->
                                <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-heart"></i> Preferencias y Datos Adicionales
                                </h3>

                                <div class="form-group">
                                    <label for="preferencias">
                                        <i class="fas fa-star"></i> Preferencias de Actividades
                                    </label>
                                    <textarea class="form-control" 
                                              id="preferencias" 
                                              name="preferencias" 
                                              rows="3"
                                              placeholder="Qué tipo de actividades te interesan..."><?php echo htmlspecialchars($datosEspecificos['preferencias'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="nivel_experiencia">
                                        <i class="fas fa-signal"></i> Nivel de Experiencia
                                    </label>
                                    <select class="form-control" id="nivel_experiencia" name="nivel_experiencia">
                                        <option value="principiante" <?php echo ($datosEspecificos['nivel_experiencia'] ?? '') === 'principiante' ? 'selected' : ''; ?>>Principiante</option>
                                        <option value="intermedio" <?php echo ($datosEspecificos['nivel_experiencia'] ?? '') === 'intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                                        <option value="avanzado" <?php echo ($datosEspecificos['nivel_experiencia'] ?? '') === 'avanzado' ? 'selected' : ''; ?>>Avanzado</option>
                                        <option value="experto" <?php echo ($datosEspecificos['nivel_experiencia'] ?? '') === 'experto' ? 'selected' : ''; ?>>Experto</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="restricciones_medicas">
                                        <i class="fas fa-heartbeat"></i> Restricciones Médicas
                                    </label>
                                    <textarea class="form-control" 
                                              id="restricciones_medicas" 
                                              name="restricciones_medicas" 
                                              rows="2"
                                              placeholder="Condiciones médicas relevantes..."><?php echo htmlspecialchars($datosEspecificos['restricciones_medicas'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="alergias">
                                        <i class="fas fa-allergies"></i> Alergias
                                    </label>
                                    <textarea class="form-control" 
                                              id="alergias" 
                                              name="alergias" 
                                              rows="2"
                                              placeholder="Alergias alimentarias, medicamentos, etc."><?php echo htmlspecialchars($datosEspecificos['alergias'] ?? ''); ?></textarea>
                                </div>

                            <?php endif; ?>

                            <!-- Botones -->
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="../dashboard.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cambiar contraseña -->
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Cambiar Contraseña</h3>
                    </div>
                    <div class="card-body">
                        <p>¿Deseas cambiar tu contraseña?</p>
                        <a href="cambiar-password.php" class="btn btn-warning">
                            <i class="fas fa-lock"></i> Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
