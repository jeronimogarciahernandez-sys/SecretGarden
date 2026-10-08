<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'vendedor') {
    header('Location: login_vendedor.php');
    exit;
}

$id_articulo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_vendedor = $_SESSION['usuario_id'];
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

if ($id_articulo && $conexion_db) {
    // Verificar que el artículo pertenezca al vendedor logueado
    $sql = "DELETE FROM articulo WHERE id_articulo = ? AND id_vendedor = ?";
    $stmt = $conexion_db->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $id_articulo, $id_vendedor);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: panel_vendedor.php?msg=eliminado');
exit;