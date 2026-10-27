<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

$id_articulo  = filter_input(INPUT_GET, 'articulo', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);
$id_comprador = filter_input(INPUT_GET, 'comprador', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id_comprador', FILTER_VALIDATE_INT);

if ($_SESSION['rol'] === 'comprador') {
    $id_comprador = $_SESSION['usuario_id'];
}

if (!$id_articulo || !$id_comprador || !$conexion_db) {
    header('Location: index.php');
    exit;
}

// 1. PRIMERO: Cargar datos del artículo para obtener el id_vendedor
$sql_art = "SELECT a.titulo, a.precio, a.stock, a.id_vendedor, v.nombre AS nombre_vendedor, v.nombre_tienda 
            FROM articulo a 
            INNER JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
            WHERE a.id_articulo = ?";
$stmt = $conexion_db->prepare($sql_art);
$stmt->bind_param("i", $id_articulo);
$stmt->execute();
$articulo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$articulo) {
    header('Location: index.php');
    exit;
}

// Variables globales necesarias
$id_vendedor = $articulo['id_vendedor'];
$rol_actual  = $_SESSION['rol'];
$mensaje_alerta = '';

// 2. SEGUNDO: Marcar mensajes como leídos
$remitente_opuesto = ($rol_actual === 'vendedor') ? 'comprador' : 'vendedor';
$sql_update_leido = "UPDATE mensaje SET leido = 1 
                     WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? AND remitente_tipo = ? AND leido = 0";
$stmt_l = $conexion_db->prepare($sql_update_leido);
$stmt_l->bind_param("iiis", $id_articulo, $id_comprador, $id_vendedor, $remitente_opuesto);
$stmt_l->execute();
$stmt_l->close();

