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
$success = '';
$error = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Validaciones
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (strlen($password_nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas nuevas no coinciden';
    } else {
        try {
            // Verificar contraseña actual
            $query = "SELECT password FROM usuarios WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!verifyPassword($password_actual, $usuario['password'])) {
                $error = 'La contraseña actual es incorrecta';
            } else {
                // Actualizar contraseña
                $nueva_hash = hashPassword($password_nueva);
                $updateQuery = "UPDATE usuarios SET password = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$nueva_hash, $user_id]);
                
                $success = 'Contraseña actualizada correctamente';
                $_POST = array(); // Limpiar formulario
            }
            
        } catch (Exception $e) {
            $error = 'Error al actualizar la contraseña';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="editar.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a editar perfil
                </a>
            </div>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-key"></i> Cambiar Contraseña</h1>
                        <p>Actualiza tu contraseña de forma segura</p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <br><br>
                                <a href="editar.php" class="btn btn-primary">Volver al perfil</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Por seguridad, necesitas ingresar tu contraseña actual.
                            </div>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label for="password_actual">
                                        <i class="fas fa-lock"></i> Contraseña Actual *
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_actual" 
                                           name="password_actual" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="password_nueva">
                                        <i class="fas fa-key"></i> Nueva Contraseña *
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_nueva" 
                                           name="password_nueva" 
                                           required>
                                    <small style="color: #6c757d;">Mínimo 6 caracteres</small>
                                </div>

                                <div class="form-group">
                                    <label for="password_confirmar">
                                        <i class="fas fa-check"></i> Confirmar Nueva Contraseña *
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmar" 
                                           name="password_confirmar" 
                                           required>
                                </div>

                                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                                        <i class="fas fa-save"></i> Cambiar Contraseña
                                    </button>
                                    <a href="editar.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        // Validación de confirmación de contraseña
        document.getElementById('password_confirmar').addEventListener('input', function() {
            const nueva = document.getElementById('password_nueva').value;
            const confirmar = this.value;
            
            if (nueva !== confirmar) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password_nueva').addEventListener('input', function() {
            const confirmar = document.getElementById('password_confirmar');
            if (confirmar.value) {
                confirmar.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>
