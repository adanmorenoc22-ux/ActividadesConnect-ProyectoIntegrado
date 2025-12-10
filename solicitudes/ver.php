<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('../dashboard.php');
}

$solicitud_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Obtener datos de la solicitud
try {
    $query = "SELECT s.*, u.nombre as consumidor_nombre, u.apellidos as consumidor_apellidos, u.email as consumidor_email
              FROM solicitudes_consumidores s
              JOIN consumidores c ON s.consumidor_id = c.id
              JOIN usuarios u ON c.usuario_id = u.id
              WHERE s.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$solicitud_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        showAlert('Solicitud no encontrada', 'danger');
        redirect('../dashboard.php');
    }
    
    // Verificar permisos
    if ($user_type === 'consumidor') {
        $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$user_id]);
        $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($solicitud['consumidor_id'] != $consumidor['id']) {
            showAlert('No tienes permisos para ver esta solicitud', 'danger');
            redirect('../dashboard.php');
        }
    }
    
    // Obtener intereses si es consumidor
    $intereses = [];
    if ($user_type === 'consumidor') {
        $interesesQuery = "SELECT i.*, o.verificado,
                          u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
                          FROM intereses_ofertantes i
                          JOIN ofertantes o ON i.ofertante_id = o.id
                          JOIN usuarios u ON o.usuario_id = u.id
                          WHERE i.solicitud_id = ? AND i.estado = 'activo'
                          ORDER BY i.fecha_interes DESC";
        $interesesStmt = $db->prepare($interesesQuery);
        $interesesStmt->execute([$solicitud_id]);
        $intereses = $interesesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marcar todos los intereses de esta solicitud como vistos
        $marcarVistosQuery = "UPDATE intereses_ofertantes 
                             SET visto = TRUE, fecha_visto = NOW() 
                             WHERE solicitud_id = ? AND estado = 'activo' AND visto = FALSE";
        $marcarVistosStmt = $db->prepare($marcarVistosQuery);
        $marcarVistosStmt->execute([$solicitud_id]);
    }
    
    // Verificar si ofertante ya mostró interés
    $ya_interesado = false;
    if ($user_type === 'ofertante') {
        $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
        $ofertanteStmt = $db->prepare($ofertanteQuery);
        $ofertanteStmt->execute([$user_id]);
        $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ofertante) {
            $checkQuery = "SELECT id FROM intereses_ofertantes WHERE ofertante_id = ? AND solicitud_id = ? AND estado = 'activo'";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$ofertante['id'], $solicitud_id]);
            $ya_interesado = $checkStmt->rowCount() > 0;
        }
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar la solicitud';
}

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($solicitud['titulo']); ?> - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Navegación -->
            <div style="margin-bottom: 2rem;">
                <?php if ($user_type === 'consumidor'): ?>
                    <a href="mis-solicitudes.php" style="color: #667eea; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Volver a mis solicitudes
                    </a>
                <?php else: ?>
                    <a href="buscar.php" style="color: #667eea; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Volver a buscar solicitudes
                    </a>
                <?php endif; ?>
            </div>

            <!-- Detalles de la solicitud -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <h1 style="margin: 0; color: white;"><?php echo htmlspecialchars($solicitud['titulo']); ?></h1>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="background: rgba(255,255,255,0.3); color: white; padding: 8px 15px; border-radius: 20px;">
                                <?php echo $categorias[$solicitud['categoria']] ?? $solicitud['categoria']; ?>
                            </span>
                            <?php
                            $estadoColors = [
                                'activa' => '#2ecc71',
                                'en_proceso' => '#f39c12',
                                'completada' => '#3498db',
                                'cancelada' => '#e74c3c'
                            ];
                            $color = $estadoColors[$solicitud['estado']] ?? '#6c757d';
                            ?>
                            <span style="background: <?php echo $color; ?>; color: white; padding: 8px 15px; border-radius: 20px;">
                                <?php echo ucfirst($solicitud['estado']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Información del solicitante -->
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #495057;">
                            <i class="fas fa-user"></i> Solicitante
                        </h3>
                        <p style="margin: 0; font-size: 1.1rem;">
                            <strong><?php echo htmlspecialchars($solicitud['consumidor_nombre'] . ' ' . $solicitud['consumidor_apellidos']); ?></strong>
                        </p>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($solicitud['consumidor_email']); ?>
                        </p>
                    </div>

                    <!-- Descripción -->
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: #495057; margin-bottom: 1rem;">
                            <i class="fas fa-info-circle"></i> Descripción
                        </h3>
                        <p style="line-height: 1.6; color: #495057; white-space: pre-wrap;"><?php echo htmlspecialchars($solicitud['descripcion']); ?></p>
                    </div>

                    <!-- Detalles de la actividad -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #2196f3;">
                            <h4 style="margin: 0 0 1rem 0; color: #1976d2;">
                                <i class="fas fa-calendar"></i> Fecha y Hora
                            </h4>
                            <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">
                                <?php echo formatDate($solicitud['fecha_deseada']); ?>
                            </p>
                            <?php if ($solicitud['hora_deseada']): ?>
                                <p style="margin: 0.5rem 0 0 0; color: #666;">
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($solicitud['hora_deseada'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div style="background: #f3e5f5; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #9c27b0;">
                            <h4 style="margin: 0 0 1rem 0; color: #7b1fa2;">
                                <i class="fas fa-map-marker-alt"></i> Ubicación
                            </h4>
                            <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">
                                <?php echo htmlspecialchars($solicitud['ubicacion']); ?>
                            </p>
                        </div>

                        <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #4caf50;">
                            <h4 style="margin: 0 0 1rem 0; color: #388e3c;">
                                <i class="fas fa-users"></i> Participantes
                            </h4>
                            <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">
                                <?php echo $solicitud['participantes_estimados']; ?> personas
                            </p>
                        </div>

                        <?php if ($solicitud['presupuesto_max']): ?>
                            <div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ff9800;">
                                <h4 style="margin: 0 0 1rem 0; color: #f57c00;">
                                    <i class="fas fa-euro-sign"></i> Presupuesto
                                </h4>
                                <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">
                                    Máximo <?php echo formatPrice($solicitud['presupuesto_max']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($solicitud['duracion_estimada']): ?>
                            <div style="background: #fce4ec; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #e91e63;">
                                <h4 style="margin: 0 0 1rem 0; color: #c2185b;">
                                    <i class="fas fa-hourglass-half"></i> Duración
                                </h4>
                                <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">
                                    <?php echo $solicitud['duracion_estimada']; ?> horas
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Requisitos especiales -->
                    <?php if ($solicitud['requisitos_especiales']): ?>
                        <div style="background: #fff8e1; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 1rem 0; color: #f57f17;">
                                <i class="fas fa-exclamation-triangle"></i> Requisitos Especiales
                            </h4>
                            <p style="margin: 0; line-height: 1.6; color: #495057; white-space: pre-wrap;">
                                <?php echo htmlspecialchars($solicitud['requisitos_especiales']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Fechas -->
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #495057;">
                            <i class="fas fa-calendar-alt"></i> Fechas Importantes
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>Publicada:</strong><br>
                                <?php echo formatDate($solicitud['fecha_creacion']); ?>
                            </div>
                            <div>
                                <strong>Última actualización:</strong><br>
                                <?php echo formatDate($solicitud['fecha_actualizacion']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones según el tipo de usuario -->
            <?php if ($user_type === 'consumidor'): ?>
                <!-- Acciones para consumidor -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="fas fa-cogs"></i> Acciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <?php if ($solicitud['estado'] === 'activa'): ?>
                                <a href="editar.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Editar Solicitud
                                </a>
                                <a href="cancelar-solicitud.php?id=<?php echo $solicitud['id']; ?>" 
                                   class="btn btn-warning"
                                   onclick="return confirm('¿Estás seguro de cancelar esta solicitud?');">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                            
                            <a href="ver-intereses.php?solicitud_id=<?php echo $solicitud['id']; ?>" class="btn btn-success">
                                <i class="fas fa-heart"></i> Ver Intereses (<?php echo count($intereses); ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Intereses recibidos -->
                <?php if (!empty($intereses)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin: 0;">
                                <i class="fas fa-heart"></i> Ofertantes Interesados (<?php echo count($intereses); ?>)
                            </h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; gap: 1.5rem;">
                                <?php foreach ($intereses as $interes): ?>
                                    <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; background: #f8f9fa;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h4 style="margin: 0;">
                                                <?php echo htmlspecialchars($interes['ofertante_nombre'] . ' ' . $interes['ofertante_apellidos']); ?>
                                                <?php if ($interes['verificado']): ?>
                                                    <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;">
                                                        <i class="fas fa-check-circle"></i> Verificado
                                                    </span>
                                                <?php endif; ?>
                                            </h4>
                                            <span style="color: #6c757d; font-size: 0.9rem;">
                                                <i class="fas fa-clock"></i> <?php echo formatDateTime($interes['fecha_interes']); ?>
                                            </span>
                                        </div>
                                        
                                        <p style="margin-bottom: 1rem; color: #495057;">
                                            Este ofertante se ha interesado en tu solicitud. Revisa su perfil para estar atento a cuando cree esta actividad.
                                        </p>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                            </div>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="../ofertantes/ver.php?id=<?php echo $interes['ofertante_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Ver Perfil
                                                </a>
                                                <a href="../mensajes/nuevo.php?destinatario_id=<?php echo $interes['ofertante_id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-envelope"></i> Contactar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($user_type === 'ofertante'): ?>
                <!-- Acciones para ofertante -->
                <div class="card">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="fas fa-cogs"></i> Acciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <?php if (!$ya_interesado && $solicitud['estado'] === 'activa'): ?>
                                <a href="interes.php?solicitud_id=<?php echo $solicitud['id']; ?>" class="btn btn-success"
                                   onclick="return confirm('¿Mostrar interés en esta solicitud? El consumidor recibirá una notificación.');">
                                    <i class="fas fa-heart"></i> Me Interesa
                                </a>
                            <?php elseif ($ya_interesado): ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed;">
                                    <i class="fas fa-check"></i> Ya mostraste interés
                                </span>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed;">
                                    <i class="fas fa-lock"></i> Solicitud no disponible
                                </span>
                            <?php endif; ?>
                            
                            <a href="../mensajes/nuevo.php?destinatario_id=<?php echo $solicitud['consumidor_id']; ?>" class="btn btn-info">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
