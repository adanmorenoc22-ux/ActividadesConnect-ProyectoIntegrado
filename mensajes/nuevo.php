<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$destinatario_id = $_GET['destinatario_id'] ?? '';
$destinatario_info = null;

// Si viene con destinatario_id, obtener información del usuario
if ($destinatario_id) {
    try {
        $usuarioQuery = "SELECT id, nombre, apellidos, email, tipo FROM usuarios WHERE id = ? AND activo = 1";
        $usuarioStmt = $db->prepare($usuarioQuery);
        $usuarioStmt->execute([$destinatario_id]);
        $destinatario_info = $usuarioStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Error al cargar datos del destinatario';
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinatario_email = sanitizeInput($_POST['destinatario_email']);
    $asunto = sanitizeInput($_POST['asunto']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    
    if (empty($destinatario_email) || empty($asunto) || empty($mensaje)) {
        $error = 'Completa todos los campos';
    } elseif (!validateEmail($destinatario_email)) {
        $error = 'Por favor, ingresa un email válido';
    } else {
        try {
            // Buscar usuario por email
            $buscarQuery = "SELECT id, activo FROM usuarios WHERE email = ?";
            $buscarStmt = $db->prepare($buscarQuery);
            $buscarStmt->execute([$destinatario_email]);
            $destinatario = $buscarStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$destinatario) {
                $error = 'No existe ningún usuario con ese email';
            } elseif (!$destinatario['activo']) {
                $error = 'El usuario no está activo en el sistema';
            } elseif ($destinatario['id'] == $user_id) {
                $error = 'No puedes enviarte mensajes a ti mismo';
            } else {
                // Enviar mensaje
                $insertQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje)
                               VALUES (?, ?, ?, ?)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$user_id, $destinatario['id'], $asunto, $mensaje]);
                
                showAlert('Mensaje enviado correctamente', 'success');
                redirect('bandeja.php');
            }
            
        } catch (Exception $e) {
            $error = 'Error al enviar mensaje: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><i class="fas fa-mountain"></i> ActividadesConnect</h2>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link">Mi Panel</a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link">Cerrar Sesión</a>
                    </li>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="bandeja.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a mensajes
                </a>
            </div>

            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-paper-plane"></i> Nuevo Mensaje</h1>
                        <p>Envía un mensaje a otro usuario</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" id="mensajeForm">
                            <div class="form-group">
                                <label for="destinatario_email">
                                    <i class="fas fa-user"></i> Email del Destinatario: *
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="destinatario_email" 
                                       name="destinatario_email" 
                                       value="<?php echo $destinatario_info ? htmlspecialchars($destinatario_info['email']) : ''; ?>"
                                       placeholder="ejemplo@email.com"
                                       <?php echo $destinatario_info ? 'readonly style="background: #f8f9fa;"' : ''; ?>
                                       required>
                                <?php if ($destinatario_info): ?>
                                    <div style="margin-top: 0.5rem; padding: 0.75rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">
                                        <i class="fas fa-check-circle"></i>
                                        <strong>Destinatario:</strong> 
                                        <?php echo htmlspecialchars($destinatario_info['nombre'] . ' ' . $destinatario_info['apellidos']); ?>
                                        (<?php echo ucfirst($destinatario_info['tipo']); ?>)
                                    </div>
                                <?php else: ?>
                                    <small style="color: #6c757d;">
                                        Ingresa el email del usuario al que quieres escribir
                                    </small>
                                    <div id="email-validation-message" style="margin-top: 0.5rem; display: none;"></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="asunto">
                                    <i class="fas fa-heading"></i> Asunto: *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="asunto" 
                                       name="asunto" 
                                       placeholder="Asunto del mensaje"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="mensaje">
                                    <i class="fas fa-comment"></i> Mensaje: *
                                </label>
                                <textarea class="form-control" 
                                          id="mensaje" 
                                          name="mensaje" 
                                          rows="10"
                                          placeholder="Escribe tu mensaje aquí..."
                                          required></textarea>
                            </div>

                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-paper-plane"></i> Enviar Mensaje
                                </button>
                                <a href="bandeja.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>ActividadesConnect</h3>
                    <p>Conectando aventuras desde 2024</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 ActividadesConnect. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
    <script>
        // Validación en tiempo real del email del destinatario
        <?php if (!$destinatario_info): ?>
        const emailInput = document.getElementById('destinatario_email');
        const validationMessage = document.getElementById('email-validation-message');
        let validationTimeout;

        emailInput.addEventListener('input', function() {
            clearTimeout(validationTimeout);
            const email = this.value.trim();
            
            if (email.length < 3) {
                validationMessage.style.display = 'none';
                return;
            }

            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                validationMessage.style.display = 'block';
                validationMessage.className = 'alert alert-warning';
                validationMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Formato de email inválido';
                return;
            }

            // Verificar si el usuario existe (con debounce)
            validationTimeout = setTimeout(function() {
                validationMessage.style.display = 'block';
                validationMessage.className = 'alert alert-info';
                validationMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

                // Hacer petición AJAX
                fetch('verificar-email.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            validationMessage.className = 'alert alert-success';
                            validationMessage.innerHTML = '<i class="fas fa-check-circle"></i> Usuario encontrado: <strong>' + data.nombre + '</strong> (' + data.tipo + ')';
                        } else {
                            validationMessage.className = 'alert alert-danger';
                            validationMessage.innerHTML = '<i class="fas fa-times-circle"></i> No existe ningún usuario con este email';
                        }
                    })
                    .catch(error => {
                        validationMessage.style.display = 'none';
                    });
            }, 500); // Espera 500ms después de dejar de escribir
        });
        <?php endif; ?>
    </script>
</body>
</html>
