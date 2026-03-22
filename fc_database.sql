-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-02-2026 a las 03:23:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fc_database`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Bebidas', 'Proveedores de refrescos, jugos, agua y bebidas energéticas.'),
(2, 'Lácteos', 'Proveedores de leche, yogurt, quesos y derivados.'),
(3, 'Panificados y tortillas', 'Pan de caja, bollería, tortillas y harinas.'),
(4, 'Abarrotes generales', 'Productos de abarrotes en general para tienda o supermercado.'),
(5, 'Snacks y botanas', 'Papas, frituras, cacahuates, semillas, etc.'),
(6, 'Limpieza y hogar', 'Productos de limpieza para el hogar y uso comercial.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contact_requests`
--

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado','en_proceso','cerrado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contact_requests`
--

INSERT INTO `contact_requests` (`id`, `cliente_id`, `proveedor_id`, `asunto`, `mensaje`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(2, 7, 4, 'Solicitud de alta como cliente', 'El usuario \'Cliente Demo\' solicita ser dado de alta como cliente del proveedor \'Coca-Cola FEMSA\'. Esta solicitud fue generada automáticamente desde FastContact.', 'aprobado', '2025-11-27 02:54:41', '2025-11-26 18:54:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `cliente_id`, `proveedor_id`, `fecha_pedido`, `estado`, `total`, `notas`) VALUES
(1, 7, 19, '2025-11-27 14:13:18', 'en_proceso', 4102.00, 'Entregar por la mañana, la descarga debe ser por la parte de atrás, por favor.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 27, 10, 14.00, 140.00),
(2, 1, 26, 15, 10.00, 150.00),
(3, 1, 20, 30, 18.00, 540.00),
(4, 1, 21, 25, 32.00, 800.00),
(5, 1, 19, 48, 14.50, 696.00),
(6, 1, 22, 48, 15.00, 720.00),
(7, 1, 24, 24, 14.00, 336.00),
(8, 1, 25, 24, 16.00, 384.00),
(9, 1, 23, 24, 14.00, 336.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provider_categories`
--

