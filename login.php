<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, email, password, nombre, apellidos, tipo, activo FROM usuarios WHERE email = ? AND activo = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (verifyPassword($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellidos'];
                    $_SESSION['user_type'] = $user['tipo'];
                    
                    // Actualizar último acceso
                    $updateQuery = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([$user['id']]);
                    
                    redirect('dashboard.php');
                } else {
                    $error = 'Credenciales incorrectas';
                }
            } else {
                $error = 'Credenciales incorrectas';
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
    <title>Iniciar Sesión - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="form-container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">Iniciar Sesión</h1>
                <p style="color: #6c757d;">Accede a tu cuenta para continuar</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
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
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required>
                    <div class="invalid-feedback">
                        Por favor, ingresa tu contraseña
                    </div>
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                    
                    <div style="margin-top: 1rem;">
                        <p style="color: #6c757d; margin-bottom: 0.5rem;">¿No tienes cuenta?</p>
                        <a href="register.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                            Regístrate aquí
                        </a>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <a href="forgot-password.php" style="color: #6c757d; text-decoration: none; font-size: 0.9rem;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
