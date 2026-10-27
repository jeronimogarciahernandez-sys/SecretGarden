-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-10-2026 a las 10:16:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sgardent`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `id_articulo` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` bigint(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT 'default.jpg',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_categoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`id_articulo`, `id_vendedor`, `titulo`, `descripcion`, `precio`, `stock`, `imagen`, `fecha_creacion`, `id_categoria`) VALUES
(39, 4, 'Gomitas mexicanas', 'Gomitas ahogadas \r\nGomitas enchiladas', 5000, 25, '2fe898ec4a9135f8c2d068fe425baf04.webp', '2026-10-18 16:27:08', NULL),
(40, 4, 'Takis', 'Disponible todos los sabores', 4500, 30, '404aa3300ba1298a9763b606681a4733.jpg', '2026-10-18 16:28:22', NULL),
(41, 4, 'Tarro de tajín', 'Rico tarrito de tajín para acompañarlo con lo que mas le gusta', 8000, 10, '02d77b3d5dd82f6f71d1138a45fd3741.webp', '2026-10-18 16:34:31', NULL),
(42, 4, 'Bola de fuego', 'Chicles mexicanos picantes por unidad', 500, 50, '45dd38000ed630318c98f3297574088b.webp', '2026-10-18 16:35:48', NULL),
(43, 4, 'bonbonbum mexicano', 'bonbonbum mexicano picante con polvo de tajín', 2500, 0, '0b7557f164f806cf8c4cae9ec4816896.jpg', '2026-10-18 16:38:53', NULL),
(44, 5, 'Corrector Atenea', 'Disponible todos los tonos', 30000, 45, '19c087aba68ac80be9a9b0443690e9e6.jpg', '2026-10-18 16:52:03', NULL),
(45, 5, 'Polvo translucido Bloomshell', 'Disponible todos los tonos', 25000, 25, '496235bf449de22b91d685251aac71d4.jpg', '2026-10-18 16:54:30', NULL),
(46, 5, 'Iluminador Ani-k', 'Iluminador polvo suelto disponible todos los tonos', 15000, 15, '5c8c3ece64888a865a8570955b23b5ac.jpg', '2026-10-18 16:57:20', NULL),
(47, 6, 'Limoncitas', 'Hay de diferentes sabores \r\nBBQ\r\nLimón\r\nLimón y pimienta', 3500, 45, '1e7675aa163d650f1831ac09662927a7.jpg', '2026-10-18 17:05:33', NULL),
(48, 4, 'alfajores', 'destinos sabores', 2000, 20, '2170dac5e4321984f4c05a98497cb004.jpg', '2026-10-19 21:10:52', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprador`
--

CREATE TABLE `comprador` (
  `id_comprador` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comprador`
--

INSERT INTO `comprador` (`id_comprador`, `nombre`, `email`, `password`, `creado_en`) VALUES
(5, 'Dulce Gomez', 'dulce.mariagomez@inemjose.edu.co', '$2y$10$r.PDYqUvyjhhKUtzcRyA9.ljb0trrftvoEwhfSouwPMqTY5lJw70G', '2026-10-18 16:11:29'),
(7, 'Felipe', 'felipe.diazvega@inemjose.edu.co', '$2y$10$lraGzVFbNFYktpyhqxp7OuHtEBYzGw6XUflAN7ddm5yrqYdP8Kteu', '2026-10-19 20:23:48'),
(8, 'Isabel Madrigal Alvarez', 'isabel.madrigalalvarez@inemjose.edu.co', '$2y$10$UE.NKu9680NDPiy7mYtfYezKKtzcMLALzAlQgg6YwTxntldXRx4Vm', '2026-10-19 21:12:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `id_articulo`, `cantidad`, `precio_unitario`) VALUES
(12, 2, 43, 25, 10000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favorito`
--

CREATE TABLE `favorito` (
  `id_favorito` int(11) NOT NULL,
  `id_comprador` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favorito`
--

INSERT INTO `favorito` (`id_favorito`, `id_comprador`, `id_articulo`, `fecha_agregado`) VALUES
(19, 5, 40, '2026-10-18 13:47:28'),
(20, 5, 47, '2026-10-19 15:39:01'),
(21, 8, 48, '2026-10-19 16:13:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje`
--

CREATE TABLE `mensaje` (
  `id_mensaje` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `id_comprador` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `remitente_tipo` enum('comprador','vendedor') NOT NULL,
  `contenido` text NOT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `es_oferta` tinyint(1) DEFAULT 0,
  `oferta_cantidad` int(11) DEFAULT NULL,
  `oferta_precio` decimal(10,2) DEFAULT NULL,
  `oferta_estado` enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `id_comprador` int(11) NOT NULL,
  `fecha_pedido` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT 'Completado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `id_comprador`, `fecha_pedido`, `total`, `estado`) VALUES
(2, 8, '2026-10-19 16:16:22', 250000.00, 'Completado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion_password`
--

CREATE TABLE `recuperacion_password` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `codigo` varchar(6) NOT NULL,
  `expiracion` datetime NOT NULL,
  `rol` enum('comprador','vendedor') NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recuperacion_password`
--

INSERT INTO `recuperacion_password` (`id`, `email`, `codigo`, `expiracion`, `rol`, `creado_en`) VALUES
(5, 'jeronimo.garciahernandez@inemjose.edu.co', '340510', '2026-10-19 20:50:46', 'vendedor', '2026-10-19 18:35:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedor`
--

CREATE TABLE `vendedor` (
  `id_vendedor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `nombre_tienda` varchar(100) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vendedor`
--

INSERT INTO `vendedor` (`id_vendedor`, `nombre`, `email`, `password`, `telefono`, `nombre_tienda`, `creado_en`) VALUES
(4, 'Jeronimo', 'jeronimo.garciahernandez@inemjose.edu.co', '$2y$10$laeJoNaQM8NWkclbkfwgW./6gGlSbCPKbLJ9hDxgmsgqw1yWk9QLi', '3332630155', 'Dulce Picantico', '2026-10-18 16:14:57'),
(5, 'Isabel', 'isabel.madrigalalvarez@inemjose.edu.co', '$2y$10$OzHZKbCs.xDtP6COAZdL9u6VctHtPwxwz9GkFPQGBZA0Q/U4qMZF.', '3015543225', 'Diva Cosmetics', '2026-10-18 16:43:51'),
(6, 'Paulina', 'maria.gonzalezmunera@inemjose.edu.co', '$2y$10$COpX3hreqmQaGvn7b.M2z.aL7oLCbUukoCHTKnEiQ9YGN0QuzBnPq', '3022359386', 'Papitas Locas', '2026-10-18 17:02:13');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`id_articulo`),
  ADD KEY `id_vendedor` (`id_vendedor`);

--
-- Indices de la tabla `comprador`
--
ALTER TABLE `comprador`
  ADD PRIMARY KEY (`id_comprador`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_articulo` (`id_articulo`);

--
-- Indices de la tabla `favorito`
--
ALTER TABLE `favorito`
  ADD PRIMARY KEY (`id_favorito`),
  ADD UNIQUE KEY `usuario_articulo_unico` (`id_comprador`,`id_articulo`),
  ADD KEY `id_articulo` (`id_articulo`);

--
-- Indices de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_articulo` (`id_articulo`),
  ADD KEY `id_comprador` (`id_comprador`),
  ADD KEY `id_vendedor` (`id_vendedor`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_comprador` (`id_comprador`);

--
-- Indices de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  ADD PRIMARY KEY (`id_vendedor`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulo`
--
ALTER TABLE `articulo`
  MODIFY `id_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `comprador`
--
ALTER TABLE `comprador`
  MODIFY `id_comprador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `favorito`
--
ALTER TABLE `favorito`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  MODIFY `id_vendedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `articulo_ibfk_1` FOREIGN KEY (`id_vendedor`) REFERENCES `vendedor` (`id_vendedor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `favorito`
--
ALTER TABLE `favorito`
  ADD CONSTRAINT `favorito_ibfk_1` FOREIGN KEY (`id_comprador`) REFERENCES `comprador` (`id_comprador`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorito_ibfk_2` FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD CONSTRAINT `mensaje_ibfk_1` FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensaje_ibfk_2` FOREIGN KEY (`id_comprador`) REFERENCES `comprador` (`id_comprador`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensaje_ibfk_3` FOREIGN KEY (`id_vendedor`) REFERENCES `vendedor` (`id_vendedor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_comprador`) REFERENCES `comprador` (`id_comprador`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