CREATE TABLE `provider_categories` (
  `id` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provider_categories`
--

INSERT INTO `provider_categories` (`id`, `nombre_categoria`) VALUES
(1, 'Bebidas'),
(2, 'Lácteos'),
(3, 'Panificados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provider_products`
--

CREATE TABLE `provider_products` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_producto` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `sku_proveedor` varchar(100) DEFAULT NULL,
  `stock_disponible` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provider_products`
--

INSERT INTO `provider_products` (`id`, `proveedor_id`, `nombre_producto`, `descripcion`, `categoria_producto`, `unidad_medida`, `precio_unitario`, `sku_proveedor`, `stock_disponible`, `activo`, `fecha_creacion`) VALUES
(19, 19, 'Coca-Cola 600 ml', 'Refresco de cola, botella PET 600 ml', 'Bebidas', 'botella', 14.50, 'CC-600ML', 200, 1, '2025-11-27 13:54:57'),
(20, 19, 'Coca-Cola 1 L', 'Refresco de cola, botella PET 1 litro retornable', 'Bebidas', 'botella', 18.00, 'CC-1L', 150, 1, '2025-11-27 13:54:57'),
(21, 19, 'Coca-Cola 2.5 L', 'Refresco de cola, botella PET 2.5 litros', 'Bebidas', 'botella', 32.00, 'CC-2_5L', 100, 1, '2025-11-27 13:54:57'),
(22, 19, 'Coca-Cola Light 600 ml', 'Refresco de cola sin azúcar, botella PET 600 ml', 'Bebidas', 'botella', 15.00, 'CCL-600ML', 120, 1, '2025-11-27 13:54:57'),
(23, 19, 'Sprite 600 ml', 'Refresco limón, botella PET 600 ml', 'Bebidas', 'botella', 14.00, 'SPR-600ML', 180, 1, '2025-11-27 13:54:57'),
(24, 19, 'Fanta Naranja 600 ml', 'Refresco sabor naranja, botella PET 600 ml', 'Bebidas', 'botella', 14.00, 'FAN-600ML', 160, 1, '2025-11-27 13:54:57'),
(25, 19, 'Fuze Tea Durazno 600 ml', 'Bebida de té sabor durazno, 600 ml', 'Bebidas', 'botella', 16.00, 'FZT-DUR-600', 90, 1, '2025-11-27 13:54:57'),
(26, 19, 'Agua Ciel 600 ml', 'Agua purificada Ciel, botella PET 600 ml', 'Bebidas', 'botella', 10.00, 'CIEL-600', 250, 1, '2025-11-27 13:54:57'),
(27, 19, 'Agua Ciel 1.5 L', 'Agua purificada Ciel, botella PET 1.5 L', 'Bebidas', 'botella', 14.00, 'CIEL-1_5', 200, 1, '2025-11-27 13:54:57'),
(28, 20, 'Pan Blanco Bimbo Grande', 'Pan blanco rebanado, presentación grande', 'Panificados', 'paquete', 39.00, 'BIM-PAN-BLA-G', 80, 1, '2025-11-27 13:55:08'),
(29, 20, 'Pan Blanco Bimbo Chico', 'Pan blanco rebanado, presentación chica', 'Panificados', 'paquete', 28.00, 'BIM-PAN-BLA-C', 90, 1, '2025-11-27 13:55:08'),
(30, 20, 'Pan Integral Bimbo', 'Pan integral rebanado, alto en fibra', 'Panificados', 'paquete', 42.00, 'BIM-PAN-INT', 70, 1, '2025-11-27 13:55:08'),
(31, 20, 'Pan Tostado Clásico', 'Pan tostado para acompañar comidas', 'Panificados', 'paquete', 30.00, 'BIM-TST-CLAS', 60, 1, '2025-11-27 13:55:08'),
(32, 20, 'Roles de Canela 4 pzas', 'Roles de canela con glaseado, paquete con 4 piezas', 'Pan dulce', 'paquete', 32.00, 'BIM-ROL-CAN', 50, 1, '2025-11-27 13:55:08'),
(33, 20, 'Mantecadas Vainilla', 'Mantecadas sabor vainilla, paquete individual', 'Pan dulce', 'paquete', 25.00, 'BIM-MAN-VAIN', 70, 1, '2025-11-27 13:55:08'),
(34, 20, 'Gansito', 'Panecillo relleno de crema y mermelada cubierto de chocolate', 'Pan dulce', 'pieza', 17.00, 'BIM-GANSITO', 100, 1, '2025-11-27 13:55:08'),
(35, 20, 'Submarinos Vainilla', 'Pan relleno sabor vainilla, paquete con 2 piezas', 'Pan dulce', 'paquete', 24.00, 'BIM-SUB-VAIN', 65, 1, '2025-11-27 13:55:08'),
(36, 20, 'Pan Molido Bimbo', 'Pan molido para empanizar, 200 g', 'Abarrotes', 'bolsa', 22.00, 'BIM-PAN-MOL', 40, 1, '2025-11-27 13:55:08'),
(37, 21, 'Leche Lala Entera 1 L', 'Leche de vaca entera UHT, 1 litro', 'Lácteos', 'caja', 27.00, 'LALA-ENT-1L', 120, 1, '2025-11-27 13:55:18'),
(38, 21, 'Leche Lala Light 1 L', 'Leche baja en grasa, 1 litro', 'Lácteos', 'caja', 28.00, 'LALA-LIG-1L', 100, 1, '2025-11-27 13:55:18'),
(39, 21, 'Leche Lala Deslactosada 1 L', 'Leche deslactosada UHT, 1 litro', 'Lácteos', 'caja', 29.00, 'LALA-DES-1L', 90, 1, '2025-11-27 13:55:18'),
(40, 21, 'Yoghurt Lala Fresa 1 L', 'Bebida láctea sabor fresa, 1 litro', 'Lácteos', 'botella', 35.00, 'LALA-YOG-FR-1L', 60, 1, '2025-11-27 13:55:18'),
(41, 21, 'Yoghurt Lala Durazno 1 L', 'Bebida láctea sabor durazno, 1 litro', 'Lácteos', 'botella', 35.00, 'LALA-YOG-DU-1L', 55, 1, '2025-11-27 13:55:18'),
(42, 21, 'Crema Lala 450 ml', 'Crema ácida Lala, 450 ml', 'Lácteos', 'tarrina', 32.00, 'LALA-CRE-450', 50, 1, '2025-11-27 13:55:18'),
(43, 21, 'Queso Panela Lala 400 g', 'Queso panela fresco, 400 gramos', 'Lácteos', 'pieza', 58.00, 'LALA-Q-PAN-400', 40, 1, '2025-11-27 13:55:18'),
(44, 21, 'Queso Oaxaca Lala 400 g', 'Queso tipo Oaxaca, 400 gramos', 'Lácteos', 'pieza', 65.00, 'LALA-Q-OAX-400', 35, 1, '2025-11-27 13:55:18'),
(45, 21, 'Mantequilla Lala 90 g', 'Mantequilla con sal, barra 90 g', 'Lácteos', 'barra', 18.00, 'LALA-MANT-90', 80, 1, '2025-11-27 13:55:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provider_profiles`
--

CREATE TABLE `provider_profiles` (
  `id` int(11) NOT NULL,
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
  `tipo_proveedor` enum('bebidas','lácteos','panificados','abarrotes','limpieza','otros') DEFAULT 'otros'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provider_profiles`
--

INSERT INTO `provider_profiles` (`id`, `user_id`, `nombre_empresa`, `categoria_id`, `direccion`, `nombre_contacto`, `telefono_contacto`, `sitio_web`, `disponibilidad`, `creado_en`, `actualizado_en`, `tipo_proveedor`) VALUES
(19, 4, 'Coca-Cola FEMSA', 1, 'Parque Industrial, Tijuana, BC', 'Juan Pérez', '664-123-4567', 'https://www.coca-cola.com.mx', 'disponible', '2025-11-26 15:01:13', '2025-11-26 15:01:13', 'bebidas'),
(20, 5, 'Grupo Bimbo', 3, 'Zona Industrial, Tijuana, BC', 'María López', '664-234-5678', 'https://www.bimbo.com.mx', 'disponible', '2025-11-26 15:01:13', '2025-11-26 15:01:13', 'panificados'),
(21, 6, 'Lala', 2, 'Parque Industrial La Mesa, Tijuana, BC', 'Carlos García', '664-345-6789', 'https://www.lala.com.mx', 'disponible', '2025-11-26 15:01:13', '2025-11-26 16:04:49', 'lácteos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('cliente','proveedor','admin') NOT NULL DEFAULT 'cliente',
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre`, `email`, `password_hash`, `rol`, `telefono`, `estado`, `creado_en`, `actualizado_en`) VALUES
(4, 'Juan Pérez', 'juan.cocacola@example.com', 'proveedor123', 'proveedor', '664-123-4567', 'activo', '2025-11-26 14:54:41', '2025-11-26 15:41:24'),
(5, 'María López', 'maria.bimbo@example.com', 'pass_bimbo_hash', 'proveedor', '664-234-5678', 'activo', '2025-11-26 14:54:41', '2025-11-26 14:54:41'),
(6, 'Carlos García', 'carlos.lala@example.com', 'pass_lala_hash', 'proveedor', '664-345-6789', 'activo', '2025-11-26 14:54:41', '2025-11-26 14:54:41'),
(7, 'Cliente Demo', 'cliente.demo@example.com', 'cliente123', 'cliente', NULL, 'activo', '2025-11-26 15:30:50', '2025-11-26 15:30:50');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `provider_categories`
--
ALTER TABLE `provider_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `provider_products`
--
ALTER TABLE `provider_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `contact_requests`
--
ALTER TABLE `contact_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `provider_categories`
--
ALTER TABLE `provider_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `provider_products`
--
ALTER TABLE `provider_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `provider_profiles`
--
ALTER TABLE `provider_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD CONSTRAINT `contact_requests_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_requests_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `provider_products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `provider_products`
--
ALTER TABLE `provider_products`
  ADD CONSTRAINT `provider_products_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD CONSTRAINT `fk_provider_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
