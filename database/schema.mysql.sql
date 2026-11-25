-- Estructura relacional basada en el ERD del proyecto.
-- Este archivo prepara una base vacia. Para conservar una base existente, usa las migraciones de Laravel.
-- Las tablas internas de Laravel mantienen sus nombres requeridos por el framework.

CREATE DATABASE IF NOT EXISTS `diego_alquileres`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `diego_alquileres`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `USUARIO` (
  `id_usuario` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `correo` VARCHAR(255) NOT NULL,
  `contrasena` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(20) NOT NULL DEFAULT 'inquilino',
  `nombres` VARCHAR(255) NOT NULL,
  `apellidos` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(30) NULL,
  `dni` VARCHAR(8) NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `correo_verificado_en` TIMESTAMP NULL,
  `token_recordar` VARCHAR(100) NULL,
  `creado_en` TIMESTAMP NULL,
  `actualizado_en` TIMESTAMP NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario_correo_unico` (`correo`),
  UNIQUE KEY `usuario_dni_unico` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `HABITACIONES` (
  `id_habitacion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(10) NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'disponible',
  `creado_en` TIMESTAMP NULL,
  `actualizado_en` TIMESTAMP NULL,
  PRIMARY KEY (`id_habitacion`),
  UNIQUE KEY `habitaciones_numero_unico` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `CONTRATOS` (
  `id_contrato` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NULL,
  `precio_renta` DECIMAL(10,2) NOT NULL,
  `garantia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dia_vencimiento` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
  `id_usuario` BIGINT UNSIGNED NOT NULL,
  `id_habitacion` BIGINT UNSIGNED NOT NULL,
  `creado_en` TIMESTAMP NULL,
  `actualizado_en` TIMESTAMP NULL,
  PRIMARY KEY (`id_contrato`),
  KEY `contratos_usuario_fk` (`id_usuario`),
  KEY `contratos_habitacion_fk` (`id_habitacion`),
  CONSTRAINT `contratos_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT,
  CONSTRAINT `contratos_habitacion_fk` FOREIGN KEY (`id_habitacion`) REFERENCES `HABITACIONES` (`id_habitacion`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FACTURAS` (
  `id_factura` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `monto_total` DECIMAL(10,2) NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_pago` DATE NULL,
  `estado_pago` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  `recargo_mora` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `id_contrato` BIGINT UNSIGNED NOT NULL,
  `creado_en` TIMESTAMP NULL,
  `actualizado_en` TIMESTAMP NULL,
  PRIMARY KEY (`id_factura`),
  KEY `facturas_estado_vencimiento_indice` (`estado_pago`, `fecha_vencimiento`),
  KEY `facturas_contrato_fk` (`id_contrato`),
  CONSTRAINT `facturas_contrato_fk` FOREIGN KEY (`id_contrato`) REFERENCES `CONTRATOS` (`id_contrato`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FACTURA_AGUA` (
  `id_facturaagua` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lectura_anterior` DECIMAL(12,3) NULL,
  `lectura_actual` DECIMAL(12,3) NULL,
  `tarifa_m3` DECIMAL(10,4) NULL,
  `id_factura` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_facturaagua`),
  UNIQUE KEY `factura_agua_factura_unica` (`id_factura`),
  CONSTRAINT `factura_agua_factura_fk` FOREIGN KEY (`id_factura`) REFERENCES `FACTURAS` (`id_factura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FACTURA_LUZ` (
  `id_facturaluz` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lectura_anterior` DECIMAL(12,3) NULL,
  `lectura_actual` DECIMAL(12,3) NULL,
  `tarifa_kwh` DECIMAL(10,4) NULL,
  `id_factura` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_facturaluz`),
  UNIQUE KEY `factura_luz_factura_unica` (`id_factura`),
  CONSTRAINT `factura_luz_factura_fk` FOREIGN KEY (`id_factura`) REFERENCES `FACTURAS` (`id_factura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PAGO` (
  `id_pago` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `monto` DECIMAL(10,2) NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `ruta_voucher` VARCHAR(255) NOT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` TEXT NULL,
  `id_factura` BIGINT UNSIGNED NOT NULL,
  `id_revisor` BIGINT UNSIGNED NULL,
  `creado_en` TIMESTAMP NULL,
  `actualizado_en` TIMESTAMP NULL,
  PRIMARY KEY (`id_pago`),
  KEY `pago_estado_creado_indice` (`estado`, `creado_en`),
  KEY `pago_factura_fk` (`id_factura`),
  KEY `pago_revisor_fk` (`id_revisor`),
  CONSTRAINT `pago_factura_fk` FOREIGN KEY (`id_factura`) REFERENCES `FACTURAS` (`id_factura`) ON DELETE RESTRICT,
  CONSTRAINT `pago_revisor_fk` FOREIGN KEY (`id_revisor`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`(100), `queue`(100), `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
