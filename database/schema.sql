-- ============================================
-- Base de Datos: actividades_connect
-- Sistema: ActividadesConnect
-- Descripción: Script completo de creación de la base de datos
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `actividades_connect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `actividades_connect`;

-- ============================================
-- TABLA: usuarios
-- Descripción: Información base de todos los usuarios del sistema
-- ============================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `tipo` enum('ofertante','consumidor','admin') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` datetime DEFAULT NULL,
  `token_reset` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: ofertantes
-- Descripción: Perfiles específicos de usuarios ofertantes
-- ============================================
CREATE TABLE IF NOT EXISTS `ofertantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `descripcion` text,
  `experiencia` text,
  `certificaciones` text,
  `disponibilidad_general` text,
  `verificado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_verificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_ofertantes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: consumidores
-- Descripción: Perfiles específicos de usuarios consumidores
-- ============================================
CREATE TABLE IF NOT EXISTS `consumidores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `preferencias` text,
  `nivel_experiencia` enum('principiante','intermedio','avanzado') DEFAULT NULL,
  `restricciones_medicas` text,
  `alergias` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_consumidores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: actividades
-- Descripción: Catálogo de actividades ofertadas por los ofertantes
-- ============================================
CREATE TABLE IF NOT EXISTS `actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ofertante_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `subcategoria` varchar(100) DEFAULT NULL,
  `duracion_horas` decimal(4,2) NOT NULL,
  `dificultad` enum('facil','media','dificil','muy_dificil') NOT NULL,
  `precio_persona` decimal(10,2) NOT NULL,
  `precio_grupo` decimal(10,2) DEFAULT NULL,
  `min_participantes` int(11) DEFAULT '1',
  `max_participantes` int(11) DEFAULT '10',
  `lugar_inicio` varchar(255) NOT NULL,
  `lugar_fin` varchar(255) DEFAULT NULL,
  `coordenadas_inicio` varchar(100) DEFAULT NULL,
  `coordenadas_fin` varchar(100) DEFAULT NULL,
  `material_requerido` text,
  `material_incluido` text,
  `preparacion_fisica` text,
  `requisitos_edad_min` int(11) DEFAULT NULL,
  `requisitos_edad_max` int(11) DEFAULT NULL,
  `restricciones` text,
  `incluye_transporte` tinyint(1) NOT NULL DEFAULT '0',
  `incluye_comida` tinyint(1) NOT NULL DEFAULT '0',
  `incluye_seguro` tinyint(1) NOT NULL DEFAULT '0',
  `estado` enum('activa','cancelada','pausada') NOT NULL DEFAULT 'activa',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ofertante` (`ofertante_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_actividades_ofertante` FOREIGN KEY (`ofertante_id`) REFERENCES `ofertantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: disponibilidad_actividades
-- Descripción: Horarios y fechas disponibles para cada actividad
-- ============================================
CREATE TABLE IF NOT EXISTS `disponibilidad_actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `plazas_disponibles` int(11) NOT NULL DEFAULT '1',
  `precio_especial` decimal(10,2) DEFAULT NULL,
  `notas` text,
  `estado` enum('disponible','completo','cancelado') NOT NULL DEFAULT 'disponible',
  PRIMARY KEY (`id`),
  KEY `idx_actividad` (`actividad_id`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_disponibilidad_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: reservas
-- Descripción: Sistema de reservas de actividades por consumidores
-- ============================================
CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumidor_id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `disponibilidad_id` int(11) NOT NULL,
  `fecha_reserva` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actividad` datetime NOT NULL,
  `num_participantes` int(11) NOT NULL DEFAULT '1',
  `precio_total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','confirmada','rechazada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `notas` text,
  `fecha_confirmacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consumidor` (`consumidor_id`),
  KEY `idx_actividad` (`actividad_id`),
  KEY `idx_disponibilidad` (`disponibilidad_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_reservas_consumidor` FOREIGN KEY (`consumidor_id`) REFERENCES `consumidores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reservas_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reservas_disponibilidad` FOREIGN KEY (`disponibilidad_id`) REFERENCES `disponibilidad_actividades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: participantes_reservas
