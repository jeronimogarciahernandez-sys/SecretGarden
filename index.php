<?php
session_start();
require_once 'conexion.php';

$mensaje_carrito = '';

// 1. Manejo de la lógica para agregar productos al carrito (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $id_articulo = filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

    if ($id_articulo && $cantidad && $cantidad > 0) {
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (isset($_SESSION['carrito'][$id_articulo])) {
            $_SESSION['carrito'][$id_articulo] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_articulo] = $cantidad;
        }

        $mensaje_carrito = "Producto agregado al carrito exitosamente.";
    } else {
        $mensaje_carrito = "Cantidad o artículo no válido.";
    }
}

// 2. Obtener total de elementos en el carrito
$total_items = 0;
if (isset($_SESSION['carrito'])) {
    $total_items = array_sum($_SESSION['carrito']);
}

// 3. Consultar artículos disponibles usando MySQLi
$articulos = [];

// Ajusta el nombre de la variable de conexión ($conn o $conexion) según la tengas definida en conexion.php
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

if ($conexion_db) {
    $sql = "SELECT a.*, v.nombre_tienda, v.nombre AS nombre_vendedor 
            FROM articulo a 
            LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
            WHERE a.stock > 0";

    $resultado = $conexion_db->query($sql);

    if ($resultado) {
        // fetch_all con MYSQLI_ASSOC sustituye al fetchAll() de PDO
        $articulos = $resultado->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensaje_carrito = "Error en la consulta: " . $conexion_db->error;
    }
} else {
    $mensaje_carrito = "No se pudo establecer la conexión a la base de datos.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Secret Garden - Modo Comprador</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="top-bar">
            <h1>Secret Garden </h1>
            <div class="user-session">
                <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>
                    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                    
                <?php else: ?>

                <div class="menu-container">
            <span id="miTexto" class="texto-activador">Hola, identifícate ▾</span>
            <ul id="misOpciones" class="opciones-menu">
         <li><a href="login.php">vendedor</a></li>
            <li><a href="login.php">comprador</a></li>

            </ul>
             </div>
                <?php endif; ?>
            </div>
        </div>


            <a href="carrito.php" class="cart-container" title="Ver Carrito">
                <h3>🛒<strong><?php echo $total_items; ?></strong></h3>
                
            </a>

    </header>

    <?php if (!empty($mensaje_carrito)): ?>
        <p class="alert-message"><strong><?php echo $mensaje_carrito; ?></strong></p>
    <?php endif; ?>

    <main>
        <h2>Catálogo de Artículos Disponibles</h2>

        <?php if (count($articulos) > 0): ?>
            <?php foreach ($articulos as $articulo): ?>
                <article>
                    <h3><?php echo htmlspecialchars($articulo['titulo'] ?? $articulo['nombre'] ?? 'Sin título'); ?></h3>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($articulo['descripcion'] ?? ''); ?></p>
                    <p><strong>Precio:</strong> $<?php echo number_format($articulo['precio'] ?? 0, 2); ?></p>
                    <p><strong>Stock disponible:</strong> <?php echo $articulo['stock'] ?? 0; ?></p>
                    <p><strong>Vendido por:</strong> <?php echo htmlspecialchars($articulo['nombre_tienda'] ?? $articulo['nombre_vendedor'] ?? 'Tienda oficial'); ?></p>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo'] ?? $articulo['id']; ?>">
                        
                        <label for="cant_<?php echo $articulo['id_articulo'] ?? $articulo['id']; ?>">Cantidad:</label>
                        <input type="number" 
                            id="cant_<?php echo $articulo['id_articulo'] ?? $articulo['id']; ?>" 
                            name="cantidad" 
                            value="1" 
                            min="1" 
                            max="<?php echo $articulo['stock'] ?? 1; ?>" 
                            required>
                        
                        <button type="submit">🛒 Agregar al Carrito</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay artículos disponibles en este momento.</p>
        <?php endif; ?>
    </main>


 <script src="js/script.js"></script>
</body>
</html>