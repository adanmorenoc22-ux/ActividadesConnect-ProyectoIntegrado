<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Parámetros de búsqueda
$categoria = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$dificultad = $_GET['dificultad'] ?? '';
$fecha = $_GET['fecha'] ?? '';

// Construir consulta
$whereConditions = ["a.estado = 'activa'"];
$params = [];

if (!empty($categoria)) {
    $whereConditions[] = "a.categoria = ?";
    $params[] = $categoria;
}

if (!empty($busqueda)) {
    $whereConditions[] = "(a.titulo LIKE ? OR a.descripcion LIKE ? OR a.lugar_inicio LIKE ?)";
    $searchTerm = "%$busqueda%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($precio_min)) {
    $whereConditions[] = "a.precio_persona >= ?";
    $params[] = $precio_min;
}

if (!empty($precio_max)) {
    $whereConditions[] = "a.precio_persona <= ?";
    $params[] = $precio_max;
}

if (!empty($dificultad)) {
    $whereConditions[] = "a.dificultad = ?";
    $params[] = $dificultad;
}

if (!empty($fecha)) {
    $whereConditions[] = "EXISTS (SELECT 1 FROM disponibilidad_actividades da WHERE da.actividad_id = a.id AND DATE(da.fecha_inicio) = ? AND da.estado = 'disponible')";
    $params[] = $fecha;
}

$whereClause = implode(' AND ', $whereConditions);

// Consulta principal
$query = "SELECT a.*, u.nombre as ofertante_nombre, u.apellidos as ofertante_apellidos
          FROM actividades a
          JOIN ofertantes o ON a.ofertante_id = o.id
          JOIN usuarios u ON o.usuario_id = u.id
          WHERE $whereClause
          ORDER BY a.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$categorias = getActivityCategories();
$niveles_dificultad = getDifficultyLevels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h1>Descubre Actividades Increíbles</h1>
                <p>Encuentra la aventura perfecta para ti</p>
            </div>
            
            <!-- Filtros de búsqueda -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
                </div>
                <div class="card-body">
                    <form method="GET" id="searchForm">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label for="busqueda">Buscar</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="busqueda" 
                                       name="busqueda" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>"
                                       placeholder="Título, descripción, lugar...">
                            </div>
                            
                            <div class="form-group">
                                <label for="categoria">Categoría</label>
                                <select class="form-control" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $categoria === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dificultad">Dificultad</label>
                                <select class="form-control" id="dificultad" name="dificultad">
                                    <option value="">Todas las dificultades</option>
                                    <?php foreach ($niveles_dificultad as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $dificultad === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha">Fecha</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha" 
                                       name="fecha" 
                                       value="<?php echo htmlspecialchars($fecha); ?>">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label for="precio_min">Precio Mínimo (€)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="precio_min" 
                                       name="precio_min" 
                                       value="<?php echo htmlspecialchars($precio_min); ?>"
                                       min="0" 
                                       step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="precio_max">Precio Máximo (€)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="precio_max" 
                                       name="precio_max" 
                                       value="<?php echo htmlspecialchars($precio_max); ?>"
                                       min="0" 
                                       step="0.01">
                            </div>
                        </div>
                        
                        <div style="text-align: center;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="actividades.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resultados -->
            <div style="margin-bottom: 2rem;">
                <h2>Resultados (<?php echo count($actividades); ?> actividades encontradas)</h2>
            </div>
            
            <?php if (empty($actividades)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-search" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No se encontraron actividades</h3>
                    <p>Intenta ajustar los filtros de búsqueda o explora todas las categorías</p>
                    <a href="actividades.php" class="btn btn-primary">Ver Todas las Actividades</a>
                </div>
            <?php else: ?>
                <div class="activities-grid">
                    <?php foreach ($actividades as $actividad): ?>
                        <div class="activity-card">
                            <div class="activity-image">
                                <i class="fas fa-mountain"></i>
                            </div>
                            <div class="activity-content">
                                <h3 class="activity-title"><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
                                <div class="activity-category">
                                    <?php echo $categorias[$actividad['categoria']] ?? $actividad['categoria']; ?>
                                </div>
                                <p class="activity-description">
                                    <?php echo htmlspecialchars(substr($actividad['descripcion'], 0, 150)) . '...'; ?>
                                </p>
                                <div class="activity-meta">
                                    <div>
                                        <strong><?php echo formatPrice($actividad['precio_persona']); ?></strong>
                                        <small>/persona</small>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock"></i>
                                        <?php echo $actividad['duracion_horas']; ?>h
                                    </div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i>
                                        <span><?php echo htmlspecialchars($actividad['lugar_inicio']); ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <i class="fas fa-signal" style="color: #667eea;"></i>
                                        <span><?php echo $niveles_dificultad[$actividad['dificultad']] ?? $actividad['dificultad']; ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-users" style="color: #667eea;"></i>
                                        <span>Plazas según fecha disponible</span>
                                    </div>
                                </div>
                            </div>
                            <div class="activity-footer">
                                <div>
                                    <strong>Por:</strong> <?php echo htmlspecialchars($actividad['ofertante_nombre'] . ' ' . $actividad['ofertante_apellidos']); ?>
                                </div>
                                <div>
                                    <a href="actividades/ver.php?id=<?php echo $actividad['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
