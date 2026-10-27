<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    exit('');
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

$id_articulo  = filter_input(INPUT_GET, 'articulo', FILTER_VALIDATE_INT);
$id_comprador = filter_input(INPUT_GET, 'comprador', FILTER_VALIDATE_INT);
$rol_actual   = $_SESSION['rol'];

if ($_SESSION['rol'] === 'comprador') {
    $id_comprador = $_SESSION['usuario_id'];
}

if (!$id_articulo || !$id_comprador || !$conexion_db) {
    exit('');
}

// Obtener id_vendedor
$sql_art = "SELECT id_vendedor FROM articulo WHERE id_articulo = ?";
$stmt = $conexion_db->prepare($sql_art);
$stmt->bind_param("i", $id_articulo);
$stmt->execute();
$art = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$art) exit('');
$id_vendedor = $art['id_vendedor'];

// Marcar como leídos los nuevos mensajes recibidos mientras el chat está abierto
$remitente_opuesto = ($rol_actual === 'vendedor') ? 'comprador' : 'vendedor';
$sql_update_leido = "UPDATE mensaje SET leido = 1 
                     WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? AND remitente_tipo = ? AND leido = 0";
$stmt_l = $conexion_db->prepare($sql_update_leido);
$stmt_l->bind_param("iiis", $id_articulo, $id_comprador, $id_vendedor, $remitente_opuesto);
$stmt_l->execute();
$stmt_l->close();

// Cargar historial de mensajes
$sql_msg = "SELECT * FROM mensaje 
            WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? 
            ORDER BY fecha_envio ASC";
$stmt_m = $conexion_db->prepare($sql_msg);
$stmt_m->bind_param("iii", $id_articulo, $id_comprador, $id_vendedor);
$stmt_m->execute();
$mensajes = $stmt_m->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_m->close();

// Renderizar solo los mensajes
foreach ($mensajes as $m): 
    $es_mio = ($m['remitente_tipo'] === $rol_actual); ?>
    <div class="msg <?php echo $es_mio ? 'msg-enviado' : 'msg-recibido'; ?>">
        <?php echo htmlspecialchars($m['contenido']); ?>

        <?php if ($m['es_oferta']): ?>
            <div class="card-oferta">
                <strong>Propuesta de Compra</strong><br>
                <span>Cantidad: <?php echo $m['oferta_cantidad']; ?></span><br>
                <span>Total: $<?php echo number_format($m['oferta_precio'], 2); ?></span><br>
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

        <small><?php echo date('H:i', strtotime($m['fecha_envio'])); ?></small>
    </div>
<?php endforeach; ?>