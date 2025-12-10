<?php
// Determinar la ruta base
$base_path = '';
$current_dir = dirname($_SERVER['PHP_SELF']);

if (strpos($current_dir, '/actividades') !== false || 
    strpos($current_dir, '/admin') !== false || 
    strpos($current_dir, '/mensajes') !== false || 
    strpos($current_dir, '/perfil') !== false || 
    strpos($current_dir, '/propuestas') !== false || 
    strpos($current_dir, '/reservas') !== false || 
    strpos($current_dir, '/solicitudes') !== false) {
    $base_path = '../';
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>ActividadesConnect</h3>
                <p>Conectando aventuras desde 2025</p>
            </div>
            <div class="footer-section">
                <h4>Enlaces</h4>
                <ul>
                    <li><a href="<?php echo $base_path; ?>actividades.php">Actividades</a></li>
                    <li><a href="<?php echo $base_path; ?>contacto.php">Contacto</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="<?php echo $base_path; ?>privacidad.php">Privacidad</a></li>
                    <li><a href="<?php echo $base_path; ?>terminos.php">Términos</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> ActividadesConnect. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>
