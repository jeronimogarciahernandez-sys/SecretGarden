<?php
$host     = "localhost";
$usuario  = "root";
$password = "";          
$database = "Sgardent";  

// Crear la conexión usando MySQLi
$pdo = new mysqli($host, $usuario, $password, $database);

// Verificar la conexión
if ($pdo->connect_error) {
    die("Error al conectar a la base de datos: " . $pdo->connect_error);
}

// Establecer el conjunto de caracteres a utf8
$pdo->set_charset("utf8");

function contarMensajesNoLeidos($conexion_db, $id_usuario, $rol) {
    if (!$conexion_db || !$id_usuario) return 0;

    if ($rol === 'vendedor') {
        // Cuenta mensajes enviados por compradores no leídos aún
        $sql = "SELECT COUNT(*) AS total FROM mensaje WHERE id_vendedor = ? AND remitente_tipo = 'comprador' AND leido = 0";
    } else {
        // Cuenta mensajes enviados por vendedores no leídos aún
        $sql = "SELECT COUNT(*) AS total FROM mensaje WHERE id_comprador = ? AND remitente_tipo = 'vendedor' AND leido = 0";
    }

    $stmt = $conexion_db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res['total'] ?? 0;
    }
    return 0;
}
?>