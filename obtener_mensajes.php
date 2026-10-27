<?php
session_start();
require_once 'conexion.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

$id_articulo  = filter_input(INPUT_GET, 'articulo', FILTER_VALIDATE_INT);
$id_comprador = filter_input(INPUT_GET, 'comprador', FILTER_VALIDATE_INT);
$rol_actual   = $_SESSION['rol'];

// SI ES COMPRADOR O SI NO VIENE EL PARÁMETRO GET, USAR EL ID DE LA SESIÓN
if ($rol_actual === 'comprador' || !$id_comprador) {
    if ($rol_actual === 'comprador') {
        $id_comprador = $_SESSION['usuario_id'];
    }
}

if (!$id_articulo || !$id_comprador || !$conexion_db) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

// Obtener ID Vendedor
$stmt = $conexion_db->prepare("SELECT id_vendedor FROM articulo WHERE id_articulo = ?");
$stmt->bind_param("i", $id_articulo);
$stmt->execute();
$art = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$art) {
    echo json_encode(['error' => 'Artículo no encontrado']);
    exit;
}

$id_vendedor = $art['id_vendedor'];

// MARCAR MENSAJES COMO LEÍDOS
$remitente_opuesto = ($rol_actual === 'vendedor') ? 'comprador' : 'vendedor';
$sql_update_leido = "UPDATE mensaje SET leido = 1 
                     WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? AND remitente_tipo = ? AND leido = 0";
$stmt_l = $conexion_db->prepare($sql_update_leido);
$stmt_l->bind_param("iiis", $id_articulo, $id_comprador, $id_vendedor, $remitente_opuesto);
$stmt_l->execute();
$stmt_l->close();

// Cargar Historial
$sql = "SELECT * FROM mensaje 
        WHERE id_articulo = ? AND id_comprador = ? AND id_vendedor = ? 
        ORDER BY fecha_envio ASC";
$stmt_m = $conexion_db->prepare($sql);
$stmt_m->bind_param("iii", $id_articulo, $id_comprador, $id_vendedor);
$stmt_m->execute();
$mensajes = $stmt_m->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_m->close();

echo json_encode([
    'rol_actual' => $rol_actual,
    'mensajes'   => $mensajes
]);