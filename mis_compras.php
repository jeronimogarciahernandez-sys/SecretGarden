<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_comprador = $_SESSION['usuario_id'];
$compras = [];

if ($conexion_db) {
    $sql = "SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado,
                   dp.cantidad, dp.precio_unitario,
                   a.titulo, a.imagen, v.nombre_tienda
            FROM pedido p
            INNER JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
            INNER JOIN articulo a ON dp.id_articulo = a.id_articulo
            LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor
            WHERE p.id_comprador = ?
            ORDER BY p.fecha_pedido DESC, p.id_pedido DESC";

    $stmt = $conexion_db->prepare($sql);
    $stmt->bind_param("i", $id_comprador);
    $stmt->execute();
    $res = $stmt->get_result();

    // Agrupar los detalles por ID de pedido
    while ($row = $res->fetch_assoc()) {
        $id_p = $row['id_pedido'];
        if (!isset($compras[$id_p])) {
            $compras[$id_p] = [
                'fecha' => $row['fecha_pedido'],
                'total' => $row['total'],
                'estado' => $row['estado'],
                'items' => []
            ];
        }
        $compras[$id_p]['items'][] = [
            'titulo' => $row['titulo'],
            'imagen' => $row['imagen'],
            'tienda' => $row['nombre_tienda'],
            'cantidad' => $row['cantidad'],
            'precio_unitario' => $row['precio_unitario']
        ];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Compras - Secret Garden</title>
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
    <h2>Mis Compras Realizadas</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'exito'): ?>
        <p>¡Compra realizada con éxito! Gracias por tu pedido.</p>
    <?php endif; ?>

    <?php if (!empty($compras)): ?>
        <?php foreach ($compras as $id_pedido => $pedido): ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <div>
                        <strong>Pedido #<?php echo $id_pedido; ?></strong><br>
                        <small>Fecha: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></small>
                    </div>
                    <div>
                        <span>Estado: <strong><?php echo htmlspecialchars($pedido['estado']); ?></strong></span><br>
                        <strong>Total: $<?php echo number_format($pedido['total'], 2); ?></strong>
                    </div>
                </div>

                <div class="pedido-items">
                    <?php foreach ($pedido['items'] as $item): ?>
                        <div class="item-row">
                            <?php $img = !empty($item['imagen']) ? $item['imagen'] : 'default.jpg'; ?>
                            <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Producto">
                            <div >
                                <strong><?php echo htmlspecialchars($item['titulo']); ?></strong><br>
                                <small>Vendido por: <?php echo htmlspecialchars($item['tienda'] ?? 'Tienda Oficial'); ?></small>
                            </div>
                            <div>
                                Cantidad: <?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio_unitario'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aún no has realizado ninguna compra.</p>
    <?php endif; ?>
</main>

</body>
</html>