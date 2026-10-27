<?php
session_start();
require_once 'conexion.php';

// Validar que la sesión sea de un vendedor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'vendedor') {
    header('Location: login_vendedor.php');
    exit;
}

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_vendedor = $_SESSION['usuario_id'];
$mis_articulos = [];
$mensaje = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'eliminado') $mensaje = "Artículo eliminado correctamente.";
    if ($_GET['msg'] === 'editado') $mensaje = "Artículo actualizado correctamente.";
}

if ($conexion_db) {
    $sql = "SELECT * FROM articulo WHERE id_vendedor = ? ORDER BY id_articulo DESC";
    $stmt = $conexion_db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id_vendedor);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $mis_articulos = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor - Secret Garden</title>
    <link rel="stylesheet" href="css/stylevendedor.css?v=<?php echo filemtime('css/stylevendedor.css'); ?>">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>

   <header>
    <div class="brand-container">
        <img src="img/LogoSg.jpeg" alt="Logo de Secret Garden" class="logo">
        <h1>Secret Garden - Panel de Vendedor</h1>
    </div>
    <div>
        <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button> |
        <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span> | 
        <a href="index.php">Ir a Tienda Principal</a> | 
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</header>
   <?php
// Consulta para obtener mensajes pendientes o chats iniciados por compradores
$sql_chats = "SELECT DISTINCT m.id_articulo, m.id_comprador, a.titulo, c.nombre AS comprador_nombre,
              (SELECT COUNT(*) FROM mensaje m2 
               WHERE m2.id_articulo = m.id_articulo 
                 AND m2.id_comprador = m.id_comprador 
                 AND m2.remitente_tipo = 'comprador' 
                 AND m2.leido = 0) AS no_leidos
              FROM mensaje m 
              INNER JOIN articulo a ON m.id_articulo = a.id_articulo 
              INNER JOIN comprador c ON m.id_comprador = c.id_comprador 
              WHERE m.id_vendedor = ?";
$stmt_c = $conexion_db->prepare($sql_chats);
$stmt_c->bind_param("i", $id_vendedor);
$stmt_c->execute();
$chats_activos = $stmt_c->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_c->close();
?>

<!-- Sección para el Panel de Vendedor -->
<section>
    <h3>Mensajes de Compradores</h3>
    <?php if (!empty($chats_activos)): ?>
        <ul>
            <?php foreach ($chats_activos as $chat): ?>
                <li>
                    <strong><?php echo htmlspecialchars($chat['comprador_nombre']); ?></strong> sobre <em><?php echo htmlspecialchars($chat['titulo']); ?></em> 
                    <?php if ($chat['no_leidos'] > 0): ?>
                        <span class="badge-notif"><?php echo $chat['no_leidos']; ?> nuevo(s)</span>
                    <?php endif; ?>
                    <a href="chat.php?articulo=<?php echo $chat['id_articulo']; ?>&comprador=<?php echo $chat['id_comprador']; ?>">Responder Chat</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No tienes mensajes nuevos de compradores.</p>
    <?php endif; ?>
</section>

    <main>
        <h2>Mis Productos Publicados</h2>
        <a href="publicar_articulo.php" class="btn"> + Publicar Nuevo Artículo</a>

        <?php if (!empty($mensaje)): ?>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <?php if (count($mis_articulos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mis_articulos as $art): ?>
                        <tr>
                            <td>
                                <?php $img = !empty($art['imagen']) ? $art['imagen'] : 'default.jpg'; ?>
                                <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Imagen">
                            </td>
                            <td><strong><?php echo htmlspecialchars($art['titulo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($art['descripcion']); ?></td>
                            <td>$<?php echo number_format($art['precio'], 0, ',', '.'); ?> COP</td>
                            <td><?php echo $art['stock']; ?></td>
                            <td>
                                <a href="editar_articulo.php?id=<?php echo $art['id_articulo']; ?>"> Editar</a>
                                <a href="eliminar_articulo.php?id=<?php echo $art['id_articulo']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este artículo?');"> Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aún no has publicado ningún artículo.</p>
        <?php endif; ?>
    </main>

    <script src="js/detalle.js"></script>
</body>
</html>