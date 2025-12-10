<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nombre = sanitizeInput($_POST['nombre']);
    $apellidos = sanitizeInput($_POST['apellidos']);
    $telefono = sanitizeInput($_POST['telefono']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $tipo = $_POST['tipo'];
    $terminos = isset($_POST['terminos']);
    
    // Validaciones
    if (empty($email) || empty($password) || empty($confirm_password) || empty($nombre) || empty($apellidos) || empty($tipo)) {
        $error = 'Por favor, completa todos los campos obligatorios';
    } elseif (!validateEmail($email)) {
        $error = 'Por favor, ingresa un email válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!empty($telefono) && !validatePhone($telefono)) {
        $error = 'Por favor, ingresa un teléfono válido (9 dígitos)';
    } elseif (!$terminos) {
        $error = 'Debes aceptar los términos y condiciones';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar si el email ya existe
            $checkQuery = "SELECT id FROM usuarios WHERE email = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $error = 'Este email ya está registrado';
            } else {
                // Insertar usuario
                $hashedPassword = hashPassword($password);
                $insertQuery = "INSERT INTO usuarios (email, password, nombre, apellidos, telefono, fecha_nacimiento, tipo) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$email, $hashedPassword, $nombre, $apellidos, $telefono ?: null, $fecha_nacimiento ?: null, $tipo]);
                
                $usuario_id = $db->lastInsertId();
                
                // Crear perfil específico según el tipo
                if ($tipo === 'ofertante') {
                    $ofertanteQuery = "INSERT INTO ofertantes (usuario_id) VALUES (?)";
                    $ofertanteStmt = $db->prepare($ofertanteQuery);
                    $ofertanteStmt->execute([$usuario_id]);
                } elseif ($tipo === 'consumidor') {
                    $consumidorQuery = "INSERT INTO consumidores (usuario_id) VALUES (?)";
                    $consumidorStmt = $db->prepare($consumidorQuery);
                    $consumidorStmt->execute([$usuario_id]);
                }
                
                $success = 'Registro exitoso. Ya puedes iniciar sesión.';
                // Limpiar formulario
                $_POST = array();
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Inténtalo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="min-height: 80vh; padding: 2rem 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="form-container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">Crear Cuenta</h1>
                <p style="color: #6c757d;">Únete a nuestra comunidad de aventureros</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label for="tipo">
                        <i class="fas fa-user-tag"></i> Tipo de Usuario *
                    </label>
                    <select class="form-control" id="tipo" name="tipo" required>
                        <option value="">Selecciona tu tipo de usuario</option>
                        <option value="ofertante" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'ofertante') ? 'selected' : ''; ?>>
                            Ofertante - Organizo actividades
                        </option>
                        <option value="consumidor" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'consumidor') ? 'selected' : ''; ?>>
                            Consumidor - Busco actividades
                        </option>
                    </select>
                    <div class="invalid-feedback">
                        Por favor, selecciona tu tipo de usuario
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-user"></i> Nombre *
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="nombre" 
                           name="nombre" 
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                           required>
                    <div class="invalid-feedback">
                        Por favor, ingresa tu nombre
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="apellidos">
                        <i class="fas fa-user"></i> Apellidos *
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="apellidos" 
                           name="apellidos" 
                           value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>"
                           required>
                    <div class="invalid-feedback">
                        Por favor, ingresa tus apellidos
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                    <div class="invalid-feedback">
                        Por favor, ingresa un email válido
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="telefono">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="tel" 
                           class="form-control" 
                           id="telefono" 
                           name="telefono" 
                           value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                           placeholder="123456789">
                    <div class="invalid-feedback">
                        Por favor, ingresa un teléfono válido (9 dígitos)
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">
                        <i class="fas fa-calendar"></i> Fecha de Nacimiento
                    </label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_nacimiento" 
                           name="fecha_nacimiento" 
                           value="<?php echo isset($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña *
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required>
                    <div class="invalid-feedback">
                        La contraseña debe tener al menos 6 caracteres
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmar Contraseña *
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required>
                    <div class="invalid-feedback">
                        Las contraseñas no coinciden
                    </div>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <input type="checkbox" 
                               id="terminos" 
                               name="terminos" 
                               required
                               style="margin-top: 5px;">
                        <label for="terminos" style="font-size: 0.9rem; line-height: 1.4;">
                            Acepto los <a href="terminos.php" target="_blank" style="color: #667eea;">términos y condiciones</a> 
                            y la <a href="privacidad.php" target="_blank" style="color: #667eea;">política de privacidad</a> *
                        </label>
                    </div>
                    <div class="invalid-feedback">
                        Debes aceptar los términos y condiciones
                    </div>
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-user-plus"></i> Crear Cuenta
                    </button>
                    
                    <div style="margin-top: 1rem;">
                        <p style="color: #6c757d; margin-bottom: 0.5rem;">¿Ya tienes cuenta?</p>
                        <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                            Inicia sesión aquí
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // Validación de confirmación de contraseña
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validación de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>
