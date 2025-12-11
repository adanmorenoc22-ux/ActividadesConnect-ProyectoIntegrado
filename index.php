<?php
session_start();

// Verificar si los archivos de configuración existen
if (!file_exists('config/database.php')) {
    die('Error: Archivo de configuración no encontrado. Por favor, verifica la instalación.');
}

if (!file_exists('includes/functions.php')) {
    die('Error: Archivo de funciones no encontrado. Por favor, verifica la instalación.');
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si se eliminó una cuenta
$deleteMessage = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $deleteMessage = 'Tu cuenta ha sido eliminada correctamente. Lamentamos verte partir.';
    // Guardar en sesión para que persista
    $_SESSION['account_deleted_message'] = $deleteMessage;
}
// También verificar si hay mensaje en la sesión (para que persista entre páginas)
if (isset($_SESSION['account_deleted_message'])) {
    $deleteMessage = $_SESSION['account_deleted_message'];
}
// Verificar cookie como respaldo
if (empty($deleteMessage) && isset($_COOKIE['account_deleted_message']) && $_COOKIE['account_deleted_message'] == '1') {
    $deleteMessage = 'Tu cuenta ha sido eliminada correctamente. Lamentamos verte partir.';
    $_SESSION['account_deleted_message'] = $deleteMessage;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ActividadesConnect - Conecta con tu próxima aventura</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <?php if ($deleteMessage): ?>
            <div class="container" style="margin-top: 2rem;">
                <div class="alert alert-success no-auto-hide" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 2rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($deleteMessage); ?>
                </div>
            </div>
        <?php endif; ?>
        <section class="hero">
            <div class="hero-content">
                <h1>Conecta con tu próxima aventura</h1>
                <p>Descubre actividades increíbles o comparte tu pasión organizando experiencias únicas</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">Comenzar ahora</a>
                    <a href="actividades.php" class="btn btn-secondary">Explorar actividades</a>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>¿Cómo funciona?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-search"></i>
                        <h3>Busca</h3>
                        <p>Encuentra actividades que se adapten a tus intereses y disponibilidad</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-handshake"></i>
                        <h3>Conecta</h3>
                        <p>Contacta directamente con organizadores de actividades</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-star"></i>
                        <h3>Disfruta</h3>
                        <p>Vive experiencias únicas y crea recuerdos inolvidables</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="categories">
            <div class="container">
                <h2>Categorías de Actividades</h2>
                <div class="categories-grid">
                    <div class="category-card">
                        <i class="fas fa-city"></i>
                        <h3>Turismo Urbano</h3>
                        <p>Recorridos históricos, gastronómicos y culturales</p>
                    </div>
                    <div class="category-card">
                        <i class="fas fa-mountain"></i>
                        <h3>Deporte Aventura</h3>
                        <p>Barranquismo, escalada, senderismo y más</p>
                    </div>
                    <div class="category-card">
                        <i class="fas fa-bicycle"></i>
                        <h3>Rutas BTT</h3>
                        <p>Explora la naturaleza en bicicleta</p>
                    </div>
                    <div class="category-card">
                        <i class="fas fa-gamepad"></i>
                        <h3>Juegos y Entretenimiento</h3>
                        <p>Yincanas, juegos de rol y actividades lúdicas</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
