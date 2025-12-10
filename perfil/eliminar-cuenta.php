<?php
// Iniciar output buffering al principio para evitar problemas con headers
ob_start();
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    ob_end_clean();
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Verificar que la conexión a la base de datos funcionó
if (!$db) {
    die('Error: No se pudo conectar a la base de datos. Por favor, verifica la configuración.');
}

// Aumentar timeout para operaciones largas
@$db->exec("SET SESSION wait_timeout = 300");
@$db->exec("SET SESSION interactive_timeout = 300");

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$error = '';
$success = '';

// Procesar eliminación de cuenta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_cuenta']) && isset($_POST['confirmar_eliminacion'])) {
    // Activar mostrar errores temporalmente para debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    // Debug temporal - descomentar para ver qué está pasando:
    // error_reporting(E_ALL);
    // ini_set('display_errors', 1);
    
    // Verificar si el checkbox está marcado
    $confirmar = isset($_POST['confirmar']) && $_POST['confirmar'] === 'si';
    
    if (!$confirmar) {
        $error = 'DEBES MARCAR LA CASILLA DE VERIFICACIÓN para confirmar que entiendes las consecuencias de eliminar tu cuenta.';
    } else {
        try {
            // Verificar que el usuario existe
            $verificarQuery = "SELECT id, email, nombre, apellidos FROM usuarios WHERE id = ?";
            $verificarStmt = $db->prepare($verificarQuery);
            $verificarStmt->execute([$user_id]);
            $usuario = $verificarStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                $error = 'Usuario no encontrado';
            } else {
                // Verificar que la conexión sigue activa
                if (!$db) {
                    throw new Exception('Error: La conexión a la base de datos se perdió.');
                }
                
                // ESTRATEGIA SIMPLIFICADA: Intentar eliminar directamente primero
                // Si CASCADE está configurado, esto funcionará inmediatamente
                @$db->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Intentar eliminar directamente el usuario primero
                $deleteQueryDirect = "DELETE FROM usuarios WHERE id = ?";
                $deleteStmtDirect = $db->prepare($deleteQueryDirect);
                $deleteStmtDirect->execute([$user_id]);
                $rowsDeletedDirect = $deleteStmtDirect->rowCount();
                
                if ($rowsDeletedDirect > 0) {
                    // ¡Funcionó! El CASCADE eliminó todo automáticamente
                    @$db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $db = null;
                    
                    // Limpiar sesión y redirigir
                    $_SESSION = array();
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        @setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    @session_destroy();
                    
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    header("Location: ../index.php?deleted=1", true, 302);
                    exit();
                }
                
                // Si llegamos aquí, CASCADE no funcionó, hacer eliminación manual
                
                // Eliminar mensajes relacionados primero
                try {
                    $deleteMensajesQuery = "DELETE FROM mensajes WHERE remitente_id = ? OR destinatario_id = ?";
                    $deleteMensajesStmt = $db->prepare($deleteMensajesQuery);
                    $deleteMensajesStmt->execute([$user_id, $user_id]);
                } catch (Exception $e) {
                    // Continuar aunque falle, puede que no haya mensajes
                }
                
                // Obtener IDs de ofertante/consumidor si existen
                $ofertante_id = null;
                $consumidor_id = null;
                
                if ($user_type === 'ofertante') {
                    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
                    $ofertanteStmt = $db->prepare($ofertanteQuery);
                    $ofertanteStmt->execute([$user_id]);
                    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
                    if ($ofertante) {
                        $ofertante_id = (int)$ofertante['id'];
                        
                        // Eliminar actividades y todo lo relacionado
                        $actividadesQuery = "SELECT id FROM actividades WHERE ofertante_id = ?";
                        $actividadesStmt = $db->prepare($actividadesQuery);
                        $actividadesStmt->execute([$ofertante_id]);
                        $actividades = $actividadesStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($actividades as $actividad) {
                            $actividad_id = (int)$actividad['id'];
                            
                            // Eliminar participantes - primero obtener los IDs de reservas
                            try {
                                $reservasIdsQuery = "SELECT id FROM reservas WHERE actividad_id = ?";
                                $reservasIdsStmt = $db->prepare($reservasIdsQuery);
                                $reservasIdsStmt->execute([$actividad_id]);
                                $reservasIds = $reservasIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                if (!empty($reservasIds)) {
                                    $placeholders = implode(',', array_fill(0, count($reservasIds), '?'));
                                    $delPartStmt = $db->prepare("DELETE FROM participantes_reservas WHERE reserva_id IN ($placeholders)");
                                    $delPartStmt->execute($reservasIds);
                                }
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                            
                            // Eliminar reservas
                            try {
                                $delResStmt = $db->prepare("DELETE FROM reservas WHERE actividad_id = ?");
                                $delResStmt->execute([$actividad_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                            
                            // Eliminar disponibilidades
                            try {
                                $delDispStmt = $db->prepare("DELETE FROM disponibilidad_actividades WHERE actividad_id = ?");
                                $delDispStmt->execute([$actividad_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                            
                            // Eliminar imágenes
                            try {
                                $delImgStmt = $db->prepare("DELETE FROM imagenes WHERE actividad_id = ?");
                                $delImgStmt->execute([$actividad_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                        }
                        
                        // Eliminar intereses y propuestas
                        try {
                            $delInteresesStmt = $db->prepare("DELETE FROM intereses_ofertantes WHERE ofertante_id = ?");
                            $delInteresesStmt->execute([$ofertante_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                        
                        try {
                            $delPropuestasStmt = $db->prepare("DELETE FROM propuestas_ofertantes WHERE ofertante_id = ?");
                            $delPropuestasStmt->execute([$ofertante_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                        
                        // Eliminar actividades
                        try {
                            $delActStmt = $db->prepare("DELETE FROM actividades WHERE ofertante_id = ?");
                            $delActStmt->execute([$ofertante_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                        
                        // Eliminar ofertante
                        try {
                            $delOfertanteStmt = $db->prepare("DELETE FROM ofertantes WHERE id = ?");
                            $delOfertanteStmt->execute([$ofertante_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                    }
                } elseif ($user_type === 'consumidor') {
                    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
                    $consumidorStmt = $db->prepare($consumidorQuery);
                    $consumidorStmt->execute([$user_id]);
                    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
                    if ($consumidor) {
                        $consumidor_id = (int)$consumidor['id'];
                        
                        // Eliminar participantes de reservas
                        $reservasQuery = "SELECT id FROM reservas WHERE consumidor_id = ?";
                        $reservasStmt = $db->prepare($reservasQuery);
                        $reservasStmt->execute([$consumidor_id]);
                        $reservas = $reservasStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($reservas as $reserva) {
                            $reserva_id = (int)$reserva['id'];
                            try {
                                $delPartStmt = $db->prepare("DELETE FROM participantes_reservas WHERE reserva_id = ?");
                                $delPartStmt->execute([$reserva_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                        }
                        
                        // Eliminar reservas
                        try {
                            $delReservasStmt = $db->prepare("DELETE FROM reservas WHERE consumidor_id = ?");
                            $delReservasStmt->execute([$consumidor_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                        
                        // Eliminar imágenes de solicitudes
                        $solicitudesQuery = "SELECT id FROM solicitudes_consumidores WHERE consumidor_id = ?";
                        $solicitudesStmt = $db->prepare($solicitudesQuery);
                        $solicitudesStmt->execute([$consumidor_id]);
                        $solicitudes = $solicitudesStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($solicitudes as $solicitud) {
                            $solicitud_id = (int)$solicitud['id'];
                            try {
                                $delImgSolStmt = $db->prepare("DELETE FROM imagenes WHERE solicitud_id = ?");
                                $delImgSolStmt->execute([$solicitud_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                            try {
                                $delIntSolStmt = $db->prepare("DELETE FROM intereses_ofertantes WHERE solicitud_id = ?");
                                $delIntSolStmt->execute([$solicitud_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                            try {
                                $delPropSolStmt = $db->prepare("DELETE FROM propuestas_ofertantes WHERE solicitud_id = ?");
                                $delPropSolStmt->execute([$solicitud_id]);
                            } catch (Exception $e) {
                                // Continuar aunque falle
                            }
                        }
                        
                        // Eliminar solicitudes
                        try {
                            $delSolicitudesStmt = $db->prepare("DELETE FROM solicitudes_consumidores WHERE consumidor_id = ?");
                            $delSolicitudesStmt->execute([$consumidor_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                        
                        // Eliminar consumidor
                        try {
                            $delConsumidorStmt = $db->prepare("DELETE FROM consumidores WHERE id = ?");
                            $delConsumidorStmt->execute([$consumidor_id]);
                        } catch (Exception $e) {
                            // Continuar aunque falle
                        }
                    }
                }
                
                // Finalmente, eliminar el usuario
                $deleteQuery = "DELETE FROM usuarios WHERE id = ?";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute([$user_id]);
                
                // Verificar que se eliminó
                if ($deleteStmt->rowCount() === 0) {
                    throw new Exception('No se pudo eliminar el usuario. Verifica que el ID sea correcto.');
                }
                
                // Reactivar las verificaciones de foreign keys
                @$db->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Cerrar conexión a la base de datos
                $db = null;
                
                // Limpiar todas las variables de sesión ANTES de destruir
                $_SESSION = array();
                
                // Destruir la cookie de sesión si existe
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    @setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                
                // Guardar mensaje en cookie antes de destruir sesión
                // El mensaje durará 7 días o hasta que se visite index.php
                setcookie('account_deleted_message', '1', time() + (7 * 24 * 60 * 60), '/');
                
                // Destruir la sesión
                @session_destroy();
                
                // Asegurar que no hay output antes del header
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Redirigir a página principal con mensaje
                header("Location: ../index.php?deleted=1", true, 302);
                exit();
            }
        } catch (PDOException $e) {
            // Reactivar las verificaciones en caso de error
            if ($db) {
                try {
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                } catch (Exception $e2) {
                    // Ignorar
                }
            }
            $error = 'Error de base de datos: ' . $e->getMessage() . ' (Código: ' . $e->getCode() . ')';
        } catch (Exception $e) {
            // Reactivar las verificaciones en caso de error
            if ($db) {
                try {
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                } catch (Exception $e2) {
                    // Ignorar
                }
            }
            $error = 'Error al eliminar la cuenta: ' . $e->getMessage();
        }
    }
}

// Obtener información del usuario para mostrar
try {
    $userQuery = "SELECT u.*, 
                  CASE 
                      WHEN u.tipo = 'ofertante' THEN (SELECT COUNT(*) FROM actividades WHERE ofertante_id = (SELECT id FROM ofertantes WHERE usuario_id = u.id))
                      WHEN u.tipo = 'consumidor' THEN (SELECT COUNT(*) FROM reservas WHERE consumidor_id = (SELECT id FROM consumidores WHERE usuario_id = u.id))
                      ELSE 0
                  END as total_registros
                  FROM usuarios u
                  WHERE u.id = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$user_id]);
    $usuario = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        showAlert('Usuario no encontrado', 'danger');
        redirect('../dashboard.php');
    }
} catch (Exception $e) {
    $error = 'Error al cargar información del usuario';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Cuenta - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="ver.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a Mi Perfil
                </a>
            </div>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                    <h1 style="margin: 0; color: white;">
                        <i class="fas fa-exclamation-triangle"></i> Eliminar Cuenta
                    </h1>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                            <i class="fas fa-times-circle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php displayAlert(); ?>

                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1.5rem; margin-bottom: 2rem; border-radius: 6px;">
                        <h3 style="margin: 0 0 1rem 0; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> Advertencia Importante
                        </h3>
                        <p style="margin: 0 0 1rem 0; color: #856404; line-height: 1.6;">
                            <strong>Esta acción es irreversible.</strong> Al eliminar tu cuenta:
                        </p>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #856404; line-height: 1.8;">
                            <li>Se eliminará permanentemente toda tu información personal</li>
                            <li>Se eliminarán todos tus datos de perfil (ofertante o consumidor)</li>
                            <?php if ($user_type === 'ofertante'): ?>
                                <li>Se eliminarán todas tus actividades y disponibilidades</li>
                                <li>Se cancelarán todas las reservas pendientes de tus actividades</li>
                            <?php elseif ($user_type === 'consumidor'): ?>
                                <li>Se eliminarán todas tus reservas pendientes</li>
                                <li>Se eliminarán todas tus solicitudes</li>
                            <?php endif; ?>
                            <li>Se eliminarán todos tus mensajes</li>
                            <li>No podrás recuperar esta cuenta después de eliminarla</li>
                        </ul>
                    </div>

                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #495057;">
                            <i class="fas fa-info-circle"></i> Información de tu cuenta
                        </h4>
                        <p style="margin: 0.5rem 0;"><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Tipo de cuenta:</strong> <?php echo ucfirst($usuario['tipo']); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Fecha de registro:</strong> <?php echo formatDateTime($usuario['fecha_registro']); ?></p>
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="formEliminarCuenta">
                        <input type="hidden" name="eliminar_cuenta" value="1">
                        
                        <div style="background: #e7f3ff; border: 2px solid #0066cc; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
                            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                <input type="checkbox" 
                                       id="confirmar" 
                                       name="confirmar" 
                                       value="si" 
                                       required
                                       style="margin-top: 0.25rem; width: 20px; height: 20px; cursor: pointer;">
                                <label for="confirmar" style="cursor: pointer; color: #0066cc; font-weight: 600; line-height: 1.6;">
                                    Entiendo las consecuencias y deseo eliminar permanentemente mi cuenta. 
                                    Esta acción no se puede deshacer.
                                </label>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button type="submit" 
                                    name="confirmar_eliminacion" 
                                    value="si"
                                    id="btnEliminar"
                                    class="btn" 
                                    style="background: #e74c3c; color: white; flex: 1; min-width: 200px; padding: 0.75rem 1.5rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;">
                                <i class="fas fa-trash-alt"></i> Eliminar Cuenta Permanentemente
                            </button>
                            <a href="ver.php" 
                               class="btn" 
                               style="background: #6c757d; color: white; flex: 1; min-width: 200px; padding: 0.75rem 1.5rem; font-weight: 600; text-decoration: none; text-align: center; border-radius: 6px; transition: all 0.3s ease;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

