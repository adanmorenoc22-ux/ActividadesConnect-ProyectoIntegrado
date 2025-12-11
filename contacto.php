<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="padding: 3rem 0; min-height: 80vh;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h1><i class="fas fa-envelope"></i> Contacta con Nosotros</h1>
                <p style="color: #6c757d; font-size: 1.1rem;">
                    ¿Tienes alguna pregunta? Estamos aquí para ayudarte
                </p>
            </div>

            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                        <h2 style="color: white; margin: 0;">
                            <i class="fas fa-info-circle"></i> Información de Contacto
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 2rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; color: #2c3e50;">Nombre</h3>
                                    <p style="margin: 0.5rem 0 0 0; color: #6c757d; font-size: 1.1rem;">Adán Moreno</p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; color: #2c3e50;">Email</h3>
                                    <p style="margin: 0.5rem 0 0 0;">
                                        <a href="mailto:adanmorenocarrera@icloud.com" style="color: #667eea; text-decoration: none; font-size: 1.1rem;">
                                            adanmorenocarrera@icloud.com
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; color: #2c3e50;">Teléfono</h3>
                                    <p style="margin: 0.5rem 0 0 0;">
                                        <a href="tel:+34633740089" style="color: #667eea; text-decoration: none; font-size: 1.1rem;">
                                            633 740 089
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr style="margin: 2rem 0;">

                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                            <h4 style="color: #2c3e50; margin-bottom: 1rem;">
                                <i class="fas fa-clock"></i> Horario de Atención
                            </h4>
                            <p style="margin: 0; color: #6c757d; line-height: 1.8;">
                                <strong>Lunes a Viernes:</strong> 9:00 - 18:00<br>
                                <strong>Sábados:</strong> 10:00 - 14:00<br>
                                <strong>Domingos:</strong> Cerrado
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Sobre Nosotros</h3>
                </div>
                <div class="card-body">
                    <p style="color: #6c757d; text-align: center; font-size: 1.1rem; margin: 0;">
                        ActividadesConnect es una plataforma online que conecta ofertantes y consumidores de actividades.
                        Puedes contactarnos utilizando nuestros datos de contacto directos.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>

