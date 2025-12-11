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
$user_type = $_SESSION['user_type'];

// Obtener reservas según el tipo de usuario
try {
    if ($user_type === 'consumidor') {
        // Obtener ID del consumidor
        $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
        $consumidorStmt = $db->prepare($consumidorQuery);
        $consumidorStmt->execute([$user_id]);
        $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
        $consumidor_id = $consumidor['id'];
        
        // Obtener reservas del consumidor - usar array asociativo para eliminar duplicados
        $query = "SELECT r.id, r.consumidor_id, r.actividad_id, r.disponibilidad_id, 
                  r.fecha_reserva, r.fecha_actividad, r.num_participantes, r.precio_total, 
                  r.estado, r.notas, r.fecha_confirmacion,
                  a.titulo, a.lugar_inicio, a.duracion_horas,
                  o.id as ofertante_id, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
                  FROM reservas r
                  JOIN actividades a ON r.actividad_id = a.id
                  JOIN ofertantes o ON a.ofertante_id = o.id
                  JOIN usuarios u ON o.usuario_id = u.id
                  WHERE r.consumidor_id = ?
                  ORDER BY r.fecha_actividad DESC, r.id DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$consumidor_id]);
        $reservas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar duplicados por ID de reserva usando array asociativo (más eficiente y garantiza unicidad)
        // Usar el ID como clave para garantizar que no haya duplicados
        $reservas_unicas = [];
        $ids_vistos = [];
        foreach ($reservas_raw as $reserva) {
            $reserva_id = (int)$reserva['id'];
            // Solo agregar si no hemos visto este ID antes
            if (!in_array($reserva_id, $ids_vistos, true)) {
                $ids_vistos[] = $reserva_id;
                $reservas_unicas[] = $reserva;
            }
        }
        $reservas = $reservas_unicas;
        
        // Obtener participantes para cada reserva
        foreach ($reservas as $key => $reserva) {
            $participantesQuery = "SELECT nombre FROM participantes_reservas WHERE reserva_id = ? ORDER BY orden ASC";
            $participantesStmt = $db->prepare($participantesQuery);
            $participantesStmt->execute([$reserva['id']]);
            $reservas[$key]['participantes'] = $participantesStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
    } elseif ($user_type === 'ofertante') {
        // Obtener ID del ofertante
        $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
        $ofertanteStmt = $db->prepare($ofertanteQuery);
        $ofertanteStmt->execute([$user_id]);
        $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
        $ofertante_id = $ofertante['id'];
        
        // Obtener reservas de las actividades del ofertante
        $query = "SELECT r.*, a.titulo, a.lugar_inicio, a.duracion_horas,
                  c.id as consumidor_id, u.nombre as consumidor_nombre, u.apellidos as consumidor_apellidos, u.telefono as consumidor_telefono
                  FROM reservas r
                  JOIN actividades a ON r.actividad_id = a.id
                  JOIN consumidores c ON r.consumidor_id = c.id
                  JOIN usuarios u ON c.usuario_id = u.id
                  WHERE a.ofertante_id = ?
                  ORDER BY r.fecha_actividad DESC, r.id DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$ofertante_id]);
        $reservas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar duplicados por ID de reserva usando array asociativo (más eficiente y garantiza unicidad)
        // Usar el ID como clave para garantizar que no haya duplicados
        $reservas_unicas = [];
        $ids_vistos = [];
        foreach ($reservas_raw as $reserva) {
            $reserva_id = (int)$reserva['id'];
            // Solo agregar si no hemos visto este ID antes
            if (!in_array($reserva_id, $ids_vistos, true)) {
                $ids_vistos[] = $reserva_id;
                $reservas_unicas[] = $reserva;
            }
        }
        $reservas = $reservas_unicas;
        
        // Obtener participantes para cada reserva
        foreach ($reservas as $key => $reserva) {
            $participantesQuery = "SELECT nombre FROM participantes_reservas WHERE reserva_id = ? ORDER BY orden ASC";
            $participantesStmt = $db->prepare($participantesQuery);
            $participantesStmt->execute([$reserva['id']]);
            $reservas[$key]['participantes'] = $participantesStmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar las reservas';
}