-- Descripción: Nombres de los participantes de cada reserva
-- ============================================
CREATE TABLE IF NOT EXISTS `participantes_reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_reserva` (`reserva_id`),
  CONSTRAINT `fk_participantes_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: solicitudes_consumidores
-- Descripción: Solicitudes personalizadas creadas por consumidores
-- ============================================
CREATE TABLE IF NOT EXISTS `solicitudes_consumidores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumidor_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `fecha_deseada` date NOT NULL,
  `hora_deseada` time DEFAULT NULL,
  `duracion_estimada` decimal(4,2) DEFAULT NULL,
  `presupuesto_max` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `participantes_estimados` int(11) NOT NULL,
  `requisitos_especiales` text,
  `estado` enum('activa','cancelada','completada') NOT NULL DEFAULT 'activa',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_consumidor` (`consumidor_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_fecha_deseada` (`fecha_deseada`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_solicitudes_consumidor` FOREIGN KEY (`consumidor_id`) REFERENCES `consumidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: intereses_ofertantes
-- Descripción: Intereses mostrados por ofertantes en solicitudes
-- ============================================
CREATE TABLE IF NOT EXISTS `intereses_ofertantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ofertante_id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `fecha_interes` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `visto` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_visto` datetime DEFAULT NULL,
  `estado` enum('activo','cancelado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interes` (`ofertante_id`,`solicitud_id`,`estado`),
  KEY `idx_ofertante` (`ofertante_id`),
  KEY `idx_solicitud` (`solicitud_id`),
  CONSTRAINT `fk_intereses_ofertante` FOREIGN KEY (`ofertante_id`) REFERENCES `ofertantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_intereses_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_consumidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: propuestas_ofertantes
-- Descripción: Propuestas enviadas por ofertantes a solicitudes
-- ============================================
CREATE TABLE IF NOT EXISTS `propuestas_ofertantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ofertante_id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `precio_propuesto` decimal(10,2) DEFAULT NULL,
  `fecha_propuesta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `idx_ofertante` (`ofertante_id`),
  KEY `idx_solicitud` (`solicitud_id`),
  CONSTRAINT `fk_propuestas_ofertante` FOREIGN KEY (`ofertante_id`) REFERENCES `ofertantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_propuestas_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_consumidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: mensajes
-- Descripción: Sistema de mensajería entre usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS `mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remitente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `archivado_remitente` tinyint(1) NOT NULL DEFAULT '0',
  `archivado_destinatario` tinyint(1) NOT NULL DEFAULT '0',
  `eliminado_remitente` tinyint(1) NOT NULL DEFAULT '0',
  `eliminado_destinatario` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_remitente` (`remitente_id`),
  KEY `idx_destinatario` (`destinatario_id`),
  KEY `idx_leido` (`leido`),
  KEY `idx_archivado_remitente` (`archivado_remitente`),
  KEY `idx_archivado_destinatario` (`archivado_destinatario`),
  KEY `idx_eliminado_remitente` (`eliminado_remitente`),
  KEY `idx_eliminado_destinatario` (`eliminado_destinatario`),
  CONSTRAINT `fk_mensajes_remitente` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mensajes_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: imagenes
-- Descripción: Imágenes asociadas a actividades y solicitudes
-- ============================================
CREATE TABLE IF NOT EXISTS `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) DEFAULT NULL,
  `solicitud_id` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(500) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `tamaño` int(11) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actividad` (`actividad_id`),
  KEY `idx_solicitud` (`solicitud_id`),
  CONSTRAINT `fk_imagenes_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_imagenes_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_consumidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: configuracion
-- Descripción: Configuración general del sistema
-- ============================================
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text,
  `descripcion` text,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================

