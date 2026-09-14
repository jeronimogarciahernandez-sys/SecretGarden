<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$mensaje_carrito = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $id_articulo = (int)$_POST['id_articulo'];
    $cantidad    = (int)$_POST['cantidad'];

    if ($id_articulo > 0 && $cantidad > 0) {
        if (isset($_SESSION['carrito'][$id_articulo])) {
            $_SESSION['carrito'][$id_articulo] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_articulo] = $cantidad;
        }
        $mensaje_carrito = "Artículo agregado al carrito de compras.";
    }
}


$total_items = array_sum($_SESSION['carrito']);

$sql = "SELECT a.id_articulo, a.titulo, a.descripcion, a.precio, a.stock, v.nombre_tienda, v.nombre AS nombre_vendedor 
        FROM articulos a 
        JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
        WHERE a.stock > 0 
        ORDER BY a.creado_en DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Secret Garden - Modo Comprador</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>Tienda Virtual</h1>
        
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <p>
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong> | 
                <a href="login.php">Cerrar Sesión</a>
            </p>
        <?php else: ?>
            <p>
                <a href="login.php">Iniciar Sesión</a> | 
                <a href="registro.php">Registrarse</a>
            </p>
        <?php endif; ?>

        <hr>

        <div>
            <h3>Carrito de Compras</h3>
            <p>Artículos en el carrito: <strong><?php echo $total_items; ?></strong></p>
            <?php if ($total_items > 0): ?>
                <a href="carrito.php">Ver Carrito / Finalizar Compra</a>
            <?php endif; ?>
        </div>
    </header>

    <hr>

    <?php if (!empty($mensaje_carrito)): ?>
        <p><strong><?php echo $mensaje_carrito; ?></strong></p>
        <hr>
    <?php endif; ?>

    <main>
        <h2>Catálogo de Artículos Disponibles</h2>

        <?php if (count($articulos) > 0): ?>
            <?php foreach ($articulos as $articulo): ?>
                <article>
                    <h3><?php echo htmlspecialchars($articulo['titulo']); ?></h3>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($articulo['descripcion']); ?></p>
                    <p><strong>Precio:</strong> $<?php echo number_format($articulo['precio'], 2); ?></p>
                    <p><strong>Stock disponible:</strong> <?php echo $articulo['stock']; ?></p>
                    <p><strong>Vendido por:</strong> <?php echo htmlspecialchars($articulo['nombre_tienda'] ?: $articulo['nombre_vendedor']); ?></p>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">
                        
                        <label for="cant_<?php echo $articulo['id_articulo']; ?>">Cantidad:</label>
                        <input type="number" 
                            id="cant_<?php echo $articulo['id_articulo']; ?>" 
                            name="cantidad" 
                            value="1" 
                            min="1" 
                            max="<?php echo $articulo['stock']; ?>" 
                            required>
                        
                        <button type="submit">Agregar al Carrito</button>
                    </form>
                </article>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay artículos disponibles en este momento.</p>
        <?php endif; ?>
    </main>

</body>
</html>