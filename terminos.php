<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-file-contract"></i> Términos y Condiciones</h1>
                    <p>Última actualización: <?php echo date('d/m/Y'); ?></p>
                </div>
                <div class="card-body" style="line-height: 1.8;">
                    <h2>1. Aceptación de los Términos</h2>
                    <p>Al acceder y utilizar ActividadesConnect, usted acepta estar sujeto a estos términos y condiciones de uso. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestro servicio.</p>

                    <h2>2. Descripción del Servicio</h2>
                    <p>ActividadesConnect es una plataforma web que conecta ofertantes de actividades de ocio con consumidores interesados en participar en dichas actividades. La plataforma facilita la comunicación y reserva de actividades, pero no es responsable de la ejecución de las mismas.</p>

                    <h2>3. Registro y Cuentas de Usuario</h2>
                    <h3>3.1 Requisitos de Registro</h3>
                    <ul>
                        <li>Debe ser mayor de 18 años o tener el consentimiento de un tutor legal</li>
                        <li>Proporcionar información veraz y actualizada</li>
                        <li>Mantener la confidencialidad de su cuenta</li>
                        <li>Notificar inmediatamente cualquier uso no autorizado</li>
                    </ul>

                    <h3>3.2 Tipos de Usuario</h3>
                    <ul>
                        <li><strong>Ofertantes:</strong> Personas que organizan y ofrecen actividades</li>
                        <li><strong>Consumidores:</strong> Personas que buscan y reservan actividades</li>
                        <li><strong>Administradores:</strong> Personal de ActividadesConnect</li>
                    </ul>

                    <h2>4. Responsabilidades de los Usuarios</h2>
                    <h3>4.1 Ofertantes</h3>
                    <ul>
                        <li>Proporcionar información precisa sobre las actividades</li>
                        <li>Cumplir con todas las leyes y regulaciones aplicables</li>
                        <li>Mantener seguros a los participantes</li>
                        <li>Honrar las reservas confirmadas</li>
                        <li>Proporcionar materiales y equipos según lo acordado</li>
                    </ul>

                    <h3>4.2 Consumidores</h3>
                    <ul>
                        <li>Respetar las reglas y condiciones de las actividades</li>
                        <li>Pagar las tarifas acordadas</li>
                        <li>Notificar cancelaciones con suficiente antelación</li>
                        <li>Informar sobre restricciones médicas o físicas</li>
                    </ul>

                    <h2>5. Reservas y Pagos</h2>
                    <h3>5.1 Proceso de Reserva</h3>
                    <ul>
                        <li>Las reservas se confirman mediante el sistema de la plataforma</li>
                        <li>Los precios mostrados son finales e incluyen impuestos</li>
                        <li>Las reservas están sujetas a disponibilidad</li>
                    </ul>

                    <h3>5.2 Cancelaciones y Reembolsos</h3>
                    <ul>
                        <li>Las cancelaciones están sujetas a las políticas específicas de cada ofertante</li>
                        <li>Los reembolsos se procesan según las condiciones acordadas</li>
                        <li>ActividadesConnect puede retener una comisión por servicios</li>
                    </ul>

                    <h2>6. Seguridad y Seguros</h2>
                    <ul>
                        <li>Los ofertantes deben tener seguros de responsabilidad civil apropiados</li>
                        <li>Los participantes asisten a las actividades bajo su propio riesgo</li>
                        <li>Se recomienda contratar seguros de viaje para actividades de aventura</li>
                    </ul>

                    <h2>7. Contenido y Propiedad Intelectual</h2>
                    <h3>7.1 Contenido del Usuario</h3>
                    <ul>
                        <li>Los usuarios conservan los derechos sobre su contenido</li>
                        <li>Al subir contenido, otorgan licencia para su uso en la plataforma</li>
                        <li>No se permite contenido ilegal, ofensivo o inapropiado</li>
                    </ul>

                    <h3>7.2 Propiedad de la Plataforma</h3>
                    <ul>
                        <li>ActividadesConnect es propietario de la plataforma y su código</li>
                        <li>Los usuarios no pueden copiar o distribuir el software</li>
                    </ul>

                    <h2>8. Privacidad y Protección de Datos</h2>
                    <p>El tratamiento de datos personales se rige por nuestra <a href="privacidad.php">Política de Privacidad</a>, que forma parte integral de estos términos.</p>

                    <h2>9. Limitación de Responsabilidad</h2>
                    <ul>
                        <li>ActividadesConnect actúa como intermediario entre ofertantes y consumidores</li>
                        <li>No somos responsables de la calidad o seguridad de las actividades</li>
                        <li>Nuestra responsabilidad se limita al funcionamiento de la plataforma</li>
                        <li>Los usuarios utilizan el servicio bajo su propio riesgo</li>
                    </ul>

                    <h2>10. Suspensión y Terminación</h2>
                    <h3>10.1 Suspensión de Cuentas</h3>
                    <ul>
                        <li>Podemos suspender cuentas que violen estos términos</li>
                        <li>Se notificará al usuario antes de la suspensión</li>
                        <li>El usuario puede apelar la decisión</li>
                    </ul>

                    <h3>10.2 Terminación de Cuentas</h3>
                    <ul>
                        <li>Los usuarios pueden cerrar su cuenta en cualquier momento</li>
                        <li>Los datos se eliminarán según la política de retención</li>
                        <li>Las reservas activas deben completarse o cancelarse</li>
                    </ul>

                    <h2>11. Modificaciones</h2>
                    <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Los cambios se notificarán a través de la plataforma y entrarán en vigor inmediatamente.</p>

                    <h2>12. Ley Aplicable y Jurisdicción</h2>
                    <p>Estos términos se rigen por la legislación española. Cualquier disputa será resuelta por los tribunales competentes de Sevilla, España.</p>

                    <h2>13. Contacto</h2>
                    <p>Para preguntas sobre estos términos, puede contactarnos en:</p>
                    <ul>
                        <li><strong>Email:</strong> legal@actividadesconnect.com</li>
                        <li><strong>Dirección:</strong> [Dirección de la empresa]</li>
                        <li><strong>Teléfono:</strong> [Número de teléfono]</li>
                    </ul>

                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 2rem;">
                        <p><strong>Nota:</strong> Estos términos y condiciones están sujetos a cambios. Le recomendamos revisarlos periódicamente para estar informado de cualquier actualización.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
