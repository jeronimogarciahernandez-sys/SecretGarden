<?php
session_start();
require_once 'conexion.php';

// Validar que el comprador esté autenticado
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

if (!$conexion_db || empty($_SESSION['carrito'])) {
    header('Location: index.php');
    exit;
}

$id_comprador = $_SESSION['usuario_id'];
$carrito = $_SESSION['carrito'];

// Obtener los datos de los artículos guardados en la sesión
$ids = implode(',', array_map('intval', array_keys($carrito)));
$sql = "SELECT id_articulo, precio, stock FROM articulo WHERE id_articulo IN ($ids)";
$resultado = $conexion_db->query($sql);

$total = 0;
$items_compra = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $id = $row['id_articulo'];
        $cant = $carrito[$id];
        $precio = $row['precio'];
        
        $total += $precio * $cant;
        $items_compra[] = [
            'id_articulo' => $id,
            'cantidad' => $cant,
            'precio' => $precio
        ];
    }
}

if ($total > 0 && !empty($items_compra)) {
    // 1. Insertar en la tabla 'pedido'
    $sql_pedido = "INSERT INTO pedido (id_comprador, total, estado) VALUES (?, ?, 'Completado')";
    $stmt_p = $conexion_db->prepare($sql_pedido);
    $stmt_p->bind_param("id", $id_comprador, $total);
    
    if ($stmt_p->execute()) {
        $id_pedido = $conexion_db->insert_id;
        $stmt_p->close();

        // 2. Insertar detalles y descontar stock
        $sql_det = "INSERT INTO detalle_pedido (id_pedido, id_articulo, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_d = $conexion_db->prepare($sql_det);

        $sql_stock = "UPDATE articulo SET stock = stock - ? WHERE id_articulo = ?";
        $stmt_s = $conexion_db->prepare($sql_stock);

        foreach ($items_compra as $item) {
            $stmt_d->bind_param("iiid", $id_pedido, $item['id_articulo'], $item['cantidad'], $item['precio']);
            $stmt_d->execute();

            $stmt_s->bind_param("ii", $item['cantidad'], $item['id_articulo']);
            $stmt_s->execute();
        }

        $stmt_d->close();
        $stmt_s->close();

        // Vaciar el carrito de la sesión
        unset($_SESSION['carrito']);

        header('Location: mis_compras.php?msg=exito');
        exit;
    }
}

header('Location: index.php');
exit; 