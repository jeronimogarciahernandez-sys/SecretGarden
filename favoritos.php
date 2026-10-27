<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_comprador = $_SESSION['usuario_id'];
$favoritos = [];

if ($conexion_db) {
    $sql = "SELECT a.*, v.nombre_tienda, v.nombre AS nombre_vendedor, f.fecha_agregado 
            FROM favorito f
            INNER JOIN articulo a ON f.id_articulo = a.id_articulo
            LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor
            WHERE f.id_comprador = ?
            ORDER BY f.fecha_agregado ASC";

    $stmt = $conexion_db->prepare($sql);
    $stmt->bind_param("i", $id_comprador);
    $stmt->execute();
    $favoritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - Secret Garden</title>
    <link rel="stylesheet" href="css/style.css">   
    <link rel="stylesheet" href="css/stylefavoritos.css?v=<?php echo filemtime('css/stylefavoritos.css'); ?>">
    <link rel="stylesheet" href="css/header-favoritos.css">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>

<header>
    <div class="top-bar">
        <div class="brand-container">
            <img src="img/LogoSg.jpeg" alt="Logo de Secret Garden" class="logo">
            <h1>Secret Garden</h1>
        </div>

        <div class="user-session">
            <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>
            <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>
            <a href="index.php">Volver a la Tienda</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>

<main class="favoritos-main">
    <h2>❤️ Mis Productos Favoritos</h2>

    <?php if (!empty($favoritos)): ?>
        <div class="fav-grid">
            <?php foreach ($favoritos as $item): ?>
                <article class="fav-card">
                    <?php $img = !empty($item['imagen']) ? $item['imagen'] : 'default.jpg'; ?>
                    <a href="detalle_articulo.php?id=<?php echo $item['id_articulo']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Producto">
                        <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                    </a>

                    <p class="precio">$<?php echo number_format($item['precio'], 0, ',', '.'); ?> COP</p>
                    <small>Tienda: <?php echo htmlspecialchars($item['nombre_tienda'] ?? $item['nombre_vendedor']); ?></small>

                    <div class="card-acciones">
                        <a href="detalle_articulo.php?id=<?php echo $item['id_articulo']; ?>" class="btn-detalles">Ver detalles</a>
                        
                        <form action="agregar_favorito.php" method="POST">
                            <input type="hidden" name="id_articulo" value="<?php echo $item['id_articulo']; ?>">
                            <button type="submit" class="btn-quitar-fav">Quitar</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="sin-resultados">Aún no has guardado ningún producto en tus favoritos.</p>
    <?php endif; ?>
</main>

<script src="js/script.js"></script>
</body>
</html>