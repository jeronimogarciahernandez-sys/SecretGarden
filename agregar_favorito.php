<?php
session_start();
require_once 'conexion.php';

// Validar que sea un comprador autenticado
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_comprador = $_SESSION['usuario_id'];
$id_articulo = filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id_articulo', FILTER_VALIDATE_INT);
$origen = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if ($id_articulo && $conexion_db) {
    // Verificar si ya está marcado como favorito
    $sql_check = "SELECT id_favorito FROM favorito WHERE id_comprador = ? AND id_articulo = ?";
    $stmt = $conexion_db->prepare($sql_check);
    $stmt->bind_param("ii", $id_comprador, $id_articulo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Si ya existe, lo eliminamos (toggle off)
        $sql_del = "DELETE FROM favorito WHERE id_comprador = ? AND id_articulo = ?";
        $stmt_del = $conexion_db->prepare($sql_del);
        $stmt_del->bind_param("ii", $id_comprador, $id_articulo);
        $stmt_del->execute();
        $stmt_del->close();
    } else {
        // Si no existe, lo agregamos (toggle on)
        $sql_add = "INSERT INTO favorito (id_comprador, id_articulo) VALUES (?, ?)";
        $stmt_add = $conexion_db->prepare($sql_add);
        $stmt_add->bind_param("ii", $id_comprador, $id_articulo);
        $stmt_add->execute();
        $stmt_add->close();
    }
    $stmt->close();
}

header("Location: $origen");
exit;