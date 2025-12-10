<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$database = new Database();
$db = $database->getConnection();

try {
    // Obtener datos del usuario
    $userQuery = "SELECT * FROM usuarios WHERE id = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$user_id]);
    $usuario = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        showAlert('Usuario no encontrado', 'danger');
        redirect('../dashboard.php');
    }
    
    // Obtener datos específicos según el tipo de usuario
    if ($user_type === 'ofertante') {
        $ofertanteQuery = "SELECT * FROM ofertantes WHERE usuario_id = ?";
        $ofertanteStmt = $db->prepare($ofertanteQuery);
        $ofertanteStmt->execute([$user_id]);
        $perfil = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas del ofertante
        $statsQuery = "SELECT 
            COUNT(DISTINCT a.id) as total_actividades,
            COUNT(DISTINCT r.id) as total_reservas
            FROM ofertantes o
            LEFT JOIN actividades a ON o.id = a.ofertante_id
            LEFT JOIN reservas r ON a.id = r.actividad_id
            WHERE o.id = ?";
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute([$perfil['id']]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
    } elseif ($user_type === 'consumidor') {
        $consumidorQuery = "SELECT * FROM consumidores WHERE usuario_id = ?";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$user_id]);
        $perfil = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas del consumidor
        $statsQuery = "SELECT 
            COUNT(DISTINCT s.id) as total_solicitudes,
            COUNT(DISTINCT r.id) as total_reservas
            FROM consumidores c
            LEFT JOIN solicitudes_consumidores s ON c.id = s.consumidor_id
            LEFT JOIN reservas r ON c.id = r.consumidor_id
            WHERE c.id = ?";
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute([$perfil['id']]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar el perfil';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Navegación -->
            <div style="margin-bottom: 2rem;">
                <a href="../dashboard.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>

            <!-- Información del perfil -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <h1 style="margin: 0; color: white;">
                            <i class="fas fa-user-circle"></i> Mi Perfil
                        </h1>
                        <a href="editar.php" class="btn" style="background: white; color: #667eea; border: 2px solid white; padding: 0.5rem 1.5rem; font-weight: 600; text-decoration: none; border-radius: 6px; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                        <!-- Columna: datos personales -->
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #667eea;">
                            <h3 style="margin: 0 0 1.5rem 0; color: #495057; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                                <i class="fas fa-user" style="color: #667eea;"></i> Datos personales
                            </h3>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-id-card" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Nombre completo</div>
                                        <div style="font-weight: 600; color: #343a40;"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-envelope" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Email</div>
                                        <div style="color: #343a40;"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($usuario['telefono'])): ?>
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-phone" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Teléfono</div>
                                        <div style="color: #343a40;"><?php echo htmlspecialchars($usuario['telefono']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-birthday-cake" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Fecha de nacimiento</div>
                                        <div style="color: #343a40;"><?php echo formatDate($usuario['fecha_nacimiento']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-user-tag" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Tipo de usuario</div>
                                        <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.9rem; display: inline-block;">
                                            <?php echo ucfirst($user_type); ?>
                                        </span>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border-left: 3px solid #667eea;">
                                    <i class="fas fa-calendar-check" style="color: #667eea; margin-top: 0.2rem; min-width: 20px;"></i>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">Fecha de registro</div>
                                        <div style="color: #343a40;"><?php echo formatDate($usuario['fecha_registro']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna: datos profesionales / preferencias -->
                        <?php if ($user_type === 'ofertante' && $perfil): ?>
                            <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #1976d2; text-align: left;">
                                <h3 style="margin: 0 0 1.5rem 0; color: #1976d2; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                                    <i class="fas fa-briefcase" style="color: #1976d2;"></i> Datos profesionales
                                </h3>
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <?php if (!empty($perfil['descripcion'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #1976d2; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-align-left" style="color: #1976d2;"></i>
                                            <strong style="color: #1976d2;">Descripción</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['descripcion']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['experiencia'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #1976d2; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-award" style="color: #1976d2;"></i>
                                            <strong style="color: #1976d2;">Experiencia</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['experiencia']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['certificaciones'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #1976d2; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-certificate" style="color: #1976d2;"></i>
                                            <strong style="color: #1976d2;">Certificaciones y títulos</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['certificaciones']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['especialidades'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #1976d2; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-star" style="color: #1976d2;"></i>
                                            <strong style="color: #1976d2;">Especialidades</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; text-align: left;"><?php echo htmlspecialchars($perfil['especialidades']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['disponibilidad_general'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #1976d2; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-clock" style="color: #1976d2;"></i>
                                            <strong style="color: #1976d2;">Disponibilidad general</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['disponibilidad_general']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($user_type === 'consumidor' && $perfil): ?>
                            <div style="background: #e8f5e9; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #2e7d32; text-align: left;">
                                <h3 style="margin: 0 0 1.5rem 0; color: #2e7d32; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                                    <i class="fas fa-heart" style="color: #2e7d32;"></i> Preferencias y datos adicionales
                                </h3>
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <?php if (!empty($perfil['preferencias'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #2e7d32; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-star" style="color: #2e7d32;"></i>
                                            <strong style="color: #2e7d32;">Preferencias de actividades</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['preferencias']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['nivel_experiencia'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #2e7d32; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-signal" style="color: #2e7d32;"></i>
                                            <strong style="color: #2e7d32;">Nivel de experiencia</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; text-align: left;"><span style="background: #2e7d32; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.9rem; display: inline-block;"><?php echo htmlspecialchars(ucfirst($perfil['nivel_experiencia'])); ?></span></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['restricciones_medicas'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #2e7d32; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-heartbeat" style="color: #2e7d32;"></i>
                                            <strong style="color: #2e7d32;">Restricciones médicas</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['restricciones_medicas']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($perfil['alergias'])): ?>
                                    <div style="padding: 1rem; background: white; border-radius: 6px; border-left: 3px solid #2e7d32; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-allergies" style="color: #2e7d32;"></i>
                                            <strong style="color: #2e7d32;">Alergias</strong>
                                        </div>
                                        <p style="margin: 0; padding: 0; line-height: 1.7; color: #495057; white-space: pre-wrap; text-align: left;"><?php echo htmlspecialchars($perfil['alergias']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Sección de eliminación de cuenta -->
            <div class="card" style="margin-top: 2rem; border: 2px solid #e74c3c;">
                <div class="card-header" style="background: #f8f9fa; border-bottom: 2px solid #e74c3c;">
                    <h3 style="margin: 0; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle"></i> Zona de Peligro
                    </h3>
                </div>
                <div class="card-body">
                    <p style="color: #6c757d; margin-bottom: 1rem;">
                        Si ya no deseas usar tu cuenta, puedes eliminarla permanentemente. 
                        <strong>Esta acción no se puede deshacer.</strong>
                    </p>
                    <a href="eliminar-cuenta.php" 
                       class="btn" 
                       style="background: #e74c3c; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; display: inline-block; transition: all 0.3s ease;"
                       onclick="return confirm('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción es irreversible.');">
                        <i class="fas fa-trash-alt"></i> Eliminar Mi Cuenta
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
