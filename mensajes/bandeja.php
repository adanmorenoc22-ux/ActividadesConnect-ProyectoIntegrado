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

// Obtener la sección activa (por defecto: entrada)
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'entrada';
$secciones_validas = ['entrada', 'enviados', 'archivados', 'papelera'];
if (!in_array($seccion, $secciones_validas)) {
    $seccion = 'entrada';
}

try {
    $mensajes = [];
    $noLeidos = 0;
    
    switch ($seccion) {
        case 'entrada':
            // Mensajes recibidos (no archivados, no eliminados)
            $query = "SELECT m.*, u.nombre as remitente_nombre, u.apellidos as remitente_apellidos
                      FROM mensajes m
                      JOIN usuarios u ON m.remitente_id = u.id
                      WHERE m.destinatario_id = ? 
                      AND m.eliminado_destinatario = 0 
                      AND m.archivado_destinatario = 0
                      ORDER BY m.fecha_envio DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar no leídos
            $noLeidosQuery = "SELECT COUNT(*) as total FROM mensajes 
                             WHERE destinatario_id = ? AND leido = 0 
                             AND eliminado_destinatario = 0 AND archivado_destinatario = 0";
            $noLeidosStmt = $db->prepare($noLeidosQuery);
            $noLeidosStmt->execute([$user_id]);
            $noLeidos = $noLeidosStmt->fetch(PDO::FETCH_ASSOC)['total'];
            break;
            
        case 'enviados':
            // Mensajes enviados (no archivados, no eliminados)
            $query = "SELECT m.*, u.nombre as destinatario_nombre, u.apellidos as destinatario_apellidos
                      FROM mensajes m
                      JOIN usuarios u ON m.destinatario_id = u.id
                      WHERE m.remitente_id = ? 
                      AND m.eliminado_remitente = 0 
                      AND m.archivado_remitente = 0
                      ORDER BY m.fecha_envio DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'archivados':
            // Mensajes archivados (recibidos o enviados)
            $query = "SELECT m.*, 
                      ur.nombre as remitente_nombre, ur.apellidos as remitente_apellidos,
                      ud.nombre as destinatario_nombre, ud.apellidos as destinatario_apellidos
                      FROM mensajes m
                      JOIN usuarios ur ON m.remitente_id = ur.id
                      JOIN usuarios ud ON m.destinatario_id = ud.id
                      WHERE ((m.destinatario_id = ? AND m.archivado_destinatario = 1 AND m.eliminado_destinatario = 0)
                             OR (m.remitente_id = ? AND m.archivado_remitente = 1 AND m.eliminado_remitente = 0))
                      ORDER BY m.fecha_envio DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $user_id]);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'papelera':
            // Mensajes eliminados (recibidos o enviados)
            $query = "SELECT m.*, 
                      ur.nombre as remitente_nombre, ur.apellidos as remitente_apellidos,
                      ud.nombre as destinatario_nombre, ud.apellidos as destinatario_apellidos
                      FROM mensajes m
                      JOIN usuarios ur ON m.remitente_id = ur.id
                      JOIN usuarios ud ON m.destinatario_id = ud.id
                      WHERE ((m.destinatario_id = ? AND m.eliminado_destinatario = 1)
                             OR (m.remitente_id = ? AND m.eliminado_remitente = 1))
                      ORDER BY m.fecha_envio DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $user_id]);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar mensajes';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap;
        }
        .tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }
        .tab:hover {
            color: #667eea;
            background: #f8f9fa;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }
        .tab i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <a href="../dashboard.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver al panel
                </a>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1><i class="fas fa-envelope"></i> Mis Mensajes</h1>
                    <?php if ($seccion === 'entrada'): ?>
                        <p style="color: #6c757d;">
                            <?php echo $noLeidos; ?> mensaje(s) sin leer
                        </p>
                    <?php endif; ?>
                </div>
                <a href="nuevo.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Mensaje
                </a>
            </div>

            <?php displayAlert(); ?>

            <!-- Pestañas -->
            <div class="tabs">
                <a href="?seccion=entrada" class="tab <?php echo $seccion === 'entrada' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i> Entrada
                    <?php if ($seccion === 'entrada' && $noLeidos > 0): ?>
                        <span style="background: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.8rem; margin-left: 0.5rem;">
                            <?php echo $noLeidos; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?seccion=enviados" class="tab <?php echo $seccion === 'enviados' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i> Enviados
                </a>
                <a href="?seccion=archivados" class="tab <?php echo $seccion === 'archivados' ? 'active' : ''; ?>">
                    <i class="fas fa-archive"></i> Archivados
                </a>
                <a href="?seccion=papelera" class="tab <?php echo $seccion === 'papelera' ? 'active' : ''; ?>">
                    <i class="fas fa-trash"></i> Papelera
                </a>
            </div>

            <?php if (empty($mensajes)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No hay mensajes</h3>
                    <p style="color: #6c757d;">
                        <?php 
                        switch ($seccion) {
                            case 'entrada':
                                echo 'Tu bandeja de entrada está vacía';
                                break;
                            case 'enviados':
                                echo 'No has enviado ningún mensaje';
                                break;
                            case 'archivados':
                                echo 'No tienes mensajes archivados';
                                break;
                            case 'papelera':
                                echo 'Tu papelera está vacía';
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <?php 
                            // Determinar si el usuario es remitente o destinatario
                            $es_remitente = ($mensaje['remitente_id'] == $user_id);
                            $nombre_mostrar = $es_remitente 
                                ? ($mensaje['destinatario_nombre'] . ' ' . $mensaje['destinatario_apellidos'])
                                : ($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellidos']);
                            $etiqueta = $es_remitente ? 'Para:' : 'De:';
                            ?>
                            <div class="mensaje-item" style="padding: 1.5rem; border-bottom: 1px solid #e9ecef; <?php echo (!$es_remitente && !$mensaje['leido']) ? 'background: #f0f4ff;' : ''; ?> position: relative;" 
                                 data-mensaje-id="<?php echo $mensaje['id']; ?>"
                                 data-es-remitente="<?php echo $es_remitente ? '1' : '0'; ?>">
                                
                                <!-- Botones de acción -->
                                <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 0.5rem; z-index: 10;">
                                    <?php if ($seccion !== 'papelera'): ?>
                                        <?php if ($seccion !== 'archivados'): ?>
                                            <button class="btn-archivar-mensaje" 
                                                    style="background: #f39c12; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;"
                                                    onclick="archivarMensaje(<?php echo $mensaje['id']; ?>, <?php echo $es_remitente ? '1' : '0'; ?>); event.stopPropagation();"
                                                    title="Archivar">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-desarchivar-mensaje" 
                                                    style="background: #3498db; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;"
                                                    onclick="desarchivarMensaje(<?php echo $mensaje['id']; ?>, <?php echo $es_remitente ? '1' : '0'; ?>); event.stopPropagation();"
                                                    title="Desarchivar">
                                                <i class="fas fa-inbox"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($seccion === 'papelera'): ?>
                                        <button class="btn-restaurar-mensaje" 
                                                style="background: #2ecc71; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;"
                                                onclick="restaurarMensaje(<?php echo $mensaje['id']; ?>, <?php echo $es_remitente ? '1' : '0'; ?>); event.stopPropagation();"
                                                title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-eliminar-mensaje" 
                                            style="background: #e74c3c; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;"
                                            onclick="eliminarMensaje(<?php echo $mensaje['id']; ?>, <?php echo $es_remitente ? '1' : '0'; ?>, <?php echo $seccion === 'papelera' ? '1' : '0'; ?>); event.stopPropagation();"
                                            title="<?php echo $seccion === 'papelera' ? 'Eliminar permanentemente' : 'Mover a papelera'; ?>">
                                        <i class="fas fa-<?php echo $seccion === 'papelera' ? 'trash-alt' : 'times'; ?>"></i>
                                    </button>
                                </div>
                                
                                <!-- Contenido del mensaje (clickeable) -->
                                <div style="cursor: pointer; padding-right: 100px;" onclick="window.location='ver.php?id=<?php echo $mensaje['id']; ?>'">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <div style="flex: 1;">
                                            <strong style="color: #2c3e50; font-size: 1.1rem;">
                                                <?php if (!$es_remitente && !$mensaje['leido']): ?>
                                                    <i class="fas fa-envelope" style="color: #667eea;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-envelope-open" style="color: #6c757d;"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($mensaje['asunto']); ?>
                                            </strong>
                                            <div style="color: #6c757d; font-size: 0.9rem; margin-top: 0.25rem;">
                                                <?php echo $etiqueta; ?> <?php echo htmlspecialchars($nombre_mostrar); ?>
                                            </div>
                                        </div>
                                        <div style="color: #6c757d; font-size: 0.85rem; text-align: right;">
                                            <?php echo formatDateTime($mensaje['fecha_envio']); ?>
                                        </div>
                                    </div>
                                    <p style="color: #6c757d; margin: 0;">
                                        <?php echo htmlspecialchars(substr($mensaje['mensaje'], 0, 100)) . (strlen($mensaje['mensaje']) > 100 ? '...' : ''); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        function eliminarMensaje(mensajeId, esRemitente, esPapelera) {
            const accion = esPapelera ? 'eliminar permanentemente' : 'mover a papelera';
            if (!confirm(`¿Estás seguro de que quieres ${accion} este mensaje?`)) {
                return;
            }

            const mensajeElement = document.querySelector(`[data-mensaje-id="${mensajeId}"]`);
            if (!mensajeElement) {
                console.error('No se encontró el elemento del mensaje');
                return;
            }

            mensajeElement.style.transition = 'opacity 0.3s ease-out';
            mensajeElement.style.opacity = '0';

            fetch('eliminar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mensaje_id=${mensajeId}&es_remitente=${esRemitente}&eliminar_permanente=${esPapelera}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        mensajeElement.remove();
                        const mensajesRestantes = document.querySelectorAll('.mensaje-item');
                        if (mensajesRestantes.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                } else {
                    mensajeElement.style.opacity = '1';
                    alert('Error: ' + (data.message || 'No se pudo eliminar el mensaje'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mensajeElement.style.opacity = '1';
                alert('Error al eliminar el mensaje. Inténtalo de nuevo.');
            });
        }

        function archivarMensaje(mensajeId, esRemitente) {
            const mensajeElement = document.querySelector(`[data-mensaje-id="${mensajeId}"]`);
            if (!mensajeElement) return;

            mensajeElement.style.transition = 'opacity 0.3s ease-out';
            mensajeElement.style.opacity = '0';

            fetch('archivar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mensaje_id=${mensajeId}&es_remitente=${esRemitente}&archivar=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        mensajeElement.remove();
                        const mensajesRestantes = document.querySelectorAll('.mensaje-item');
                        if (mensajesRestantes.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                } else {
                    mensajeElement.style.opacity = '1';
                    alert('Error: ' + (data.message || 'No se pudo archivar el mensaje'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mensajeElement.style.opacity = '1';
                alert('Error al archivar el mensaje. Inténtalo de nuevo.');
            });
        }

        function desarchivarMensaje(mensajeId, esRemitente) {
            const mensajeElement = document.querySelector(`[data-mensaje-id="${mensajeId}"]`);
            if (!mensajeElement) return;

            mensajeElement.style.transition = 'opacity 0.3s ease-out';
            mensajeElement.style.opacity = '0';

            fetch('archivar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mensaje_id=${mensajeId}&es_remitente=${esRemitente}&archivar=0`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        mensajeElement.remove();
                        const mensajesRestantes = document.querySelectorAll('.mensaje-item');
                        if (mensajesRestantes.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                } else {
                    mensajeElement.style.opacity = '1';
                    alert('Error: ' + (data.message || 'No se pudo desarchivar el mensaje'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mensajeElement.style.opacity = '1';
                alert('Error al desarchivar el mensaje. Inténtalo de nuevo.');
            });
        }

        function restaurarMensaje(mensajeId, esRemitente) {
            const mensajeElement = document.querySelector(`[data-mensaje-id="${mensajeId}"]`);
            if (!mensajeElement) return;

            mensajeElement.style.transition = 'opacity 0.3s ease-out';
            mensajeElement.style.opacity = '0';

            fetch('restaurar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mensaje_id=${mensajeId}&es_remitente=${esRemitente}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        mensajeElement.remove();
                        const mensajesRestantes = document.querySelectorAll('.mensaje-item');
                        if (mensajesRestantes.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                } else {
                    mensajeElement.style.opacity = '1';
                    alert('Error: ' + (data.message || 'No se pudo restaurar el mensaje'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mensajeElement.style.opacity = '1';
                alert('Error al restaurar el mensaje. Inténtalo de nuevo.');
            });
        }
    </script>
</body>
</html>
