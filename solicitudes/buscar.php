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

// Parámetros de búsqueda
$categoria = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$presupuesto_min = $_GET['presupuesto_min'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';

// Construir consulta
$whereConditions = ["s.estado = 'activa'"];
$params = [];

if (!empty($categoria)) {
    $whereConditions[] = "s.categoria = ?";
    $params[] = $categoria;
}

if (!empty($busqueda)) {
    $whereConditions[] = "(s.titulo LIKE ? OR s.descripcion LIKE ? OR s.ubicacion LIKE ?)";
    $searchTerm = "%$busqueda%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($presupuesto_min)) {
    $whereConditions[] = "s.presupuesto_max >= ?";
    $params[] = $presupuesto_min;
}

if (!empty($fecha_desde)) {
    $whereConditions[] = "s.fecha_deseada >= ?";
    $params[] = $fecha_desde;
}

$whereClause = implode(' AND ', $whereConditions);

// Obtener ID del ofertante para verificar intereses
$ofertanteQuery = "SELECT id FROM ofertantes WHERE usuario_id = ?";
$ofertanteStmt = $db->prepare($ofertanteQuery);
$ofertanteStmt->execute([$_SESSION['user_id']]);
$ofertante = $ofertanteStmt->fetch(PDO::FETCH_ASSOC);
$ofertante_id = $ofertante['id'];

// Consulta principal
$query = "SELECT s.*, u.nombre as consumidor_nombre, u.apellidos as consumidor_apellidos,
          (SELECT COUNT(*) FROM intereses_ofertantes WHERE solicitud_id = s.id AND estado = 'activo') as total_intereses,
          (SELECT COUNT(*) FROM intereses_ofertantes WHERE solicitud_id = s.id AND ofertante_id = ? AND estado = 'activo') as ya_interesado
          FROM solicitudes_consumidores s
          JOIN consumidores c ON s.consumidor_id = c.id
          JOIN usuarios u ON c.usuario_id = u.id
          WHERE $whereClause
          ORDER BY s.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute(array_merge($params, [$ofertante_id]));
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = getActivityCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Solicitudes - ActividadesConnect</title>
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

            <div style="text-align: center; margin-bottom: 3rem;">
                <h1>Buscar Solicitudes de Clientes</h1>
                <p>Encuentra solicitudes que coincidan con tus servicios</p>
            </div>

            <!-- Filtros -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label for="busqueda">Buscar</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="busqueda" 
                                       name="busqueda" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>"
                                       placeholder="Título, descripción...">
                            </div>

                            <div class="form-group">
                                <label for="categoria">Categoría</label>
                                <select class="form-control" id="categoria" name="categoria">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $categoria === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fecha_desde">Fecha Desde</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_desde" 
                                       name="fecha_desde" 
                                       value="<?php echo htmlspecialchars($fecha_desde); ?>">
                            </div>

                            <div class="form-group">
                                <label for="presupuesto_min">Presupuesto Mín (€)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="presupuesto_min" 
                                       name="presupuesto_min" 
                                       value="<?php echo htmlspecialchars($presupuesto_min); ?>"
                                       min="0" 
                                       step="0.01">
                            </div>
                        </div>

                        <div style="text-align: center;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="buscar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultados -->
            <div style="margin-bottom: 2rem;">
                <h2>Resultados (<?php echo count($solicitudes); ?> solicitudes)</h2>
            </div>

            <?php if (empty($solicitudes)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-search" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No se encontraron solicitudes</h3>
                    <p>Ajusta los filtros o vuelve más tarde</p>
                </div>
            <?php else: ?>
                <div class="activities-grid">
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($solicitud['titulo']); ?></h3>
                            </div>
                            <div class="card-body">
                                <div style="margin-bottom: 1rem;">
                                    <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 15px; font-size: 0.85rem;">
                                        <?php echo $categorias[$solicitud['categoria']] ?? $solicitud['categoria']; ?>
                                    </span>
                                </div>

                                <p style="color: #6c757d; margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 150)) . '...'; ?>
                                </p>

                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <p style="margin-bottom: 0.5rem;"><i class="fas fa-calendar"></i> <strong>Fecha:</strong> <?php echo formatDate($solicitud['fecha_deseada']); ?></p>
                                    <p style="margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt"></i> <strong>Ubicación:</strong> <?php echo htmlspecialchars($solicitud['ubicacion']); ?></p>
                                    <p style="margin-bottom: 0.5rem;"><i class="fas fa-users"></i> <strong>Personas:</strong> <?php echo $solicitud['participantes_estimados']; ?></p>
                                    <?php if ($solicitud['presupuesto_max']): ?>
                                        <p style="margin-bottom: 0;"><i class="fas fa-euro-sign"></i> <strong>Presupuesto:</strong> <?php echo formatPrice($solicitud['presupuesto_max']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($solicitud['total_intereses'] > 0): ?>
                                    <p style="color: #28a745; font-size: 0.9rem;">
                                        <i class="fas fa-heart"></i> 
                                        <?php echo $solicitud['total_intereses']; ?> ofertante(s) interesado(s)
                                    </p>
                                <?php endif; ?>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <small style="color: #6c757d;">
                                        Publicada el: <?php echo formatDate($solicitud['fecha_creacion']); ?>
                                    </small>
                                    <?php if ($solicitud['total_intereses'] > 0): ?>
                                        <small style="color: #28a745; font-weight: 500;">
                                            <i class="fas fa-heart"></i> <?php echo $solicitud['total_intereses']; ?> interesado(s)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="ver.php?id=<?php echo $solicitud['id']; ?>" 
                                       class="btn btn-info" 
                                       style="flex: 1; text-align: center;">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                    <?php if ($solicitud['ya_interesado'] > 0): ?>
                                        <span class="btn btn-secondary" style="flex: 1; text-align: center; cursor: not-allowed;">
                                            <i class="fas fa-check"></i> Ya mostraste interés
                                        </span>
                                    <?php else: ?>
                                        <a href="interes.php?solicitud_id=<?php echo $solicitud['id']; ?>" 
                                           class="btn btn-success" 
                                           style="flex: 1; text-align: center;"
                                           onclick="return confirm('¿Mostrar interés en esta solicitud? El consumidor recibirá una notificación.');">
                                            <i class="fas fa-heart"></i> Me Interesa
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
</body>
</html>
