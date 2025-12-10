<?php
// Determinar la ruta base según desde dónde se incluye este archivo
$base_path = '';
$current_dir = dirname($_SERVER['PHP_SELF']);

// Calcular la profundidad para ajustar las rutas
if (strpos($current_dir, '/actividades') !== false || 
    strpos($current_dir, '/admin') !== false || 
    strpos($current_dir, '/mensajes') !== false || 
    strpos($current_dir, '/perfil') !== false || 
    strpos($current_dir, '/propuestas') !== false || 
    strpos($current_dir, '/reservas') !== false || 
    strpos($current_dir, '/solicitudes') !== false ||
    strpos($current_dir, '/ofertantes') !== false) {
    $base_path = '../';
}
?>
<header class="header">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo $base_path; ?>index.php" style="text-decoration: none; color: white;">
                    <h2><i class="fas fa-mountain"></i> ActividadesConnect</h2>
                </a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>index.php" class="nav-link">Inicio</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>actividades.php" class="nav-link">Actividades</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>mensajes/bandeja.php" class="nav-link" style="position: relative;">
                            <i class="fas fa-envelope"></i> Mensajes
                            <?php 
                            $mensajes_no_leidos = getMensajesNoLeidos();
                            if ($mensajes_no_leidos > 0): 
                            ?>
                                <span style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                    <?php echo $mensajes_no_leidos; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>dashboard.php" class="nav-link">Mi Panel</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>logout.php" class="nav-link">Cerrar Sesión</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>login.php" class="nav-link">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>register.php" class="nav-link">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>
</header>
