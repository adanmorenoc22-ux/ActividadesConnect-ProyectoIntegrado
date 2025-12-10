<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Obtener estadísticas según el tipo de usuario
$stats = [];

try {
    if ($user_type === 'ofertante') {
        // Obtener datos del ofertante
        $ofertanteQuery = "SELECT o.*, u.nombre, u.apellidos, u.email FROM ofertantes o 
                          JOIN usuarios u ON o.usuario_id = u.id 
                          WHERE o.usuario_id = ?";
        $ofertanteStmt = $db->prepare($ofertanteQuery);
        $ofertanteStmt->execute([$user_id]);
        $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas del ofertante
        $actividadesQuery = "SELECT COUNT(*) as total FROM actividades WHERE ofertante_id = ?";
        $actividadesStmt = $db->prepare($actividadesQuery);
        $actividadesStmt->execute([$ofertante['id']]);
        $stats['actividades'] = $actividadesStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $reservasQuery = "SELECT COUNT(*) as total FROM reservas r 
                         JOIN actividades a ON r.actividad_id = a.id 
                         WHERE a.ofertante_id = ?";
        $reservasStmt = $db->prepare($reservasQuery);
        $reservasStmt->execute([$ofertante['id']]);
        $stats['reservas'] = $reservasStmt->fetch(PDO::FETCH_ASSOC)['total'];
        

        // Mensajes recientes recibidos
        $mensajesQuery = "SELECT m.id, m.asunto, m.mensaje, m.fecha_envio,
                                 u.nombre, u.apellidos
                          FROM mensajes m
                          JOIN usuarios u ON m.remitente_id = u.id
                          WHERE m.destinatario_id = ?
                          ORDER BY m.fecha_envio DESC
                          LIMIT 5";
        $mensajesStmt = $db->prepare($mensajesQuery);
        $mensajesStmt->execute([$user_id]);
        $mensajes = $mensajesStmt->fetchAll(PDO::FETCH_ASSOC);

        $stats['notificaciones'] = array_map(function ($m) {
            return [
                'tipo' => 'mensaje',
                'item_id' => $m['id'],
                'fecha' => $m['fecha_envio'],
                'titulo' => $m['asunto'],
                'descripcion' => 'De: ' . $m['nombre'] . ' ' . $m['apellidos'],
                'color' => 'info'
            ];
        }, $mensajes);
        
    } elseif ($user_type === 'consumidor') {
        // Obtener datos del consumidor
        $consumidorQuery = "SELECT c.*, u.nombre, u.apellidos, u.email FROM consumidores c 
                          JOIN usuarios u ON c.usuario_id = u.id 
                          WHERE c.usuario_id = ?";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$user_id]);
        $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas del consumidor
        $reservasQuery = "SELECT COUNT(*) as total FROM reservas WHERE consumidor_id = ?";
        $reservasStmt = $db->prepare($reservasQuery);
        $reservasStmt->execute([$consumidor['id']]);
        $stats['reservas'] = $reservasStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $solicitudesQuery = "SELECT COUNT(*) as total FROM solicitudes_consumidores WHERE consumidor_id = ?";
        $solicitudesStmt = $db->prepare($solicitudesQuery);
        $solicitudesStmt->execute([$consumidor['id']]);
        $stats['solicitudes'] = $solicitudesStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Contar intereses nuevos (no vistos) y obtener ID de solicitud con intereses
        $interesesQuery = "SELECT COUNT(DISTINCT i.id) as total, 
                           (SELECT s2.id FROM solicitudes_consumidores s2 
                            JOIN intereses_ofertantes i2 ON s2.id = i2.solicitud_id 
                            WHERE s2.consumidor_id = ? AND i2.estado = 'activo' AND i2.visto = FALSE
                            ORDER BY i2.fecha_interes DESC LIMIT 1) as solicitud_con_intereses
                           FROM intereses_ofertantes i
                           JOIN solicitudes_consumidores s ON i.solicitud_id = s.id
                           WHERE s.consumidor_id = ? AND i.estado = 'activo' AND i.visto = FALSE";
        $interesesStmt = $db->prepare($interesesQuery);
        $interesesStmt->execute([$consumidor['id'], $consumidor['id']]);
        $interesesResult = $interesesStmt->fetch(PDO::FETCH_ASSOC);
        $stats['intereses_nuevos'] = $interesesResult['total'];
        $stats['solicitud_con_intereses'] = $interesesResult['solicitud_con_intereses'];

        // Mensajes recientes recibidos
        $mensajesQuery = "SELECT m.id, m.asunto, m.mensaje, m.fecha_envio,
                                 u.nombre, u.apellidos
                          FROM mensajes m
                          JOIN usuarios u ON m.remitente_id = u.id
                          WHERE m.destinatario_id = ?
                          ORDER BY m.fecha_envio DESC
                          LIMIT 5";
        $mensajesStmt = $db->prepare($mensajesQuery);
        $mensajesStmt->execute([$user_id]);
        $mensajes = $mensajesStmt->fetchAll(PDO::FETCH_ASSOC);

        $stats['notificaciones'] = array_map(function ($m) {
            return [
                'tipo' => 'mensaje',
                'item_id' => $m['id'],
                'fecha' => $m['fecha_envio'],
                'titulo' => $m['asunto'],
                'descripcion' => 'De: ' . $m['nombre'] . ' ' . $m['apellidos'],
                'color' => 'info'
            ];
        }, $mensajes);
        
    } elseif ($user_type === 'admin') {
        // Estadísticas de administrador
        $usuariosQuery = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $usuariosStmt = $db->prepare($usuariosQuery);
        $usuariosStmt->execute();
        $stats['usuarios'] = $usuariosStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $actividadesQuery = "SELECT COUNT(*) as total FROM actividades WHERE estado = 'activa'";
        $actividadesStmt = $db->prepare($actividadesQuery);
        $actividadesStmt->execute();
        $stats['actividades'] = $actividadesStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $reservasQuery = "SELECT COUNT(*) as total FROM reservas";
        $reservasStmt = $db->prepare($reservasQuery);
        $reservasStmt->execute();
        $stats['reservas'] = $reservasStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $ingresosQuery = "SELECT COALESCE(SUM(precio_total), 0) as total FROM reservas WHERE estado = 'completada'";
        $ingresosStmt = $db->prepare($ingresosQuery);
        $ingresosStmt->execute();
        $stats['ingresos'] = $ingresosStmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar las estadísticas';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Bienvenido, <?php echo $_SESSION['user_name']; ?></h1>
                <p>Gestiona tu cuenta y actividades desde aquí</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php displayAlert(); ?>
            
            <!-- Estadísticas -->
            <div class="dashboard-stats">
                <?php if ($user_type === 'ofertante'): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['actividades']; ?></div>
                        <div class="stat-label">Actividades Creadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['reservas']; ?></div>
                        <div class="stat-label">Reservas Recibidas</div>
                    </div>
                <?php elseif ($user_type === 'consumidor'): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['reservas']; ?></div>
                        <div class="stat-label">Reservas Realizadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['solicitudes']; ?></div>
                        <div class="stat-label">Solicitudes Enviadas</div>
                    </div>
                    <div class="stat-card" style="<?php echo $stats['intereses_nuevos'] > 0 ? 'background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;' : ''; ?>">
                        <div class="stat-number" style="<?php echo $stats['intereses_nuevos'] > 0 ? 'color: white;' : ''; ?>">
                            <?php echo $stats['intereses_nuevos']; ?>
                            <?php if ($stats['intereses_nuevos'] > 0): ?>
                                <i class="fas fa-heart" style="font-size: 1.5rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="stat-label" style="<?php echo $stats['intereses_nuevos'] > 0 ? 'color: white;' : ''; ?>">Intereses Recibidos</div>
                    </div>
                <?php elseif ($user_type === 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['usuarios']; ?></div>
                        <div class="stat-label">Usuarios Registrados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['actividades']; ?></div>
                        <div class="stat-label">Actividades Activas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['reservas']; ?></div>
                        <div class="stat-label">Total Reservas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatPrice($stats['ingresos']); ?></div>
                        <div class="stat-label">Ingresos Generados</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sección de Notificaciones Permanentes -->
            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                            <h2 style="margin: 0; color: white;">
                                <i class="fas fa-envelope-open-text"></i> Mensajes Recientes
                            </h2>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (!empty($stats['notificaciones'])): ?>
                                <div class="notification-scroll" style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($stats['notificaciones'] as $index => $notificacion): ?>
                                        <div class="notification-item" style="border-bottom: 1px solid #dee2e6; padding: 1.5rem; <?php echo $index === count($stats['notificaciones']) - 1 ? 'border-bottom: none;' : ''; ?>">
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="notification-dot" style="width: 12px; height: 12px; border-radius: 50%; background: <?php 
                                                    echo $notificacion['color'] === 'info' ? '#17a2b8' : 
                                                         ($notificacion['color'] === 'success' ? '#28a745' : 
                                                         ($notificacion['color'] === 'warning' ? '#ffc107' : '#dc3545')); 
                                                ?>; flex-shrink: 0;"></div>
                                                
                                                <div style="flex: 1;">
                                                    <h4 style="margin: 0 0 0.5rem 0; color: #495057; font-size: 1.1rem;">
                                                        <?php echo htmlspecialchars($notificacion['titulo']); ?>
                                                    </h4>
                                                    <p style="margin: 0 0 0.5rem 0; color: #6c757d; font-size: 0.95rem;">
                                                        <?php echo htmlspecialchars($notificacion['descripcion']); ?>
                                                    </p>
                                                    <small style="color: #6c757d;">
                                                        <i class="fas fa-clock"></i> 
                                                        <?php echo formatDateTime($notificacion['fecha']); ?>
                                                    </small>
                                                </div>
                                                
                                                <div style="flex-shrink: 0;">
                                                    <a href="mensajes/ver.php?id=<?php echo $notificacion['item_id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-envelope"></i> Ver
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div style="padding: 1rem; background: #f8f9fa; border-top: 1px solid #dee2e6;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <small style="color: #6c757d;">
                                            Mostrando las últimas <?php echo count($stats['notificaciones']); ?> notificaciones
                                        </small>
                                        <a href="mensajes/bandeja.php" class="btn btn-sm btn-primary">
                                            <i class="fas fa-list"></i> Ver Todos los Mensajes
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="notification-empty" style="text-align: center; padding: 3rem; color: #6c757d;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <h4 style="margin: 0 0 0.5rem 0;">No hay mensajes recientes</h4>
                                    <p style="margin: 0;">Cuando recibas nuevos mensajes aparecerán aquí</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones rápidas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <!-- Perfil de usuario - SIEMPRE PRIMERO -->
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                        <h3><i class="fas fa-user-circle"></i> Mi Perfil</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?php echo $_SESSION['user_name']; ?></p>
                        <p><strong>Tipo:</strong> <?php echo ucfirst($user_type); ?></p>
                        <p>Gestiona tu información personal y preferencias</p>
                    </div>
                    <div class="card-footer">
                        <a href="perfil/ver.php" class="btn btn-primary" style="width: 100%; text-align: center;">
                            <i class="fas fa-eye"></i> Ver Mi Perfil
                        </a>
                    </div>
                </div>

                <?php if ($user_type === 'ofertante'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plus"></i> Gestionar Actividades</h3>
                        </div>
                        <div class="card-body">
                            <p>Crea y gestiona tus actividades ofertadas</p>
                        </div>
                        <div class="card-footer" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <a href="actividades/mis-actividades.php" class="btn btn-primary" style="width: 100%; text-align: center;">
                                <i class="fas fa-list"></i> Ver Mis Actividades
                            </a>
                            <a href="actividades/crear.php" class="btn btn-success" style="width: 100%; text-align: center;">
                                <i class="fas fa-plus"></i> Crear Nueva
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar"></i> Reservas</h3>
                        </div>
                        <div class="card-body">
                            <p>Gestiona las reservas de tus actividades</p>
                        </div>
                        <div class="card-footer">
                            <a href="reservas/mis-reservas.php" class="btn btn-primary">
                                <i class="fas fa-calendar-check"></i> Ver Reservas
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Lista de Participantes</h3>
                        </div>
                        <div class="card-body">
                            <p>Visualiza todos los participantes confirmados organizados por actividad y fecha</p>
                        </div>
                        <div class="card-footer">
                            <a href="reservas/participantes.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Ver Participantes
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-search"></i> Solicitudes</h3>
                        </div>
                        <div class="card-body">
                            <p>Busca solicitudes de consumidores</p>
                        </div>
                        <div class="card-footer">
                            <a href="solicitudes/buscar.php" class="btn btn-info">
                                <i class="fas fa-search"></i> Buscar Solicitudes
                            </a>
                        </div>
                    </div>
                    
                <?php elseif ($user_type === 'consumidor'): ?>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar"></i> Mis Reservas</h3>
                        </div>
                        <div class="card-body">
                            <p>Gestiona tus reservas realizadas</p>
                        </div>
                        <div class="card-footer">
                            <a href="reservas/mis-reservas.php" class="btn btn-primary">
                                <i class="fas fa-calendar-check"></i> Ver Reservas
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-search"></i> Buscar Actividades</h3>
                        </div>
                        <div class="card-body">
                            <p>Encuentra actividades que te interesen</p>
                        </div>
                        <div class="card-footer">
                            <a href="actividades.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Explorar
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Mis Solicitudes</h3>
                        </div>
                        <div class="card-body">
                            <p>Gestiona tus solicitudes de actividades</p>
                        </div>
                        <div class="card-footer">
                            <a href="solicitudes/mis-solicitudes.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> Ver Solicitudes
                            </a>
                        </div>
                    </div>
                    
                    
                <?php elseif ($user_type === 'admin'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Gestionar Usuarios</h3>
                        </div>
                        <div class="card-body">
                            <p>Administra usuarios y permisos</p>
                        </div>
                        <div class="card-footer">
                            <a href="admin/usuarios.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Ver Usuarios
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Gestionar Actividades</h3>
                        </div>
                        <div class="card-body">
                            <p>Modera actividades y contenido</p>
                        </div>
                        <div class="card-footer">
                            <a href="admin/actividades.php" class="btn btn-warning">
                                <i class="fas fa-list"></i> Moderar
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Estadísticas</h3>
                        </div>
                        <div class="card-body">
                            <p>Ver estadísticas detalladas del sistema</p>
                        </div>
                        <div class="card-footer">
                            <a href="admin/estadisticas.php" class="btn btn-info">
                                <i class="fas fa-chart-bar"></i> Ver Estadísticas
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
