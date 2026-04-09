-- phpMyAdmin SQL Dump / FastContact Master DB
-- Generado para persistencia en Docker

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. ESTRUCTURAS DE TABLAS
-- --------------------------------------------------------

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado','en_proceso','cerrado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `provider_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `provider_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_producto` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `sku_proveedor` varchar(100) DEFAULT NULL,
  `stock_disponible` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `provider_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nombre_empresa` varchar(200) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `nombre_contacto` varchar(150) DEFAULT NULL,
  `telefono_contacto` varchar(30) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `disponibilidad` enum('disponible','ocupado','fuera') DEFAULT 'disponible',
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tipo_proveedor` enum('bebidas','lácteos','panificados','abarrotes','limpieza','otros') DEFAULT 'otros',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `solicitudes_proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(200) NOT NULL,
  `nombre_contacto` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_sugerida` varchar(255) NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('cliente','proveedor','admin') NOT NULL DEFAULT 'cliente',
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- 2. VOLCADO DE DATOS (SEEDING)
-- --------------------------------------------------------

INSERT INTO `categories` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Bebidas', 'Proveedores de refrescos, jugos, agua y bebidas energéticas.'),
(2, 'Lácteos', 'Proveedores de leche, yogurt, quesos y derivados.'),
(3, 'Panificados y tortillas', 'Pan de caja, bollería, tortillas y harinas.'),
(4, 'Abarrotes generales', 'Productos de abarrotes en general para tienda o supermercado.'),
(5, 'Snacks y botanas', 'Papas, frituras, cacahuates, semillas, etc.'),
(6, 'Limpieza y hogar', 'Productos de limpieza para el hogar y uso comercial.');

INSERT INTO `provider_categories` (`id`, `nombre_categoria`) VALUES
(1, 'Bebidas'),
(2, 'Lácteos'),
(3, 'Panificados');

-- Usuarios Base (Incluyendo Sabritas y Admin Maestro)
INSERT INTO `users` (`id`, `nombre`, `email`, `password_hash`, `rol`, `telefono`, `estado`) VALUES
(4, 'Juan Pérez', 'juan.cocacola@example.com', 'proveedor123', 'proveedor', '664-123-4567', 'activo'),
(5, 'María López', 'maria.bimbo@example.com', 'pass_bimbo_hash', 'proveedor', '664-234-5678', 'activo'),
(6, 'Carlos García', 'carlos.lala@example.com', 'pass_lala_hash', 'proveedor', '664-345-6789', 'activo'),
(7, 'Cliente Demo', 'cliente.demo@example.com', 'cliente123', 'cliente', NULL, 'activo'),
(8, 'Administrador Maestro', 'admin@test.com', 'admin123', 'admin', '664-000-0000', 'activo'),
(10, 'Sabritas Oficial', 'contacto@sabritas.com.mx', 'sabritas123', 'proveedor', NULL, 'activo');

-- Perfiles de Proveedor
INSERT INTO `provider_profiles` (`id`, `user_id`, `nombre_empresa`, `categoria_id`, `direccion`, `nombre_contacto`, `telefono_contacto`, `sitio_web`, `tipo_proveedor`) VALUES
(19, 4, 'Coca-Cola FEMSA', 1, 'Parque Industrial, Tijuana, BC', 'Juan Pérez', '664-123-4567', 'https://www.coca-cola.com.mx', 'bebidas'),
(20, 5, 'Grupo Bimbo', 3, 'Zona Industrial, Tijuana, BC', 'María López', '664-234-5678', 'https://www.bimbo.com.mx', 'panificados'),
(21, 6, 'Lala', 2, 'Parque Industrial La Mesa, Tijuana, BC', 'Carlos García', '664-345-6789', 'https://www.lala.com.mx', 'lácteos'),
(22, 10, 'Sabritas', NULL, 'Zona Industrial', 'Sabritas Oficial', NULL, NULL, 'abarrotes');

-- Productos Base
INSERT INTO `provider_products` (`id`, `proveedor_id`, `nombre_producto`, `descripcion`, `categoria_producto`, `unidad_medida`, `precio_unitario`, `sku_proveedor`, `stock_disponible`) VALUES
(19, 19, 'Coca-Cola 600 ml', 'Refresco de cola, botella PET 600 ml', 'Bebidas', 'botella', 14.50, 'CC-600ML', 200),
(28, 20, 'Pan Blanco Bimbo Grande', 'Pan blanco rebanado, presentación grande', 'Panificados', 'paquete', 39.00, 'BIM-PAN-BLA-G', 80),
(37, 21, 'Leche Lala Entera 1 L', 'Leche de vaca entera UHT, 1 litro', 'Lácteos', 'caja', 27.00, 'LALA-ENT-1L', 120),
(47, 22, 'Sabritas Originales 45g', 'Papas clásicas', 'Botanas', 'pieza', 18.00, 'SAB-ORI-45-10', 500);

-- Pedido de Ejemplo (Cliente Demo -> Coca-Cola)
INSERT INTO `orders` (`id`, `cliente_id`, `proveedor_id`, `estado`, `total`, `notas`) VALUES
(1, 7, 19, 'en_proceso', 4102.00, 'Entregar por la mañana.');

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 19, 10, 14.50, 145.00);

-- Solicitud de Proveedor Pendiente (Para el panel Admin)
INSERT INTO `solicitudes_proveedores` (`nombre_empresa`, `nombre_contacto`, `email`, `password_sugerida`, `estado`) VALUES
('PepsiCo México', 'Roberto Sanchez', 'nuevo@pepsico.com', 'pepsiSegura2026', 'pendiente');


-- --------------------------------------------------------
-- 3. RESTRICCIONES (CONSTRAINTS)
-- --------------------------------------------------------

ALTER TABLE `contact_requests`
  ADD CONSTRAINT `contact_requests_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_requests_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `provider_products` (`id`) ON DELETE CASCADE;

ALTER TABLE `provider_products`
  ADD CONSTRAINT `provider_products_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

ALTER TABLE `provider_profiles`
  ADD CONSTRAINT `fk_provider_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;