$estados_reserva = getRequestStatus();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="../dashboard.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver al panel
                </a>
            </div>

            <!-- Header -->
            <div style="margin-bottom: 2rem;">
                <h1><i class="fas fa-calendar-check"></i> Mis Reservas</h1>
                <p style="color: #6c757d;">
                    <?php if ($user_type === 'consumidor'): ?>
                        Gestiona tus reservas de actividades
                    <?php else: ?>
                        Gestiona las reservas de tus actividades
                    <?php endif; ?>
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php displayAlert(); ?>

            <!-- Estadísticas -->
            <div class="dashboard-stats" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($reservas); ?></div>
                    <div class="stat-label">Total Reservas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($reservas, fn($r) => $r['estado'] === 'pendiente')); ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($reservas, fn($r) => $r['estado'] === 'confirmada')); ?></div>
                    <div class="stat-label">Confirmadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($reservas, fn($r) => $r['estado'] === 'completada')); ?></div>
                    <div class="stat-label">Completadas</div>
                </div>
            </div>

            <?php if (empty($reservas)): ?>
                <!-- Sin reservas -->
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No tienes reservas</h3>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        <?php if ($user_type === 'consumidor'): ?>
                            Explora nuestras actividades y haz tu primera reserva
                        <?php else: ?>
                            Aún no has recibido reservas en tus actividades
                        <?php endif; ?>
                    </p>
                    <?php if ($user_type === 'consumidor'): ?>
                        <a href="../actividades.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar Actividades
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Lista de reservas -->
                <div class="activities-grid">
                    <?php foreach ($reservas as $reserva): ?>
                        <div class="card">
                            <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($reserva['titulo']); ?></h3>
                            </div>
                            <div class="card-body">
                                <!-- Información de la actividad -->
                                <div style="margin-bottom: 1rem;">
                                    <p><i class="fas fa-calendar"></i> <strong>Fecha:</strong> <?php echo formatDateTime($reserva['fecha_actividad']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <strong>Lugar:</strong> <?php echo htmlspecialchars($reserva['lugar_inicio']); ?></p>
                                    <p><i class="fas fa-clock"></i> <strong>Duración:</strong> <?php echo $reserva['duracion_horas']; ?> horas</p>
                                    <p><i class="fas fa-users"></i> <strong>Participantes:</strong> <?php echo $reserva['num_participantes']; ?></p>
                                    <?php if (!empty($reserva['participantes'])): ?>
                                        <div style="background: #e7f3ff; padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem;">
                                            <strong style="display: block; margin-bottom: 0.5rem; color: #0066cc;">
                                                <i class="fas fa-list"></i> Lista de Participantes:
                                            </strong>
                                            <ul style="margin: 0; padding-left: 1.5rem; color: #2c3e50;">
                                                <?php foreach ($reserva['participantes'] as $participante): ?>
                                                    <li><?php echo htmlspecialchars($participante); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <p><i class="fas fa-euro-sign"></i> <strong>Total:</strong> <?php echo formatPrice($reserva['precio_total']); ?></p>
                                </div>

                                <!-- Información de contacto -->
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <?php if ($user_type === 'consumidor'): ?>
                                        <p><strong>Ofertante:</strong> <?php echo htmlspecialchars($reserva['ofertante_nombre'] . ' ' . $reserva['ofertante_apellidos']); ?></p>
                                    <?php else: ?>
                                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($reserva['consumidor_nombre'] . ' ' . $reserva['consumidor_apellidos']); ?></p>
                                        <?php if ($reserva['consumidor_telefono']): ?>
                                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reserva['consumidor_telefono']); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($reserva['notas']): ?>
                                    <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                                        <strong><i class="fas fa-comment"></i> Notas:</strong>
                                        <p style="margin: 0.5rem 0 0 0;"><?php echo nl2br(htmlspecialchars($reserva['notas'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Estado -->
                                <div style="margin-bottom: 1rem;">
                                    <?php
                                    $estadoColors = [
                                        'pendiente' => '#f39c12',
                                        'confirmada' => '#2ecc71',
                                        'completada' => '#3498db',
                                        'cancelada' => '#e74c3c',
                                        'rechazada' => '#e74c3c'
                                    ];
                                    $color = $estadoColors[$reserva['estado']] ?? '#6c757d';
                                    ?>
                                    <span style="background: <?php echo $color; ?>; color: white; padding: 8px 20px; border-radius: 20px; font-weight: 600;">
                                        <i class="fas fa-info-circle"></i> 
                                        <?php echo $estados_reserva[$reserva['estado']] ?? $reserva['estado']; ?>
                                    </span>
                                </div>

                                <?php if ($reserva['fecha_confirmacion']): ?>
                                    <p style="color: #6c757d; font-size: 0.9rem;">
                                        <i class="fas fa-check"></i> Confirmada el: <?php echo formatDateTime($reserva['fecha_confirmacion']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="../actividades/ver.php?id=<?php echo $reserva['actividad_id']; ?>" 
                                       class="btn btn-info btn-sm"
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-eye"></i> Ver Actividad
                                    </a>

                                    <?php if ($user_type === 'ofertante' && $reserva['estado'] === 'pendiente'): ?>
                                        <a href="confirmar.php?id=<?php echo $reserva['id']; ?>&accion=confirmar" 
                                           class="btn btn-success btn-sm btn-confirmar-reserva"
                                           style="flex: 1; text-align: center;"
                                           data-reserva-id="<?php echo $reserva['id']; ?>"
                                           onclick="return confirmarReserva(this, <?php echo $reserva['id']; ?>);">
                                            <i class="fas fa-check"></i> Confirmar
                                        </a>
                                        <a href="confirmar.php?id=<?php echo $reserva['id']; ?>&accion=rechazar" 
                                           class="btn btn-danger btn-sm"
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Estás seguro de rechazar esta reserva?');">
                                            <i class="fas fa-times"></i> Rechazar
                                        </a>
                                    <?php elseif ($user_type === 'ofertante' && $reserva['estado'] === 'confirmada'): ?>
                                        <a href="completar.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-primary btn-sm"
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Marcar esta reserva como completada?');">
                                            <i class="fas fa-flag-checkered"></i> Completar
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($user_type === 'consumidor' && ($reserva['estado'] === 'pendiente' || $reserva['estado'] === 'confirmada')): ?>
                                        <a href="cancelar.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-warning btn-sm"
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Estás seguro de cancelar esta reserva?');">
                                            <i class="fas fa-ban"></i> Cancelar
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($reserva['estado'] === 'cancelada'): ?>
                                        <a href="eliminar.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Estás seguro de eliminar permanentemente esta reserva cancelada? Esta acción no se puede deshacer.');">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        // Prevenir múltiples clics en el botón de confirmar
        let procesandoReserva = false;
        
        function confirmarReserva(element, reservaId) {
            if (procesandoReserva) {
                alert('Por favor, espera a que se procese la acción anterior.');
                return false;
            }
            
            const confirmacion = confirm('¿Estás seguro de confirmar esta reserva?');
            if (!confirmacion) {
                return false;
            }
            
            // Deshabilitar todos los botones de confirmar para prevenir múltiples clics
            procesandoReserva = true;
            document.querySelectorAll('.btn-confirmar-reserva').forEach(btn => {
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.6';
            });
            
            // Permitir que el enlace continúe
            return true;
        }
        
        // Si la página se carga después de una redirección, restaurar los botones
        window.addEventListener('load', function() {
            procesandoReserva = false;
            document.querySelectorAll('.btn-confirmar-reserva').forEach(btn => {
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            });
        });
    </script>
    <style>
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
