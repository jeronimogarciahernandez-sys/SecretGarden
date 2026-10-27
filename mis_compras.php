<?php
session_start();
require_once 'conexion.php';

// Validar que la sesión sea de un comprador
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_comprador = $_SESSION['usuario_id'];
$chats_comprador = [];
$mis_pedidos = [];

// Obtener notificaciones de mensajes
$num_no_leidos = 0;
if (function_exists('contarMensajesNoLeidos') && $conexion_db) {
    $num_no_leidos = contarMensajesNoLeidos($conexion_db, $id_comprador, 'comprador');
}

if ($conexion_db) {
    // 1. Consulta para conversaciones del comprador
    $sql_chats = "SELECT DISTINCT m.id_articulo, m.id_vendedor, a.titulo AS producto, 
                         COALESCE(v.nombre_tienda, v.nombre, 'Vendedor') AS vendedor_nombre,
                         (SELECT COUNT(*) FROM mensaje m2 
                          WHERE m2.id_articulo = m.id_articulo 
                            AND m2.id_comprador = ? 
                            AND m2.id_vendedor = m.id_vendedor 
                            AND m2.remitente_tipo = 'vendedor' 
                            AND m2.leido = 0) AS no_leidos
                  FROM mensaje m 
                  INNER JOIN articulo a ON m.id_articulo = a.id_articulo 
                  LEFT JOIN vendedor v ON m.id_vendedor = v.id_vendedor 
                  WHERE m.id_comprador = ?";

    $stmt_c = $conexion_db->prepare($sql_chats);
    if ($stmt_c) {
        $stmt_c->bind_param("ii", $id_comprador, $id_comprador);
        $stmt_c->execute();
        $chats_comprador = $stmt_c->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_c->close();
    }

    // 2. Consulta de pedidos
    $sql_pedidos = "SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, 
                           dp.cantidad, dp.precio_unitario, a.titulo AS producto, a.id_articulo
                    FROM pedido p
                    INNER JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
                    INNER JOIN articulo a ON dp.id_articulo = a.id_articulo
                    WHERE p.id_comprador = ?
                    ORDER BY p.id_pedido DESC";
                    
    $stmt_p = $conexion_db->prepare($sql_pedidos);
    if ($stmt_p) {
        $stmt_p->bind_param("i", $id_comprador);
        $stmt_p->execute();
        $mis_pedidos = $stmt_p->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_p->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras y Mensajes - Secret Garden</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/mis_compras_chats.css">
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
            <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Comprador'); ?></strong></span>
            <a href="index.php" class="btn-compras">Ir a la Tienda</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>

<main class="contenedor-compras">

    <!-- SECCIÓN HISTORIAL DE PEDIDOS Y COMPRAS -->
    <section class="seccion-pedidos-comprador">
        <h2 class="titulo-seccion">🛍️ Historial de Compras</h2>

        <?php if (!empty($mis_pedidos)): ?>
            <div class="tabla-responsive">
                <table class="tabla-pedidos">
                    <thead>
                        <tr>
                            <th># Pedido</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mis_pedidos as $pedido): ?>
                            <tr>
                                <td class="pedido-id">#<?php echo $pedido['id_pedido']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($pedido['producto']); ?></strong></td>
                                <td><?php echo $pedido['cantidad']; ?></td>
                                <td class="precio-total">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?> COP</td>
                                <td><span class="estado-badge estado-<?php echo strtolower($pedido['estado']); ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span></td>
                                <td>
                                    <a href="chat.php?articulo=<?php echo $pedido['id_articulo']; ?>&comprador=<?php echo $id_comprador; ?>" class="btn-accion-chat">
                                        💬 Ver Chat
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="sin-registros">Aún no has realizado ninguna compra.</p>
        <?php endif; ?>
    </section>

    <!-- SECCIÓN DE MENSAJES Y CONVERSACIONES -->
    <section class="seccion-mensajes-comprador">
        <h2 class="titulo-seccion">💬 Mis Mensajes con Vendedores</h2>
        
        <?php if (!empty($chats_comprador)): ?>
            <div class="grid-chats-comprador">
                <?php foreach ($chats_comprador as $chat): ?>
                    <div class="card-chat-comprador">
                        <div class="info-chat">
                            <h3>Vendedor: <?php echo htmlspecialchars($chat['vendedor_nombre']); ?></h3>
                            <p>Producto: <strong><?php echo htmlspecialchars($chat['producto']); ?></strong></p>
                            
                            <?php if ($chat['no_leidos'] > 0): ?>
                                <span class="badge-no-leidos"><?php echo $chat['no_leidos']; ?> nuevo(s)</span>
                            <?php endif; ?>
                        </div>

                        <a href="chat.php?articulo=<?php echo $chat['id_articulo']; ?>&comprador=<?php echo $id_comprador; ?>" class="btn-ir-chat">
                            Ir al Chat
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="sin-registros">Aún no tienes mensajes ni conversaciones iniciadas con vendedores.</p>
        <?php endif; ?>
    </section>

</main>

<script src="js/script.js"></script>
<script src="js/mis_compras.js"></script>
</body>
</html>