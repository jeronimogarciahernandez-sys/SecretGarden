<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. Manejo de acciones (Actualizar, Eliminar, Vaciar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Actualizar cantidad
    if ($accion === 'actualizar') {
        $id_articulo = filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);
        $nueva_cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

        if ($id_articulo && $nueva_cantidad) {
            if ($nueva_cantidad > 0) {
                $_SESSION['carrito'][$id_articulo] = $nueva_cantidad;
                $mensaje = "Cantidad actualizada correctamente.";
            } else {
                unset($_SESSION['carrito'][$id_articulo]);
                $mensaje = "Artículo eliminado del carrito.";
            }
        }
    }

    // Eliminar un producto específico
    if ($accion === 'eliminar') {
        $id_articulo = filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);
        if ($id_articulo && isset($_SESSION['carrito'][$id_articulo])) {
            unset($_SESSION['carrito'][$id_articulo]);
            $mensaje = "Artículo eliminado del carrito.";
        }
    }

    // Vaciar todo el carrito
    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        $mensaje = "Se ha vaciado el carrito de compras.";
    }
}

// 2. Obtener detalles de los productos presentes en la sesión
$productos_carrito = [];
$total_compra = 0;

if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    // Generar marcadores de posición para la consulta IN (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        $stmt = $pdo->prepare("SELECT id_articulo, titulo, precio, stock FROM articulo WHERE id_articulo IN ($placeholders)");
        $stmt->execute($ids);
        $articulos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($articulos_db as $art) {
            $id = $art['id_articulo'];
            $cant_solicitada = $_SESSION['carrito'][$id];

            // Validar que la cantidad no exceda el stock actual en BD
            $cant_final = min($cant_solicitada, $art['stock']);
            $_SESSION['carrito'][$id] = $cant_final; 

            $subtotal = $art['precio'] * $cant_final;
            $total_compra += $subtotal;

            $productos_carrito[] = [
                'id_articulo' => $id,
                'titulo'      => $art['titulo'],
                'precio'      => $art['precio'],
                'stock'       => $art['stock'],
                'cantidad'    => $cant_final,
                'subtotal'    => $subtotal
            ];
        }
    } catch (PDOException $e) {
        $mensaje = "Error al recuperar los productos: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras - Secret Garden</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="top-bar">
            <h1>🛒 Carrito de Compras</h1>
            <a href="index.php" class="btn-back">⬅️ Volver a la Tienda</a>
        </div>
    </header>

    <main>
        <?php if (!empty($mensaje)): ?>
            <p class="alert-message"><strong><?php echo htmlspecialchars($mensaje); ?></strong></p>
        <?php endif; ?>

        <?php if (!empty($productos_carrito)): ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos_carrito as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['titulo']); ?></td>
                            <td>$<?php echo number_format($item['precio'], 2); ?></td>
                            <td>
                                <form action="carrito.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_articulo" value="<?php echo $item['id_articulo']; ?>">
                                    <input type="number" 
                                           name="cantidad" 
                                           value="<?php echo $item['cantidad']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>" 
                                           required>
                                    <button type="submit">Actualizar</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <form action="carrito.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_articulo" value="<?php echo $item['id_articulo']; ?>">
                                    <button type="submit" onclick="return confirm('¿Eliminar este producto?');">❌ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <h3>Total a Pagar: <strong>$<?php echo number_format($total_compra, 2); ?></strong></h3>
                
                <form action="carrito.php" method="POST" style="display: inline-block;">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" onclick="return confirm('¿Vaciar todo el carrito?');">🗑️ Vaciar Carrito</button>
                </form>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="checkout.php" class="btn-checkout">💳 Proceder al Pago</a>
                <?php else: ?>
                    <p><em>Debes <a href="login.php">iniciar sesión</a> para finalizar la compra.</em></p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p>Tu carrito está vacío.</p>
            <a href="index.php">Ir a ver productos</a>
        <?php endif; ?>
    </main>

</body>
</html>