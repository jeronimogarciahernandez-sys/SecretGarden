<?php
session_start();
require_once 'conexion.php';

$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_articulo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_articulo || !$conexion_db) {
    header('Location: index.php');
    exit;
}

// Consulta con JOIN para obtener los datos del artículo y la información de contacto del vendedor
$sql = "SELECT a.*, v.nombre AS nombre_vendedor, v.nombre_tienda, v.email AS email_vendedor, v.telefono 
        FROM articulo a 
        INNER JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
        WHERE a.id_articulo = ?";

$stmt = $conexion_db->prepare($sql);
$stmt->bind_param("i", $id_articulo);
$stmt->execute();
$resultado = $stmt->get_result();
$articulo = $resultado->fetch_assoc();
$stmt->close();

// Si el artículo no existe en la BD
if (!$articulo) {
    header('Location: index.php');
    exit;
}

// Formatear el teléfono para el enlace directo de WhatsApp si existe
$telefono_limpio = preg_replace('/[^0-9]/', '', $articulo['telefono'] ?? '');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?> - Secret Garden</title>
    <link rel="stylesheet" href="css/styledetalleA.css?v=<?php echo filemtime('css/stylepublicararticulo.css'); ?>">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">

</head>
<body>

<header>
        <div class="header-logo">
            <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
            <h1>Secret Garden</h1>
            <div class="user-session">
            <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button></div>
        </div>
        <a href="index.php">← Volver a la Tienda</a>
</header>

    <main class="detalle-container">
        <div class="detalle-imagen">
            <?php $img = !empty($articulo['imagen']) ? $articulo['imagen'] : 'default.jpg'; ?>
            <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($articulo['titulo']); ?>">
        </div>

        <div class="detalle-info">
            <h2><?php echo htmlspecialchars($articulo['titulo']); ?></h2>
            <h3>Precio: $<?php echo number_format($articulo['precio'], 0, '.', ','); ?> (COP)</h3>
            <p><strong>Stock disponible:</strong> <?php echo $articulo['stock']; ?> unidades</p>
            <p><strong>Descripción del Producto:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($articulo['descripcion'])); ?></p>

            <!-- Se quitó la sección de Agregar al Carrito y se mantiene Favoritos -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
                <form action="agregar_favorito.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">
                    <button type="submit">
                         Agregar a Favoritos
                    </button>
                </form>
            <?php endif; ?>

            <!-- Sección de información del Vendedor -->
            <div class="vendedor-box">
                <h3>Información del Vendedor</h3>
                <p><strong>Tienda:</strong> <?php echo htmlspecialchars($articulo['nombre_tienda'] ?? 'Sin nombre'); ?></p>
                <p><strong>Vendedor:</strong> <?php echo htmlspecialchars($articulo['nombre_vendedor']); ?></p>
                <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($articulo['email_vendedor']); ?></p>
                
                <?php if (!empty($articulo['telefono'])): ?>
                    <p><strong>Teléfono / Contacto:</strong> <?php echo htmlspecialchars($articulo['telefono']); ?></p>
                    
                    <a href="chat.php?articulo=<?php echo $articulo['id_articulo']; ?>" >
                            Chatear con el vendedor en la página
                    </a>
                    <a href="https://wa.me/57<?php echo $telefono_limpio; ?>?text=Hola,%20estoy%20interesado%20en%20el%20producto:%20<?php echo urlencode($articulo['titulo']); ?>" 
                       target="_blank" 
                       class="btn-whatsapp">
                        Contactar por WhatsApp
                    </a>
                <?php else: ?>
                    <p><em>Este vendedor no ha registrado un teléfono de contacto.</em></p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="js/detalle.js"></script>
</body>
</html>