// 3. TERCERO: Procesar acciones POST del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Enviar mensaje de texto normal
    if ($accion === 'mensaje_normal') {
        $texto = trim($_POST['mensaje_texto'] ?? '');
        if (!empty($texto)) {
            $sql = "INSERT INTO mensaje (id_articulo, id_comprador, id_vendedor, remitente_tipo, contenido) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion_db->prepare($sql);
            $stmt->bind_param("iiiss", $id_articulo, $id_comprador, $id_vendedor, $rol_actual, $texto);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Vendedor envía propuesta de venta
    if ($accion === 'crear_oferta' && $rol_actual === 'vendedor') {
        $cant   = filter_input(INPUT_POST, 'oferta_cantidad', FILTER_VALIDATE_INT);
        $precio = filter_input(INPUT_POST, 'oferta_precio', FILTER_VALIDATE_FLOAT);

        if ($cant && $precio && $cant > 0 && $precio > 0) {
            $precio_formateado = number_format($precio, 0, ',', '.');
            $texto = "Propuesta de Venta: $cant unidad(es) por un total de $$precio_formateado COP";
            $sql = "INSERT INTO mensaje (id_articulo, id_comprador, id_vendedor, remitente_tipo, contenido, es_oferta, oferta_cantidad, oferta_precio, oferta_estado) 
                    VALUES (?, ?, ?, 'vendedor', ?, 1, ?, ?, 'pendiente')";
            $stmt = $conexion_db->prepare($sql);
            $stmt->bind_param("iiisid", $id_articulo, $id_comprador, $id_vendedor, $texto, $cant, $precio);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Comprador acepta la oferta y confirma la compra
    if ($accion === 'aceptar_oferta' && $rol_actual === 'comprador') {
        $id_msg = filter_input(INPUT_POST, 'id_mensaje', FILTER_VALIDATE_INT);

        $sql_o = "SELECT * FROM mensaje WHERE id_mensaje = ? AND oferta_estado = 'pendiente'";
        $stmt_o = $conexion_db->prepare($sql_o);
        $stmt_o->bind_param("i", $id_msg);
        $stmt_o->execute();
        $oferta = $stmt_o->get_result()->fetch_assoc();
        $stmt_o->close();

        if ($oferta) {
            $cant_compra = $oferta['oferta_cantidad'];
            $total_compra = $oferta['oferta_precio'];
            $precio_unitario = $total_compra / $cant_compra;

            if ($articulo['stock'] >= $cant_compra) {
                $sql_p = "INSERT INTO pedido (id_comprador, total, estado) VALUES (?, ?, 'Completado')";
                $stmt_p = $conexion_db->prepare($sql_p);
                $stmt_p->bind_param("id", $id_comprador, $total_compra);
                $stmt_p->execute();
                $id_pedido = $conexion_db->insert_id;
                $stmt_p->close();

                $sql_d = "INSERT INTO detalle_pedido (id_pedido, id_articulo, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
                $stmt_d = $conexion_db->prepare($sql_d);
                $stmt_d->bind_param("iiid", $id_pedido, $id_articulo, $cant_compra, $precio_unitario);
                $stmt_d->execute();
                $stmt_d->close();

                $sql_s = "UPDATE articulo SET stock = stock - ? WHERE id_articulo = ?";
                $stmt_s = $conexion_db->prepare($sql_s);
                $stmt_s->bind_param("ii", $cant_compra, $id_articulo);
                $stmt_s->execute();
                $stmt_s->close();

                $sql_u = "UPDATE mensaje SET oferta_estado = 'aceptada' WHERE id_mensaje = ?";
                $stmt_u = $conexion_db->prepare($sql_u);
                $stmt_u->bind_param("i", $id_msg);
                $stmt_u->execute();
                $stmt_u->close();

                $total_formateado = number_format($total_compra, 0, ',', '.');
                $confirmacion = "¡Compra confirmada! Se registraron $cant_compra unidad(es) por $$total_formateado COP.";
                $sql_c = "INSERT INTO mensaje (id_articulo, id_comprador, id_vendedor, remitente_tipo, contenido) VALUES (?, ?, ?, 'comprador', ?)";
                $stmt_c = $conexion_db->prepare($sql_c);
                $stmt_c->bind_param("iiis", $id_articulo, $id_comprador, $id_vendedor, $confirmacion);
                $stmt_c->execute();
                $stmt_c->close();

                $mensaje_alerta = "¡Compra realizada con éxito!";
            } else {
                $mensaje_alerta = "No hay suficiente stock para completar la compra.";
            }
        }
    }

    // VACIAR CHAT (Borrar mensajes de esta conversación)
    if ($accion === 'vaciar_chat') {
        $sql_del = "DELETE FROM mensaje WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ?";
        $stmt_del = $conexion_db->prepare($sql_del);
        $stmt_del->bind_param("iii", $id_articulo, $id_comprador, $id_vendedor);
        $stmt_del->execute();
        $stmt_del->close();
        $mensaje_alerta = "El historial del chat ha sido vaciado.";
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['status' => 'ok', 'mensaje_alerta' => $mensaje_alerta]);
        exit;
    }

    header("Location: chat.php?articulo=$id_articulo&comprador=$id_comprador");
    exit;
}

// Cargar Historial de Mensajes
$sql_msg = "SELECT * FROM mensaje 
            WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? 
            ORDER BY fecha_envio ASC";
$stmt_m = $conexion_db->prepare($sql_msg);
$stmt_m->bind_param("iii", $id_articulo, $id_comprador, $id_vendedor);
$stmt_m->execute();
$mensajes = $stmt_m->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_m->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat y Negociación - <?php echo htmlspecialchars($articulo['titulo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style4chat.css">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>

<header>
    <div class="brand-container">
        <img src="img/LogoSg.jpeg" alt="Logo de Secret Garden" class="logo">
        <h1>Secret Garden Chat</h1>
    </div>
    <div style="display: flex; align-items: center; gap: 15px;">
        <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>
        <a href="javascript:history.back()">&larr; Volver</a>
    </div>
</header>

<div class="chat-container">
    <div class="chat-header">
        <div class="chat-info">
            <strong>Producto:</strong> <?php echo htmlspecialchars($articulo['titulo']); ?> (Stock: <?php echo $articulo['stock']; ?>)<br>
            <small>Tienda: <?php echo htmlspecialchars($articulo['nombre_tienda'] ?? $articulo['nombre_vendedor']); ?></small>
        </div>
        <!-- Botón Vaciar Chat -->
        <form method="POST" action="chat.php" onsubmit="return confirm('¿Estás seguro de vaciar todo el historial de este chat?');">
            <input type="hidden" name="accion" value="vaciar_chat">
            <input type="hidden" name="id_articulo" value="<?php echo $id_articulo; ?>">
            <input type="hidden" name="id_comprador" value="<?php echo $id_comprador; ?>">
            <button type="submit" class="btn-vaciar-chat">🗑️ Vaciar chat</button>
        </form>
    </div>

    <!-- Alerta del sistema -->
    <?php if (!empty($mensaje_alerta)): ?>
        <div>
            <?php echo htmlspecialchars($mensaje_alerta); ?>
        </div>
    <?php endif; ?>

    <div class="chat-box" id="chatBox">
        <?php foreach ($mensajes as $m): ?>
            <?php $es_mio = ($m['remitente_tipo'] === $rol_actual); ?>
            <div class="msg <?php echo $es_mio ? 'msg-enviado' : 'msg-recibido'; ?>">
                <?php echo htmlspecialchars($m['contenido']); ?>

                <!-- RENDERIZAR TARJETA DE OFERTA -->
                <?php if ($m['es_oferta']): ?>
                    <div class="card-oferta">
                        <strong>Propuesta de Compra</strong><br>
                        <span>Cantidad: <?php echo $m['oferta_cantidad']; ?></span><br>
                        <span>Total: $<?php echo number_format($m['oferta_precio'], 0, ',', '.'); ?> COP</span><br>
                        <span>Estado: <strong><?php echo ucfirst($m['oferta_estado']); ?></strong></span>

                        <?php if ($m['oferta_estado'] === 'pendiente' && $rol_actual === 'comprador'): ?>
                            <form method="POST" action="chat.php">
                                <input type="hidden" name="accion" value="aceptar_oferta">
                                <input type="hidden" name="id_mensaje" value="<?php echo $m['id_mensaje']; ?>">
                                <input type="hidden" name="id_articulo" value="<?php echo $id_articulo; ?>">
                                <input type="hidden" name="id_comprador" value="<?php echo $id_comprador; ?>">
                                <button type="submit" class="btn-aceptar">✓ Aceptar y Confirmar Compra</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <small>
                    <?php echo date('H:i', strtotime($m['fecha_envio'])); ?>
                </small>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FORMULARIO DEL VENDEDOR PARA ENVIAR OFERTA DE VENTA -->
    <?php if ($rol_actual === 'vendedor'): ?>
        <div class="panel-vendedor-oferta">
            <form method="POST" action="chat.php">
                <input type="hidden" name="accion" value="crear_oferta">
                <input type="hidden" name="id_articulo" value="<?php echo $id_articulo; ?>">
                <input type="hidden" name="id_comprador" value="<?php echo $id_comprador; ?>">
                <strong>Enviar Oferta:</strong>
                <input type="number" name="oferta_cantidad" placeholder="Cant." min="1" max="<?php echo $articulo['stock']; ?>" required>
                <input type="number" name="oferta_precio" step="1" min="1" placeholder="Precio ($COP)" required>
                <button type="submit">Enviar Propuesta</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- CHAT TEXTO NORMAL -->
    <form class="chat-form" method="POST" action="chat.php">
        <input type="hidden" name="accion" value="mensaje_normal">
        <input type="hidden" name="id_articulo" value="<?php echo $id_articulo; ?>">
        <input type="hidden" name="id_comprador" value="<?php echo $id_comprador; ?>">
        <input type="text" name="mensaje_texto" class="chat-input" placeholder="Escribe un mensaje..." required autocomplete="off">
        <button type="submit">Enviar</button>
    </form>
</div>

<script src="js/chat.js"></script>

</body>
</html>