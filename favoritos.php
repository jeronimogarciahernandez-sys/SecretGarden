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
            ORDER BY f.fecha_agregado DESC";

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
    <title>Mis Favoritos - Secret Garden</title>
    <link rel="stylesheet" href="css/style.css">

</head>
<body>

<header>
    <h1>Secret Garden</h1>
    <div>
        <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span> | 
        <a href="index.php">Volver a la Tienda</a> | 
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</header>

<main>
    <h2>❤️ Mis Productos Favoritos</h2>

    <?php if (!empty($favoritos)): ?>
        <div class="fav-grid">
            <?php foreach ($favoritos as $item): ?>
                <div class="fav-card">
                    <?php $img = !empty($item['imagen']) ? $item['imagen'] : 'default.jpg'; ?>
                    <a href="detalle_articulo.php?id=<?php echo $item['id_articulo']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Producto" >
                        <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                    </a>

                    <p>$<?php echo number_format($item['precio'], 2); ?></p>
                    <small>Tienda: <?php echo htmlspecialchars($item['nombre_tienda'] ?? $item['nombre_vendedor']); ?></small><br>

                    <div>
                        <a href="detalle_articulo.php?id=<?php echo $item['id_articulo']; ?>">Ver detalles</a>
                        
                        <form action="agregar_favorito.php" method="POST">
                            <input type="hidden" name="id_articulo" value="<?php echo $item['id_articulo']; ?>">
                            <button type="submit" class="btn-quitar-fav"> Quitar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aún no has guardado ningún producto en tus favoritos.</p>
    <?php endif; ?>
</main>

</body>
</html>