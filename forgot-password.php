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
$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Procesar solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $error = 'Por favor, ingresa tu email';
    } elseif (!validateEmail($email)) {
        $error = 'Por favor, ingresa un email válido';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar si el email existe
            $query = "SELECT id, nombre, apellidos FROM usuarios WHERE email = ? AND activo = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generar token de recuperación
                $resetToken = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
                
                // Guardar token en la base de datos
                $updateQuery = "UPDATE usuarios SET token_reset = ?, token_expires = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$resetToken, $expires, $user['id']]);
                
                // En un entorno real, aquí enviarías un email con el enlace
                // Por ahora, mostramos el enlace en la página (solo para desarrollo)
                $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                            "://" . $_SERVER['HTTP_HOST'] . 
                            dirname($_SERVER['PHP_SELF']) . 
                            "/forgot-password.php?step=reset&token=" . $resetToken;
                
                $success = 'Se ha enviado un enlace de recuperación a tu email.';
                
                // Para desarrollo: mostrar el enlace (eliminar en producción)
                if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                    $success .= '<br><br><strong>Enlace de recuperación (solo para desarrollo):</strong><br>';
                    $success .= '<a href="' . htmlspecialchars($resetLink) . '" style="color: #667eea; word-break: break-all;">' . htmlspecialchars($resetLink) . '</a>';
                }
                
            } else {
                // Por seguridad, no revelamos si el email existe o no
                $success = 'Si el email existe en nuestro sistema, recibirás un enlace de recuperación.';
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Inténtalo más tarde.';
        }
    }
}

// Procesar reset de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $token = sanitizeInput($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, completa todos los campos';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar token válido y no expirado
            $query = "SELECT id FROM usuarios WHERE token_reset = ? AND token_expires > NOW() AND activo = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Actualizar contraseña
                $hashedPassword = hashPassword($password);
                $updateQuery = "UPDATE usuarios SET password = ?, token_reset = NULL, token_expires = NULL WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$hashedPassword, $user['id']]);
                
                $success = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
                $step = 'success';
            } else {
                $error = 'El enlace de recuperación no es válido o ha expirado. Por favor, solicita uno nuevo.';
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Inténtalo más tarde.';
        }
    }
}

// Si hay token en la URL, verificar que sea válido
if ($step === 'reset' && !empty($token)) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id FROM usuarios WHERE token_reset = ? AND token_expires > NOW() AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() === 0) {
            $error = 'El enlace de recuperación no es válido o ha expirado.';
            $step = 'request';
            $token = '';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar el token.';
        $step = 'request';
        $token = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem 0;">
        <div class="form-container">
            <?php if ($step === 'request'): ?>
                <!-- Solicitar recuperación -->
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">
                        <i class="fas fa-key"></i> Recuperar Contraseña
                    </h1>
                    <p style="color: #6c757d;">Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Volver al Login
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="request_reset" value="1">
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="tu@email.com"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, ingresa un email válido
                            </div>
                        </div>
                        
                        <div class="form-group" style="text-align: center;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                <i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación
                            </button>
                            
                            <div style="margin-top: 1rem;">
                                <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-arrow-left"></i> Volver al Login
                                </a>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($step === 'reset' && !empty($token)): ?>
                <!-- Resetear contraseña -->
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">
                        <i class="fas fa-lock"></i> Nueva Contraseña
                    </h1>
                    <p style="color: #6c757d;">Ingresa tu nueva contraseña</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="forgot-password.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Solicitar Nuevo Enlace
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Nueva Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   minlength="6"
                                   required>
                            <div class="invalid-feedback">
                                La contraseña debe tener al menos 6 caracteres
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   minlength="6"
                                   required>
                            <div class="invalid-feedback">
                                Las contraseñas deben coincidir
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Requisitos:</strong> La contraseña debe tener al menos 6 caracteres
                        </div>
                        
                        <div class="form-group" style="text-align: center;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                <i class="fas fa-check"></i> Cambiar Contraseña
                            </button>
                            
                            <div style="margin-top: 1rem;">
                                <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-arrow-left"></i> Volver al Login
                                </a>
                            </div>
                        </div>
                    </form>
                    
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
                        
                        document.getElementById('password').addEventListener('input', function() {
                            const confirmPassword = document.getElementById('confirm_password');
                            if (confirmPassword.value) {
                                confirmPassword.dispatchEvent(new Event('input'));
                            }
                        });
                    </script>
                <?php endif; ?>
                
            <?php elseif ($step === 'success'): ?>
                <!-- Éxito -->
                <div style="text-align: center;">
                    <div style="font-size: 4rem; color: #2ecc71; margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">¡Contraseña Actualizada!</h1>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        Tu contraseña ha sido actualizada correctamente. Ya puedes iniciar sesión con tu nueva contraseña.
                    </p>
                    <a href="login.php" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Ir al Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>

