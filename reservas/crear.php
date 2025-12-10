<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario sea consumidor
if (!isLoggedIn() || !isConsumidor()) {
    showAlert('Debes ser un consumidor registrado para hacer reservas', 'warning');
    redirect('../login.php');
}

// Verificar que se recibió el ID de actividad
if (!isset($_GET['actividad_id']) || empty($_GET['actividad_id'])) {
    redirect('../actividades.php');
}

$actividad_id = (int)$_GET['actividad_id'];
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';


// Obtener ID del consumidor
try {
    $consumidorQuery = "SELECT id FROM consumidores WHERE usuario_id = ?";
    $consumidorStmt = $db->prepare($consumidorQuery);
    $consumidorStmt->execute([$user_id]);
    $consumidor = $consumidorStmt->fetch(PDO::FETCH_ASSOC);
    $consumidor_id = $consumidor['id'];
    
    // Obtener datos de la actividad
    $actividadQuery = "SELECT a.*, o.*, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
                       FROM actividades a
                       JOIN ofertantes o ON a.ofertante_id = o.id
                       JOIN usuarios u ON o.usuario_id = u.id
                       WHERE a.id = ? AND a.estado = 'activa'";
    $actividadStmt = $db->prepare($actividadQuery);
    $actividadStmt->execute([$actividad_id]);
    $actividad = $actividadStmt->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$actividad) {
        showAlert('Actividad no encontrada o no está disponible', 'danger');
        redirect('../actividades.php');
    }
    
    // Obtener disponibilidades futuras
    $dispQuery = "SELECT * FROM disponibilidad_actividades 
                  WHERE actividad_id = ? AND estado = 'disponible' 
                  AND fecha_inicio >= NOW()
                  ORDER BY fecha_inicio ASC";
    $dispStmt = $db->prepare($dispQuery);
    $dispStmt->execute([$actividad_id]);
    $disponibilidades = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error al cargar la actividad';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_reserva'])) {
    $disponibilidad_id = $_POST['disponibilidad_id'] ?? null;
    $num_participantes = (int)($_POST['num_participantes'] ?? 0);
    $participantes = $_POST['participantes'] ?? [];
    $notas = sanitizeInput($_POST['notas'] ?? '');
    
    // Validaciones
    if (empty($disponibilidad_id)) {
        $error = 'Por favor, selecciona una fecha disponible';
    } elseif ($num_participantes < 1) {
        $error = 'El número de participantes debe ser al menos 1';
    } elseif (count($participantes) !== $num_participantes) {
        $error = 'Debes ingresar el nombre de todos los participantes';
    } elseif (empty(array_filter(array_map('trim', $participantes)))) {
        $error = 'Debes ingresar al menos un nombre de participante';
    } else {
        try {
            // Verificar que no exista ya una reserva pendiente o confirmada para esta disponibilidad y consumidor
            $verificarDuplicadoQuery = "SELECT id FROM reservas 
                                       WHERE consumidor_id = ? 
                                       AND disponibilidad_id = ? 
                                       AND estado IN ('pendiente', 'confirmada')";
            $verificarDuplicadoStmt = $db->prepare($verificarDuplicadoQuery);
            $verificarDuplicadoStmt->execute([$consumidor_id, $disponibilidad_id]);
            if ($verificarDuplicadoStmt->rowCount() > 0) {
                $error = 'Ya tienes una reserva pendiente o confirmada para esta fecha. Por favor, revisa tus reservas existentes.';
            } else {
                // Obtener datos de la disponibilidad seleccionada
            $dispSelQuery = "SELECT * FROM disponibilidad_actividades WHERE id = ? AND actividad_id = ?";
            $dispSelStmt = $db->prepare($dispSelQuery);
            $dispSelStmt->execute([$disponibilidad_id, $actividad_id]);
            $dispSeleccionada = $dispSelStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dispSeleccionada) {
                $error = 'Disponibilidad no encontrada';
            } elseif ($dispSeleccionada['plazas_disponibles'] < $num_participantes) {
                $plazas_disponibles = $dispSeleccionada['plazas_disponibles'];
                if ($plazas_disponibles == 0) {
                    $error = 'Lo sentimos, no quedan plazas disponibles para esta fecha. Por favor, selecciona otra fecha disponible.';
                } elseif ($plazas_disponibles == 1) {
                    $error = 'Lo sentimos, solo queda 1 plaza disponible para esta fecha. No puedes reservar para ' . $num_participantes . ' participantes.';
                } else {
                    $error = 'Lo sentimos, solo quedan ' . $plazas_disponibles . ' plazas disponibles para esta fecha. No puedes reservar para ' . $num_participantes . ' participantes. Por favor, ajusta el número de participantes o selecciona otra fecha.';
                }
            } else {
                // Calcular precio total
                $precio_por_persona = $dispSeleccionada['precio_especial'] ?? $actividad['precio_persona'];
                $precio_total = $precio_por_persona * $num_participantes;
                
                // Crear reserva
                $insertQuery = "INSERT INTO reservas (
                    consumidor_id, actividad_id, disponibilidad_id, fecha_actividad,
                    num_participantes, precio_total, estado, notas
                ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)";
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    $consumidor_id, $actividad_id, $disponibilidad_id,
                    $dispSeleccionada['fecha_inicio'], $num_participantes,
                    $precio_total, $notas
                ]);
                
                $reserva_id = $db->lastInsertId();
                
                // Guardar nombres de participantes
                $participanteQuery = "INSERT INTO participantes_reservas (reserva_id, nombre, orden) VALUES (?, ?, ?)";
                $participanteStmt = $db->prepare($participanteQuery);
                
                foreach ($participantes as $index => $nombre) {
                    $nombre = sanitizeInput(trim($nombre));
                    if (!empty($nombre)) {
                        $participanteStmt->execute([$reserva_id, $nombre, $index + 1]);
                    }
                }
                
                // Enviar mensaje al ofertante sobre la nueva reserva
                $ofertanteUsuarioQuery = "SELECT u.id as ofertante_usuario_id, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos,
                                         c.usuario_id as consumidor_usuario_id, cus.nombre as consumidor_nombre, cus.apellidos as consumidor_apellidos
                                         FROM actividades a
                                         JOIN ofertantes o ON a.ofertante_id = o.id
                                         JOIN usuarios u ON o.usuario_id = u.id
                                         JOIN consumidores c ON c.id = ?
                                         JOIN usuarios cus ON c.usuario_id = cus.id
                                         WHERE a.id = ?";
                $ofertanteUsuarioStmt = $db->prepare($ofertanteUsuarioQuery);
                $ofertanteUsuarioStmt->execute([$consumidor_id, $actividad_id]);
                $ofertanteInfo = $ofertanteUsuarioStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($ofertanteInfo) {
                    $mensajeQuery = "INSERT INTO mensajes (remitente_id, destinatario_id, asunto, mensaje) VALUES (?, ?, ?, ?)";
                    $mensajeStmt = $db->prepare($mensajeQuery);
                    
                    $asunto = "Nueva reserva recibida: " . $actividad['titulo'];
                    $cuerpoMensaje = "Hola " . $ofertanteInfo['ofertante_nombre'] . " " . $ofertanteInfo['ofertante_apellidos'] . ",\n\n"
                        . "Has recibido una nueva reserva para tu actividad '" . $actividad['titulo'] . "'.\n\n"
                        . "Detalles de la reserva:\n"
                        . "- Cliente: " . $ofertanteInfo['consumidor_nombre'] . " " . $ofertanteInfo['consumidor_apellidos'] . "\n"
                        . "- Fecha de la actividad: " . formatDateTime($dispSeleccionada['fecha_inicio']) . "\n"
                        . "- Número de participantes: " . $num_participantes . "\n"
                        . "- Precio total: " . formatPrice($precio_total) . "\n\n"
                        . "Puedes revisar y gestionar esta reserva desde tu panel en la sección 'Mis Reservas'.\n\n"
                        . "Gracias por usar ActividadesConnect.";
                    
                    $mensajeStmt->execute([
                        $user_id, // consumidor que hace la reserva
                        $ofertanteInfo['ofertante_usuario_id'],
                        $asunto,
                        $cuerpoMensaje
                    ]);
                }
                
                // Actualizar plazas disponibles
                $updateDispQuery = "UPDATE disponibilidad_actividades 
                                   SET plazas_disponibles = plazas_disponibles - ? 
                                   WHERE id = ?";
                $updateDispStmt = $db->prepare($updateDispQuery);
                $updateDispStmt->execute([$num_participantes, $disponibilidad_id]);
                
                // Si no quedan plazas, marcar como completo
                if ($dispSeleccionada['plazas_disponibles'] - $num_participantes <= 0) {
                    $updateEstadoQuery = "UPDATE disponibilidad_actividades SET estado = 'completo' WHERE id = ?";
                    $updateEstadoStmt = $db->prepare($updateEstadoQuery);
                    $updateEstadoStmt->execute([$disponibilidad_id]);
                }
                
                showAlert('¡Reserva creada correctamente! El ofertante la revisará pronto.', 'success');
                redirect('mis-reservas.php');
            }
            }
            
        } catch (Exception $e) {
            $error = 'Error al crear la reserva: ' . $e->getMessage();
        }
    }
}

