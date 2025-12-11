<?php
session_start();
require_once 'includes/functions.php';

$asset_version = '2024-12-11-2';

$docs = [
    [
        'title' => 'Tecnologías Utilizadas',
        'icon'  => 'fa-gears',
        'link'  => 'enlace-docs/doc-tecnologias.php',
        'desc'  => 'Stack completo, backend, frontend, base de datos y servidor.'
    ],
    [
        'title' => 'UML',
        'icon'  => 'fa-sitemap',
        'link'  => 'enlace-docs/doc-uml.php',
        'desc'  => 'Diagramas de clases, casos de uso, secuencia y actividad.'
    ],
    [
        'title' => 'Diagrama E/R',
        'icon'  => 'fa-database',
        'link'  => 'enlace-docs/doc-diagrama-er.php',
        'desc'  => 'Modelo entidad-relación detallado con integridad referencial.'
    ],
    [
        'title' => 'Elecciones y Dificultades',
        'icon'  => 'fa-list-check',
        'link'  => 'enlace-docs/doc-elecciones.php',
        'desc'  => 'Decisiones de diseño, problemas encontrados y soluciones.'
    ],
    [
        'title' => 'Manual de Usuario (Ofertante)',
        'icon'  => 'fa-briefcase',
        'link'  => 'enlace-docs/doc-manual-ofertante.php',
        'desc'  => 'Guía paso a paso para organizadores de actividades.'
    ],
    [
        'title' => 'Manual de Usuario (Consumidores)',
        'icon'  => 'fa-users',
        'link'  => 'enlace-docs/doc-manual-consumidor.php',
        'desc'  => 'Guía completa para quienes reservan y solicitan actividades.'
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación Requerida - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $asset_version; ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="docs-main">
        <div class="container">
            <div class="docs-hero">
                <div>
                    <p class="eyebrow"><i class="fas fa-folder-open"></i> Documentación requerida</p>
                    <h1>Explora la documentación</h1>
                    <p class="subtitle">Accede a cada sección en páginas dedicadas, con lectura cuidada y jerarquía clara.</p>
                </div>
                <div class="docs-hero-badges">
                    <span class="pill"><i class="fas fa-book"></i> 6 documentos</span>
                    <span class="pill"><i class="fas fa-eye"></i> Lectura formateada</span>
                    <span class="pill"><i class="fas fa-check-circle"></i> Listo para presentación</span>
                </div>
            </div>

            <div class="docs-cards-grid">
                <?php foreach ($docs as $doc): ?>
                    <article class="doc-link-card">
                        <div class="doc-link-icon">
                            <i class="fas <?php echo $doc['icon']; ?>"></i>
                        </div>
                        <div class="doc-link-body">
                            <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                            <p><?php echo htmlspecialchars($doc['desc']); ?></p>
                        </div>
                        <a class="doc-link-cta" href="<?php echo $doc['link']; ?>">
                            Ver documento <i class="fas fa-arrow-right"></i>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>