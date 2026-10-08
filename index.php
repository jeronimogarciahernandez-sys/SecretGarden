<?php
session_start();
require_once 'conexion.php';

// Capturar término de búsqueda (GET)
$busqueda = trim($_GET['q'] ?? '');

// Consultar artículos disponibles
$articulos = [];
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

if ($conexion_db) {
    if (!empty($busqueda)) {
        $sql = "SELECT a.*, v.nombre_tienda, v.nombre AS nombre_vendedor 
                FROM articulo a 
                LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
                WHERE a.stock > 0 AND (a.titulo LIKE ? OR a.descripcion LIKE ?)";
        
        $stmt = $conexion_db->prepare($sql);
        $termino = "%" . $busqueda . "%";
        $stmt->bind_param("ss", $termino, $termino);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $articulos = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $sql = "SELECT a.*, v.nombre_tienda, v.nombre AS nombre_vendedor 
                FROM articulo a 
                LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
                WHERE a.stock > 0";
        $resultado = $conexion_db->query($sql);
        if ($resultado) {
            $articulos = $resultado->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Secret Garden - Tienda</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="top-bar">
        <h1>Secret Garden</h1>
        <div class="user-session">
            <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                
                <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'vendedor'): ?>
                    <a href="panel_vendedor.php" class="btn-vendedor">Mi Panel de Vendedor</a>
                <?php elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
                    <a href="mis_compras.php" class="btn-compras"> Mis Compras</a>
                    <a href="registro_vendedor.php" class="btn-convertir-vendedor">¿Quieres vender? Regístrate como vendedor</a>
                <?php endif; ?>

                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>

            <?php else: ?>

                <div class="menu-container">
                    <span id="miTexto" class="texto-activador">Hola, identifícate ▾</span>
                    <ul id="misOpciones" class="opciones-menu">
                        <li><strong>Compradores:</strong></li>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="registro.php">Registrarse</a></li>
                        <hr>
                        <li><strong>Vendedores:</strong></li>
                        <li><a href="login_vendedor.php">Iniciar Sesión</a></li>
                        <li><a href="registro_vendedor.php">Registrarse</a></li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Reemplazado el icono de Carrito por acceso rápido a Favoritos -->
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
        <a href="favoritos.php" class="cart-container" title="Mis Favoritos">
            <h3>Favoritos</h3>
        </a>
    <?php endif; ?>
</header>

    <main>
        <!-- BARRA DE BÚSQUEDA -->
        <div class="search-container">
            <form action="index.php" method="GET" class="search-form">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Buscar plantas, orquídeas, suculentas..." 
                    value="<?php echo htmlspecialchars($busqueda); ?>"
                >
                <button type="submit" class="search-button"> Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="index.php" class="btn-limpiar" title="Limpiar búsqueda">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>
            <?php if (!empty($busqueda)): ?>
                Resultados para: "<?php echo htmlspecialchars($busqueda); ?>"
            <?php else: ?>
                Catálogo de Artículos Disponibles
            <?php endif; ?>
        </h2>

        <?php if (count($articulos) > 0): ?>
            <?php foreach ($articulos as $articulo): ?>
                <article>
                    <?php 
                        $img_nombre = !empty($articulo['imagen']) ? $articulo['imagen'] : 'default.jpg';
                        $img_ruta = 'uploads/' . $img_nombre;
                    ?>
                    <a href="detalle_articulo.php?id=<?php echo $articulo['id_articulo']; ?>" >
                        <img src="<?php echo htmlspecialchars($img_ruta); ?>" alt="<?php echo htmlspecialchars($articulo['titulo'] ?? ''); ?>">
                        <h3><?php echo htmlspecialchars($articulo['titulo'] ?? 'Sin título'); ?></h3>
                    </a>

                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($articulo['descripcion'] ?? ''); ?></p>
                    <p><strong>Precio:</strong> $<?php echo number_format($articulo['precio'] ?? 0, 2); ?></p>
                    <p><strong>Stock disponible:</strong> <?php echo $articulo['stock'] ?? 0; ?></p>
                    <p><strong>Vendido por:</strong> <?php echo htmlspecialchars($articulo['nombre_tienda'] ?? $articulo['nombre_vendedor'] ?? 'Tienda oficial'); ?></p>

                    <a href="detalle_articulo.php?id=<?php echo $articulo['id_articulo']; ?>">
                        Ver detalles y contactar
                    </a>

                    <!-- Reemplazado formulario de Carrito por botón de Agregar a Favoritos -->
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
                        <form action="agregar_favorito.php" method="POST">
                            <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">
                            <button type="submit">
                                Agregar a Favoritos
                            </button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron productos<?php echo !empty($busqueda) ? ' que coincidan con "' . htmlspecialchars($busqueda) . '"' : ''; ?>.</p>
        <?php endif; ?>
    </main>
<script src="js/script.js"></script>
</body>
</html>