$categorias = getActivityCategories();
$niveles_dificultad = getDifficultyLevels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hacer Reserva - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="../actividades/ver.php?id=<?php echo $actividad_id; ?>" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a la actividad
                </a>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Columna principal -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h1><i class="fas fa-calendar-check"></i> Hacer Reserva</h1>
                            <p>Completa los datos para reservar tu plaza</p>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <?php if (empty($disponibilidades)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>No hay fechas disponibles</strong>
                                    <p>Esta actividad no tiene fechas programadas en este momento. Por favor, contacta con el ofertante para más información.</p>
                                </div>
                                <a href="../actividades/ver.php?id=<?php echo $actividad_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            <?php else: ?>
                                <form method="POST" class="needs-validation" novalidate id="reservaForm">
                                    <h3 style="color: #667eea; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                        <i class="fas fa-calendar-alt"></i> Selecciona Fecha y Hora
                                    </h3>

                                    <div class="form-group">
                                        <label><i class="fas fa-clock"></i> Fecha Disponible *</label>
                                        <?php foreach ($disponibilidades as $disp): ?>
                                            <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; cursor: pointer; transition: all 0.3s;" 
                                                 class="disp-option" 
                                                 onclick="selectDisp(<?php echo $disp['id']; ?>, <?php echo $disp['plazas_disponibles']; ?>, <?php echo $disp['precio_especial'] ?? $actividad['precio_persona']; ?>)">
                                                <label style="display: flex; align-items: center; cursor: pointer; width: 100%;">
                                                    <input type="radio" 
                                                           name="disponibilidad_id" 
                                                           value="<?php echo $disp['id']; ?>" 
                                                           required
                                                           style="margin-right: 1rem;">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem;">
                                                            <i class="fas fa-calendar"></i> 
                                                            <?php echo formatDate($disp['fecha_inicio']); ?>
                                                            a las <?php echo date('H:i', strtotime($disp['fecha_inicio'])); ?>
                                                        </div>
                                                        <div style="color: #6c757d; font-size: 0.9rem;">
                                                            <i class="fas fa-users"></i> 
                                                            <?php echo $disp['plazas_disponibles']; ?> plazas disponibles
                                                            <?php if ($disp['precio_especial']): ?>
                                                                | <span style="color: #e74c3c; font-weight: 600;">
                                                                    Precio especial: <?php echo formatPrice($disp['precio_especial']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($disp['notas']): ?>
                                                            <div style="color: #667eea; font-size: 0.85rem; margin-top: 0.5rem;">
                                                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($disp['notas']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <h3 style="color: #667eea; margin: 2rem 0 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                        <i class="fas fa-users"></i> Detalles de la Reserva
                                    </h3>

                                    <div class="form-group">
                                        <label for="num_participantes">
                                            <i class="fas fa-users"></i> Número de Participantes *
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="num_participantes" 
                                               name="num_participantes" 
                                               min="1"
                                               value="1"
                                               required
                                               onchange="actualizarCamposParticipantes(); calcularTotal();">
                                    </div>

                                    <!-- Campos dinámicos para nombres de participantes -->
                                    <div class="form-group" id="participantes-container" style="margin-top: 1.5rem;">
                                        <label style="margin-bottom: 1rem; display: block;">
                                            <i class="fas fa-user-friends"></i> Nombres de los Participantes *
                                        </label>
                                        <div id="participantes-lista">
                                            <div class="participante-item" style="margin-bottom: 0.75rem;">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="participantes[]" 
                                                       placeholder="Nombre completo del participante 1"
                                                       required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="notas">
                                            <i class="fas fa-comment"></i> Notas Adicionales
                                        </label>
                                        <textarea class="form-control" 
                                                  id="notas" 
                                                  name="notas" 
                                                  rows="3"
                                                  placeholder="Información adicional, restricciones médicas, alergias, preferencias..."><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : ''; ?></textarea>
                                    </div>

                                    <!-- Resumen de precio -->
                                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 2rem 0;">
                                        <h4 style="margin-bottom: 1rem;">Resumen de Precio</h4>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span>Precio por persona:</span>
                                            <strong id="precio-persona"><?php echo formatPrice($actividad['precio_persona']); ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span>Participantes:</span>
                                            <strong id="num-personas"><?php echo $actividad['min_participantes']; ?></strong>
                                        </div>
                                        <hr>
                                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem;">
                                            <strong>Total:</strong>
                                            <strong style="color: #2ecc71;" id="precio-total">
                                                <?php echo formatPrice($actividad['precio_persona'] * $actividad['min_participantes']); ?>
                                            </strong>
                                        </div>
                                    </div>

                                    <!-- Información importante -->
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Información importante:</strong>
                                        <ul style="margin: 0.5rem 0 0 1.5rem;">
                                            <li>Tu reserva quedará como <strong>pendiente</strong> hasta que el ofertante la confirme</li>
                                            <li>Recibirás una notificación cuando sea confirmada</li>
                                            <li>Puedes cancelar tu reserva desde "Mis Reservas"</li>
                                        </ul>
                                    </div>

                                    <!-- Botones -->
                                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                        <input type="hidden" name="crear_reserva" value="1">
                                        <button type="submit" class="btn btn-primary" style="flex: 1;" id="btnConfirmarReserva">
                                            <i class="fas fa-check"></i> Confirmar Reserva
                                        </button>
                                        <a href="../actividades/ver.php?id=<?php echo $actividad_id; ?>" 
                                           class="btn btn-secondary" 
                                           style="flex: 1; text-align: center;">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral - Resumen de actividad -->
                <div>
                    <div class="card">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 150px; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; border-radius: 15px 15px 0 0;">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
                            <div style="margin: 1rem 0;">
                                <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem;">
                                    <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                                </span>
                            </div>

                            <div style="margin: 1rem 0;">
                                <p><i class="fas fa-clock"></i> <strong>Duración:</strong> <?php echo $actividad['duracion_horas']; ?>h</p>
                                <p><i class="fas fa-signal"></i> <strong>Dificultad:</strong> <?php echo $niveles_dificultad[$actividad['dificultad']] ?? $actividad['dificultad']; ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <strong>Inicio:</strong> <?php echo htmlspecialchars($actividad['lugar_inicio']); ?></p>
                            </div>

                            <hr>

                            <div>
                                <h4>Ofertante</h4>
                                <p><strong><?php echo htmlspecialchars($actividad['ofertante_nombre'] . ' ' . $actividad['ofertante_apellidos']); ?></strong></p>
                                <?php if ($actividad['verificado']): ?>
                                    <p style="color: #2ecc71;">
                                        <i class="fas fa-check-circle"></i> Verificado
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        let precioBase = <?php echo $actividad['precio_persona']; ?>;
        let plazasMax = 0;

        function selectDisp(id, plazas, precio) {
            // Resaltar seleccionado
            document.querySelectorAll('.disp-option').forEach(el => {
                el.style.borderColor = '#e9ecef';
                el.style.background = 'white';
            });
            event.currentTarget.style.borderColor = '#667eea';
            event.currentTarget.style.background = '#f0f4ff';
            
            // Marcar el radio button correspondiente
            const radioButton = event.currentTarget.querySelector('input[type="radio"]');
            if (radioButton) {
                radioButton.checked = true;
            }
            
            // Actualizar precio base y plazas máximas (según la fecha seleccionada)
            precioBase = precio;
            plazasMax = plazas;
            
            // Actualizar límite del input
            const numParticipantesInput = document.getElementById('num_participantes');
            numParticipantesInput.max = plazasMax;
            
            // Si el valor actual es mayor que las plazas disponibles, ajustarlo
            if (parseInt(numParticipantesInput.value) > plazasMax) {
                numParticipantesInput.value = plazasMax;
                mostrarMensajePlazas(plazasMax);
            }
            
            // Actualizar campos de participantes y total
            actualizarCamposParticipantes();
            calcularTotal();
        }
        
        function mostrarMensajePlazas(plazasDisponibles) {
            // Crear o actualizar mensaje de alerta
            let mensajeDiv = document.getElementById('mensaje-plazas');
            if (!mensajeDiv) {
                mensajeDiv = document.createElement('div');
                mensajeDiv.id = 'mensaje-plazas';
                mensajeDiv.className = 'alert alert-warning';
                mensajeDiv.style.marginTop = '1rem';
                const numParticipantesContainer = document.getElementById('num_participantes').parentElement;
                numParticipantesContainer.parentElement.insertBefore(mensajeDiv, numParticipantesContainer.nextSibling);
            }
            
            if (plazasDisponibles === 0) {
                mensajeDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Atención:</strong> No quedan plazas disponibles para esta fecha. Por favor, selecciona otra fecha.';
                mensajeDiv.className = 'alert alert-danger';
            } else if (plazasDisponibles === 1) {
                mensajeDiv.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Información:</strong> Solo queda 1 plaza disponible para esta fecha.';
                mensajeDiv.className = 'alert alert-warning';
            } else {
                mensajeDiv.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Información:</strong> Quedan ' + plazasDisponibles + ' plazas disponibles para esta fecha.';
                mensajeDiv.className = 'alert alert-info';
            }
        }
        
        function ocultarMensajePlazas() {
            const mensajeDiv = document.getElementById('mensaje-plazas');
            if (mensajeDiv) {
                mensajeDiv.remove();
            }
        }

        function actualizarCamposParticipantes() {
            const numParticipantes = parseInt(document.getElementById('num_participantes').value) || 1;
            const container = document.getElementById('participantes-lista');
            container.innerHTML = '';
            
            // Validar que no exceda las plazas disponibles
            if (plazasMax > 0 && numParticipantes > plazasMax) {
                mostrarMensajePlazas(plazasMax);
                document.getElementById('num_participantes').value = plazasMax;
                return actualizarCamposParticipantes(); // Recursión con valor corregido
            } else if (plazasMax > 0) {
                mostrarMensajePlazas(plazasMax);
            } else {
                ocultarMensajePlazas();
            }
            
            for (let i = 0; i < numParticipantes; i++) {
                const div = document.createElement('div');
                div.className = 'participante-item';
                div.style.marginBottom = '0.75rem';
                div.innerHTML = `
                    <input type="text" 
                           class="form-control" 
                           name="participantes[]" 
                           placeholder="Nombre completo del participante ${i + 1}"
                           required>
                `;
                container.appendChild(div);
            }
        }

        function calcularTotal() {
            const numParticipantes = parseInt(document.getElementById('num_participantes').value) || 0;
            const total = precioBase * numParticipantes;
            
            document.getElementById('precio-persona').textContent = precioBase.toFixed(2) + ' €';
            document.getElementById('num-personas').textContent = numParticipantes;
            document.getElementById('precio-total').textContent = total.toFixed(2) + ' €';
        }

        // Calcular total inicial
        calcularTotal();
        
        // Validar formulario antes de enviar
        document.getElementById('reservaForm').addEventListener('submit', function(e) {
            const disponibilidadSeleccionada = document.querySelector('input[name="disponibilidad_id"]:checked');
            if (!disponibilidadSeleccionada) {
                e.preventDefault();
                alert('Por favor, selecciona una fecha disponible');
                return false;
            }
            
            // Validar que el número de participantes no exceda las plazas disponibles
            const numParticipantes = parseInt(document.getElementById('num_participantes').value) || 0;
            if (plazasMax > 0 && numParticipantes > plazasMax) {
                e.preventDefault();
                let mensaje = '';
                if (plazasMax === 0) {
                    mensaje = 'Lo sentimos, no quedan plazas disponibles para esta fecha. Por favor, selecciona otra fecha disponible.';
                } else if (plazasMax === 1) {
                    mensaje = 'Lo sentimos, solo queda 1 plaza disponible para esta fecha. No puedes reservar para ' + numParticipantes + ' participantes.';
                } else {
                    mensaje = 'Lo sentimos, solo quedan ' + plazasMax + ' plazas disponibles para esta fecha. No puedes reservar para ' + numParticipantes + ' participantes. Por favor, ajusta el número de participantes o selecciona otra fecha.';
                }
                alert(mensaje);
                return false;
            }
            
            // Validar que se hayan ingresado todos los nombres de participantes
            const participantesInputs = document.querySelectorAll('input[name="participantes[]"]');
            const participantesVacios = Array.from(participantesInputs).filter(input => !input.value.trim());
            if (participantesVacios.length > 0) {
                e.preventDefault();
                alert('Por favor, ingresa el nombre de todos los participantes');
                return false;
            }
            
            // Prevenir múltiples envíos
            if (enviandoReserva) {
                e.preventDefault();
                alert('Por favor, espera mientras se procesa tu reserva...');
                return false;
            }
            
            // Marcar como enviando y deshabilitar botón
            enviandoReserva = true;
            const btnConfirmar = document.getElementById('btnConfirmarReserva');
            if (btnConfirmar) {
                btnConfirmar.disabled = true;
                btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            }
        });
        
        // Variable para prevenir múltiples envíos
        let enviandoReserva = false;
    </script>
</body>
</html>
