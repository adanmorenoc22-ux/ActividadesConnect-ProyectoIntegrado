<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea ofertante
if (!isLoggedIn() || !isOfertante()) {
    redirect('../dashboard.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Obtener ID del ofertante
    $ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
    $ofertanteStmt = $db->prepare($ofertanteQuery);
    $ofertanteStmt->execute([$user_id]);
    $ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
    $ofertante_id = $ofertante['id'];
    
    // Obtener todas las actividades del ofertante con sus reservas confirmadas
    $query = "SELECT 
                a.id as actividad_id,
                a.titulo as actividad_titulo,
                r.id as reserva_id,
                r.disponibilidad_id,
                r.fecha_actividad,
                r.num_participantes,
                r.estado as reserva_estado,
                r.fecha_confirmacion,
                da.fecha_inicio,
                da.fecha_fin,
                c.usuario_id as consumidor_usuario_id,
                u.nombre as consumidor_nombre,
                u.apellidos as consumidor_apellidos,
                u.telefono as consumidor_telefono
              FROM actividades a
              JOIN reservas r ON a.id = r.actividad_id
              JOIN disponibilidad_actividades da ON r.disponibilidad_id = da.id
              JOIN consumidores c ON r.consumidor_id = c.id
              JOIN usuarios u ON c.usuario_id = u.id
              WHERE a.ofertante_id = ? 
                AND r.estado IN ('confirmada', 'completada')
                AND r.fecha_actividad >= NOW()
              ORDER BY a.titulo ASC, r.fecha_actividad ASC, r.id ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$ofertante_id]);
    $reservas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eliminar duplicados por ID de reserva usando array asociativo (más eficiente y garantiza unicidad)
    $reservas_unicas = [];
    $ids_vistos = [];
    foreach ($reservas_raw as $reserva) {
        $reserva_id = (int)$reserva['reserva_id'];
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
        $participantesStmt->execute([$reserva['reserva_id']]);
        $reservas[$key]['participantes'] = $participantesStmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Organizar por actividad y fecha (usar disponibilidad_id para diferenciar fechas con misma hora)
    $participantes_organizados = [];
    foreach ($reservas as $reserva) {
        $actividad_id = $reserva['actividad_id'];
        // Usar disponibilidad_id para crear una clave única por fecha/hora/disponibilidad
        $fecha_key = date('Y-m-d H:i', strtotime($reserva['fecha_actividad'])) . '_' . $reserva['disponibilidad_id'];
        
        if (!isset($participantes_organizados[$actividad_id])) {
            $participantes_organizados[$actividad_id] = [
                'titulo' => $reserva['actividad_titulo'],
                'fechas' => []
            ];
        }
        
        if (!isset($participantes_organizados[$actividad_id]['fechas'][$fecha_key])) {
            $participantes_organizados[$actividad_id]['fechas'][$fecha_key] = [
                'fecha_actividad' => $reserva['fecha_actividad'],
                'fecha_inicio' => $reserva['fecha_inicio'],
                'fecha_fin' => $reserva['fecha_fin'],
                'disponibilidad_id' => $reserva['disponibilidad_id'],
                'reservas' => []
            ];
        }
        
        // Verificar que esta reserva no esté ya en el array para evitar duplicados
        $reserva_ya_existe = false;
        foreach ($participantes_organizados[$actividad_id]['fechas'][$fecha_key]['reservas'] as $reserva_existente) {
            if ($reserva_existente['reserva_id'] == $reserva['reserva_id']) {
                $reserva_ya_existe = true;
                break;
            }
        }
        
        if (!$reserva_ya_existe) {
            $participantes_organizados[$actividad_id]['fechas'][$fecha_key]['reservas'][] = $reserva;
        }
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar los participantes: ' . $e->getMessage();
}

$estados_reserva = getRequestStatus();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Participantes - ActividadesConnect</title>
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
                <h1><i class="fas fa-users"></i> Lista de Participantes</h1>
                <p style="color: #6c757d;">
                    Visualiza todos los participantes confirmados de tus actividades, organizados por actividad y fecha
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php displayAlert(); ?>

            <?php if (empty($participantes_organizados)): ?>
                <!-- Sin participantes -->
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-users-slash" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No hay participantes confirmados</h3>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        Aún no tienes reservas confirmadas para tus actividades. Los participantes aparecerán aquí una vez que confirmes las reservas.
                    </p>
                    <a href="mis-reservas.php" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Ver Mis Reservas
                    </a>
                </div>
            <?php else: ?>
                <!-- Lista organizada por actividad y fecha -->
                <?php foreach ($participantes_organizados as $actividad_id => $actividad_data): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                            <h2 style="margin: 0; color: white;">
                                <i class="fas fa-mountain"></i> <?php echo htmlspecialchars($actividad_data['titulo']); ?>
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($actividad_data['fechas'] as $fecha_key => $fecha_data): ?>
                                <div style="border-left: 4px solid #667eea; padding-left: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef;">
                                    <!-- Información de la fecha -->
                                    <div style="margin-bottom: 1rem;">
                                        <h3 style="color: #667eea; margin-bottom: 0.5rem;">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php echo formatDateTime($fecha_data['fecha_actividad']); ?>
                                        </h3>
                                        <div style="color: #6c757d; font-size: 0.9rem;">
                                            <i class="fas fa-clock"></i> 
                                            Inicio: <?php echo date('H:i', strtotime($fecha_data['fecha_inicio'])); ?> | 
                                            Fin: <?php echo date('H:i', strtotime($fecha_data['fecha_fin'])); ?>
                                        </div>
                                    </div>

                                    <!-- Lista de reservas para esta fecha -->
                                    <?php foreach ($fecha_data['reservas'] as $reserva): ?>
                                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                            <!-- Información del cliente -->
                                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid #dee2e6;">
                                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                                    <div>
                                                        <h4 style="margin: 0 0 0.5rem 0; color: #2c3e50;">
                                                            <i class="fas fa-user"></i> 
                                                            <?php echo htmlspecialchars($reserva['consumidor_nombre'] . ' ' . $reserva['consumidor_apellidos']); ?>
                                                        </h4>
                                                        <?php if ($reserva['consumidor_telefono']): ?>
                                                            <p style="margin: 0; color: #6c757d;">
                                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($reserva['consumidor_telefono']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <span style="background: #2ecc71; color: white; padding: 6px 15px; border-radius: 20px; font-weight: 600; font-size: 0.85rem;">
                                                            <i class="fas fa-check-circle"></i> 
                                                            <?php echo $estados_reserva[$reserva['reserva_estado']] ?? $reserva['reserva_estado']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Lista de participantes -->
                                            <div>
                                                <strong style="display: block; margin-bottom: 0.75rem; color: #2c3e50;">
                                                    <i class="fas fa-users"></i> 
                                                    Participantes (<?php echo $reserva['num_participantes']; ?>):
                                                </strong>
                                                <?php if (!empty($reserva['participantes'])): ?>
                                                    <ul style="margin: 0; padding-left: 1.5rem; list-style-type: none;">
                                                        <?php foreach ($reserva['participantes'] as $index => $participante): ?>
                                                            <li style="padding: 0.5rem 0; color: #2c3e50; border-bottom: 1px solid #e9ecef;">
                                                                <i class="fas fa-user-circle" style="color: #667eea; margin-right: 0.5rem;"></i>
                                                                <strong><?php echo ($index + 1); ?>.</strong> <?php echo htmlspecialchars($participante); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p style="color: #6c757d; font-style: italic; margin: 0;">
                                                        No se registraron nombres de participantes para esta reserva.
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($reserva['fecha_confirmacion']): ?>
                                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                                                    <p style="margin: 0; color: #6c757d; font-size: 0.85rem;">
                                                        <i class="fas fa-check"></i> 
                                                        Reserva confirmada el: <?php echo formatDateTime($reserva['fecha_confirmacion']); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Resumen de la fecha -->
                                    <?php
                                    $total_participantes_fecha = 0;
                                    $total_reservas_fecha = count($fecha_data['reservas']);
                                    foreach ($fecha_data['reservas'] as $reserva) {
                                        $total_participantes_fecha += $reserva['num_participantes'];
                                    }
                                    ?>
                                    <div style="background: #e7f3ff; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                                        <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 1rem;">
                                            <div style="text-align: center;">
                                                <div style="font-size: 1.5rem; font-weight: 600; color: #0066cc;">
                                                    <?php echo $total_reservas_fecha; ?>
                                                </div>
                                                <div style="color: #6c757d; font-size: 0.85rem;">
                                                    Reserva(s)
                                                </div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-size: 1.5rem; font-weight: 600; color: #0066cc;">
                                                    <?php echo $total_participantes_fecha; ?>
                                                </div>
                                                <div style="color: #6c757d; font-size: 0.85rem;">
                                                    Participante(s) Total
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>

