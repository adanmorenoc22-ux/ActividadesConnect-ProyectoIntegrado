<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - ActividadesConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="padding: 2rem 0; min-height: 80vh;">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-shield-alt"></i> Política de Privacidad</h1>
                    <p>Última actualización: <?php echo date('d/m/Y'); ?></p>
                </div>
                <div class="card-body" style="line-height: 1.8;">
                    <h2>1. Información General</h2>
                    <p>En ActividadesConnect, nos comprometemos a proteger su privacidad y datos personales. Esta política explica cómo recopilamos, utilizamos y protegemos su información cuando utiliza nuestra plataforma.</p>

                    <h2>2. Responsable del Tratamiento</h2>
                    <ul>
                        <li><strong>Denominación:</strong> ActividadesConnect S.L.</li>
                        <li><strong>NIF:</strong> [Número de identificación fiscal]</li>
                        <li><strong>Dirección:</strong> [Dirección completa]</li>
                        <li><strong>Email:</strong> privacidad@actividadesconnect.com</li>
                        <li><strong>Teléfono:</strong> [Número de teléfono]</li>
                    </ul>

                    <h2>3. Datos que Recopilamos</h2>
                    <h3>3.1 Datos de Registro</h3>
                    <ul>
                        <li>Nombre y apellidos</li>
                        <li>Dirección de correo electrónico</li>
                        <li>Número de teléfono (opcional)</li>
                        <li>Fecha de nacimiento (opcional)</li>
                        <li>Contraseña (encriptada)</li>
                    </ul>

                    <h3>3.2 Datos de Perfil</h3>
                    <ul>
                        <li><strong>Ofertantes:</strong> Descripción, experiencia, certificaciones, disponibilidad</li>
                        <li><strong>Consumidores:</strong> Preferencias, nivel de experiencia, restricciones médicas</li>
                    </ul>

                    <h3>3.3 Datos de Actividad</h3>
                    <ul>
                        <li>Información sobre actividades creadas o reservadas</li>
                        <li>Mensajes y comunicaciones</li>
                        <li>Calificaciones y reseñas</li>
                        <li>Historial de transacciones</li>
                    </ul>

                    <h3>3.4 Datos Técnicos</h3>
                    <ul>
                        <li>Dirección IP</li>
                        <li>Información del navegador</li>
                        <li>Cookies y tecnologías similares</li>
                        <li>Registros de acceso</li>
                    </ul>

                    <h2>4. Finalidades del Tratamiento</h2>
                    <h3>4.1 Finalidades Principales</h3>
                    <ul>
                        <li>Proporcionar y mejorar nuestros servicios</li>
                        <li>Facilitar la conexión entre ofertantes y consumidores</li>
                        <li>Gestionar reservas y pagos</li>
                        <li>Comunicarnos con los usuarios</li>
                        <li>Proporcionar soporte al cliente</li>
                    </ul>

                    <h3>4.2 Finalidades Secundarias</h3>
                    <ul>
                        <li>Análisis y mejora de la plataforma</li>
                        <li>Marketing y promociones (con consentimiento)</li>
                        <li>Cumplimiento de obligaciones legales</li>
                        <li>Prevención de fraudes</li>
                    </ul>

                    <h2>5. Base Legal del Tratamiento</h2>
                    <ul>
                        <li><strong>Ejecución de contrato:</strong> Para proporcionar nuestros servicios</li>
                        <li><strong>Consentimiento:</strong> Para marketing y cookies no esenciales</li>
                        <li><strong>Interés legítimo:</strong> Para mejorar nuestros servicios y prevenir fraudes</li>
                        <li><strong>Cumplimiento legal:</strong> Para cumplir con obligaciones fiscales y legales</li>
                    </ul>

                    <h2>6. Conservación de Datos</h2>
                    <h3>6.1 Plazos de Conservación</h3>
                    <ul>
                        <li><strong>Datos de cuenta:</strong> Hasta que se elimine la cuenta + 1 año</li>
                        <li><strong>Datos de transacciones:</strong> 6 años (obligación fiscal)</li>
                        <li><strong>Datos de marketing:</strong> Hasta retirar el consentimiento</li>
                        <li><strong>Logs de acceso:</strong> 2 años</li>
                    </ul>

                    <h3>6.2 Eliminación de Datos</h3>
                    <p>Los datos se eliminarán de forma segura al finalizar el plazo de conservación, salvo que exista una obligación legal de conservarlos.</p>

                    <h2>7. Compartir Información</h2>
                    <h3>7.1 Terceros Autorizados</h3>
                    <ul>
                        <li>Proveedores de servicios de pago</li>
                        <li>Servicios de hosting y almacenamiento</li>
                        <li>Servicios de análisis y marketing</li>
                        <li>Autoridades competentes (cuando sea requerido por ley)</li>
                    </ul>

                    <h3>7.2 Transferencias Internacionales</h3>
                    <p>Algunos de nuestros proveedores pueden estar ubicados fuera del Espacio Económico Europeo. En estos casos, garantizamos un nivel adecuado de protección mediante:</p>
                    <ul>
                        <li>Decisiones de adecuación de la Comisión Europea</li>
                        <li>Cláusulas contractuales tipo</li>
                        <li>Certificaciones de privacidad reconocidas</li>
                    </ul>

                    <h2>8. Sus Derechos</h2>
                    <h3>8.1 Derechos ARCO-POL</h3>
                    <ul>
                        <li><strong>Acceso:</strong> Conocer qué datos tenemos sobre usted</li>
                        <li><strong>Rectificación:</strong> Corregir datos inexactos</li>
                        <li><strong>Cancelación:</strong> Eliminar sus datos</li>
                        <li><strong>Oposición:</strong> Oponerse al tratamiento</li>
                        <li><strong>Portabilidad:</strong> Recibir sus datos en formato estructurado</li>
                        <li><strong>Limitación:</strong> Restringir el tratamiento</li>
                    </ul>

                    <h3>8.2 Cómo Ejercer sus Derechos</h3>
                    <p>Para ejercer sus derechos, puede contactarnos en:</p>
                    <ul>
                        <li><strong>Email:</strong> privacidad@actividadesconnect.com</li>
                        <li><strong>Formulario:</strong> A través de su panel de usuario</li>
                        <li><strong>Correo postal:</strong> [Dirección completa]</li>
                    </ul>

                    <h2>9. Seguridad de los Datos</h2>
                    <h3>9.1 Medidas Técnicas</h3>
                    <ul>
                        <li>Cifrado de datos en tránsito y en reposo</li>
                        <li>Acceso restringido a datos personales</li>
                        <li>Monitoreo continuo de seguridad</li>
                        <li>Copias de seguridad regulares</li>
                    </ul>

                    <h3>9.2 Medidas Organizativas</h3>
                    <ul>
                        <li>Formación del personal en protección de datos</li>
                        <li>Políticas de confidencialidad</li>
                        <li>Controles de acceso basados en roles</li>
                        <li>Auditorías regulares de seguridad</li>
                    </ul>

                    <h2>10. Cookies y Tecnologías Similares</h2>
                    <h3>10.1 Tipos de Cookies</h3>
                    <ul>
                        <li><strong>Esenciales:</strong> Necesarias para el funcionamiento básico</li>
                        <li><strong>Analíticas:</strong> Para entender el uso de la plataforma</li>
                        <li><strong>Funcionales:</strong> Para recordar preferencias</li>
                        <li><strong>Marketing:</strong> Para personalizar anuncios</li>
                    </ul>

                    <h3>10.2 Gestión de Cookies</h3>
                    <p>Puede gestionar sus preferencias de cookies a través de la configuración de su navegador o nuestro centro de preferencias.</p>

                    <h2>11. Menores de Edad</h2>
                    <p>Nuestros servicios están dirigidos a personas mayores de 18 años. No recopilamos intencionalmente datos de menores sin el consentimiento de sus padres o tutores legales.</p>

                    <h2>12. Cambios en la Política</h2>
                    <p>Nos reservamos el derecho de actualizar esta política de privacidad. Los cambios significativos se notificarán a través de la plataforma o por correo electrónico.</p>

                    <h2>13. Contacto y Reclamaciones</h2>
                    <h3>13.1 Contacto</h3>
                    <p>Para cualquier consulta sobre privacidad:</p>
                    <ul>
                        <li><strong>Email:</strong> privacidad@actividadesconnect.com</li>
                        <li><strong>Teléfono:</strong> [Número de teléfono]</li>
                        <li><strong>Dirección:</strong> [Dirección completa]</li>
                    </ul>

                    <h3>13.2 Autoridad de Control</h3>
                    <p>Si considera que el tratamiento de sus datos no es adecuado, puede presentar una reclamación ante la Agencia Española de Protección de Datos (AEPD):</p>
                    <ul>
                        <li><strong>Web:</strong> www.aepd.es</li>
                        <li><strong>Teléfono:</strong> 901 100 099</li>
                        <li><strong>Dirección:</strong> C/ Jorge Juan, 6, 28001 Madrid</li>
                    </ul>

                    <div style="background: #e8f4fd; padding: 1rem; border-radius: 5px; margin-top: 2rem; border-left: 4px solid #3498db;">
                        <p><strong>Nota importante:</strong> Esta política de privacidad cumple con el Reglamento General de Protección de Datos (RGPD) y la Ley Orgánica de Protección de Datos Personales y garantía de los derechos digitales (LOPDGDD).</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
