CREATE DATABASE IF NOT EXISTS taller_mecanico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taller_mecanico;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  dni VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  archivado_en TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio_base DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS turnos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  vehiculo VARCHAR(120) NOT NULL,
  patente VARCHAR(10) NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  estado ENUM('pendiente','confirmado','cancelado','realizado') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (servicio_id) REFERENCES servicios(id)
) ENGINE=InnoDB;

-- Mensajes del formulario de contacto
CREATE TABLE IF NOT EXISTS contactos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(254) NOT NULL,
  mensaje TEXT NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seguimiento del estado del auto
CREATE TABLE IF NOT EXISTS ordenes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  patente VARCHAR(10) NOT NULL,
  vehiculo VARCHAR(120) NOT NULL,
  estado ENUM('ingresado','en_diagnostico','esperando_repuesto','en_reparacion','listo') NOT NULL DEFAULT 'ingresado',
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO servicios (nombre, precio_base) VALUES
 ('Cambio de aceite y filtros', 45000),
 ('Revisión de frenos', 38000),
 ('Alineación y balanceo', 32000),
 ('Diagnóstico por computadora', 25000),
 ('Reparación mecánica general', 70000);

-- Admin inicial: admin@taller.com / Admin1234  (cambiá la clave después de entrar)
INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol) VALUES
 ('Mecánico','Admin','00000000','admin@taller.com','$2y$12$O4/P7.lgU.7.Kz5uivfCYudmQ/tzioJ.zCk7kEjb4JxZQEHXPVMo2','admin');
