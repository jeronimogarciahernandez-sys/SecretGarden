<?php
session_start();
require_once 'conexion.php';

// 1. Definir la conexión
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

// 2. Contar total general de mensajes no leídos
$num_no_leidos = contarMensajesNoLeidos($conexion_db, $_SESSION['usuario_id'] ?? 0, $_SESSION['rol'] ?? '');

// Capturar parámetros GET
$busqueda   = trim($_GET['q'] ?? '');
$precio_min = filter_input(INPUT_GET, 'precio_min', FILTER_VALIDATE_FLOAT);
$precio_max = filter_input(INPUT_GET, 'precio_max', FILTER_VALIDATE_FLOAT);
$orden      = $_GET['orden'] ?? 'recientes';

// CONFIGURACIÓN DE PAGINACIÓN
$por_pagina = 8;
$pagina     = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
if ($pagina < 1) {
    $pagina = 1;
}
$offset = ($pagina - 1) * $por_pagina;

$articulos = [];
$total_articulos = 0;
$total_paginas = 1;

if ($conexion_db) {
    $conditions = ["a.stock > 0"];
    $params = [];
    $types = "";

    if (!empty($busqueda)) {
        $conditions[] = "(a.titulo LIKE ? OR a.descripcion LIKE ?)";
        $termino = "%" . $busqueda . "%";
        $params[] = $termino;
        $params[] = $termino;
        $types .= "ss";
    }

    if ($precio_min !== false && $precio_min !== null) {
        $conditions[] = "a.precio >= ?";
        $params[] = $precio_min;
        $types .= "d";
    }

    if ($precio_max !== false && $precio_max !== null) {
        $conditions[] = "a.precio <= ?";
        $params[] = $precio_max;
        $types .= "d";
    }

    $orderBy = "a.id_articulo DESC";
    if ($orden === 'precio_asc') {
        $orderBy = "a.precio ASC";
    } elseif ($orden === 'precio_desc') {
        $orderBy = "a.precio DESC";
    } elseif ($orden === 'nombre') {
        $orderBy = "a.titulo ASC";
    }

    $whereClause = implode(" AND ", $conditions);

    // TOTAL DE REGISTROS
    $sql_count = "SELECT COUNT(*) as total FROM articulo a WHERE $whereClause";
    $stmt_count = $conexion_db->prepare($sql_count);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $res_count = $stmt_count->get_result()->fetch_assoc();
    $total_articulos = $res_count['total'] ?? 0;
    $stmt_count->close();

    $total_paginas = ceil($total_articulos / $por_pagina);
    if ($total_paginas < 1) {
        $total_paginas = 1;
    }

    // OBTENER ARTÍCULOS
    $sql = "SELECT a.*, v.nombre_tienda, v.nombre AS nombre_vendedor 
            FROM articulo a 
            LEFT JOIN vendedor v ON a.id_vendedor = v.id_vendedor 
            WHERE $whereClause 
            ORDER BY $orderBy 
            LIMIT ? OFFSET ?";

    $types_limit = $types . "ii";
    $params_limit = array_merge($params, [$por_pagina, $offset]);

    $stmt = $conexion_db->prepare($sql);
    $stmt->bind_param($types_limit, ...$params_limit);
    $stmt->execute();
    $articulos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 3. CONSULTA DE CHATS ACTIVOS DEL USUARIO LOGUEADO (SIN RESTRICCIÓN DE MENSAJES NO LEÍDOS)
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$rol_actual = $_SESSION['rol'] ?? '';
$chats_activos = [];

if ($usuario_id > 0 && $conexion_db) {
    if ($rol_actual === 'vendedor') {
        // VENDEDOR: Obtener todos los chats iniciados por compradores
        $sql_chats = "SELECT DISTINCT m.id_articulo, m.id_comprador, a.titulo AS producto, 
                             COALESCE(c.nombre, 'Comprador') AS otro_usuario, 
                             (SELECT COUNT(*) FROM mensaje m2 
                              WHERE m2.id_articulo = m.id_articulo 
                                AND m2.id_comprador = m.id_comprador 
                                AND m2.id_vendedor = ? 
                                AND m2.remitente_tipo = 'comprador' 
                                AND m2.leido = 0) AS unread_count
                      FROM mensaje m
                      INNER JOIN articulo a ON m.id_articulo = a.id_articulo
                      LEFT JOIN comprador c ON m.id_comprador = c.id_comprador
                      WHERE m.id_vendedor = ?";
        $stmt_c = $conexion_db->prepare($sql_chats);
        $stmt_c->bind_param("ii", $usuario_id, $usuario_id);
    } else if ($rol_actual === 'comprador') {
        // COMPRADOR: Obtener todos los chats iniciados con vendedores
        $sql_chats = "SELECT DISTINCT m.id_articulo, m.id_comprador, a.titulo AS producto, 
                             COALESCE(v.nombre_tienda, v.nombre, 'Vendedor') AS otro_usuario, 
                             (SELECT COUNT(*) FROM mensaje m2 
                              WHERE m2.id_articulo = m.id_articulo 
                                AND m2.id_comprador = ? 
                                AND m2.id_vendedor = m.id_vendedor 
                                AND m2.remitente_tipo = 'vendedor' 
                                AND m2.leido = 0) AS unread_count
                      FROM mensaje m
                      INNER JOIN articulo a ON m.id_articulo = a.id_articulo
                      LEFT JOIN vendedor v ON m.id_vendedor = v.id_vendedor
                      WHERE m.id_comprador = ?";
        $stmt_c = $conexion_db->prepare($sql_chats);
        $stmt_c->bind_param("ii", $usuario_id, $usuario_id);
    }

    if (isset($stmt_c) && $stmt_c) {
        $stmt_c->execute();
        $chats_activos = $stmt_c->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_c->close();
    }
}

$hay_filtros = !empty($busqueda) || $precio_min || $precio_max || $orden !== 'recientes';

$query_params = $_GET;
unset($query_params['pagina']);
$query_string = http_build_query($query_params);
$link_base = "index.php?" . ($query_string ? $query_string . "&" : "");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Adaptación para los celulares -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secret Garden - Tienda</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/widget_chat.css">
<link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>

<header>
    <div class="top-bar">
        <div class="brand-container">
            <img src="img/LogoSg.jpeg" alt="Logo de Secret Garden" class="logo">
            <h1>Secret Garden</h1>
        </div>

        <div class="user-session">
            <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'vendedor'): ?>
                    <a href="panel_vendedor.php" class="btn-vendedor">
                        Mi Panel de Vendedor
                        <?php if ($num_no_leidos > 0): ?>
                            <span class="badge-notif"><?php echo $num_no_leidos; ?></span>
                        <?php endif; ?>
                    </a>
                <?php elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
                    <a href="mis_compras.php" class="btn-compras"> 
                        Mis Compras
                        <?php if ($num_no_leidos > 0): ?>
                            <span class="badge-notif"><?php echo $num_no_leidos; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="registro_vendedor.php" class="btn-convertir-vendedor">¿Quieres vender? Regístrate como vendedor</a>
                <?php endif; ?>

                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>

            <?php else: ?>
                <div class="menu-container">
                    <span id="miTexto" class="texto-activador">Hola👋, identifícate ▾</span>
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

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
        <a href="favoritos.php" class="cart-container" title="Mis Favoritos">
            <h3>Favoritos</h3>
        </a>
    <?php endif; ?>
</header>

<main>
    <div class="search-and-filter-wrapper">
        <form action="index.php" method="GET" class="main-filter-form">
            <div class="search-box">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Buscar productos..." 
                    value="<?php echo htmlspecialchars($busqueda); ?>"
                >
                <button type="submit" class="search-button">Buscar</button>
            </div>

            <details class="dropdown-filtros">
                <summary class="btn-toggle-filtros">⚙ Filtros</summary>
                <div class="filtros-contenido">
                    <div class="filtro-campo">
                        <label for="precio_min">Precio Mínimo ($):</label>
                        <input type="number" step="0.01" min="0" id="precio_min" name="precio_min" placeholder="0.00" value="<?php echo $precio_min !== false ? htmlspecialchars($precio_min) : ''; ?>">
                    </div>

                    <div class="filtro-campo">
                        <label for="precio_max">Precio Máximo ($):</label>
                        <input type="number" step="0.01" min="0" id="precio_max" name="precio_max" placeholder="Sin límite" value="<?php echo $precio_max !== false ? htmlspecialchars($precio_max) : ''; ?>">
                    </div>

                    <div class="filtro-campo">
                        <label for="orden">Ordenar por:</label>
                        <select name="orden" id="orden">
                            <option value="recientes" <?php echo ($orden === 'recientes') ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="precio_asc" <?php echo ($orden === 'precio_asc') ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                            <option value="precio_desc" <?php echo ($orden === 'precio_desc') ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                            <option value="nombre" <?php echo ($orden === 'nombre') ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                        </select>
                    </div>

                    <div class="acciones-filtros">
                        <button type="submit" class="btn-filtrar">Aplicar</button>
                        <?php if ($hay_filtros): ?>
                            <a href="index.php" class="btn-limpiar-filtros">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </details>
        </form>
    </div>

    <h2>
        <?php if ($hay_filtros): ?>
            Resultados (<?php echo $total_articulos; ?> encontrados)
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
                <a href="detalle_articulo.php?id=<?php echo $articulo['id_articulo']; ?>">
                    <img src="<?php echo htmlspecialchars($img_ruta); ?>" alt="<?php echo htmlspecialchars($articulo['titulo'] ?? ''); ?>">
                    <h3><?php echo htmlspecialchars($articulo['titulo'] ?? 'Sin título'); ?></h3>
                </a>

                <p><strong>Descripción:</strong> <?php echo htmlspecialchars($articulo['descripcion'] ?? ''); ?></p>
                <p><strong>Precio:</strong> $<?php echo number_format($articulo['precio'] ?? 0, 0, ',', '.'); ?> COP</p>
                <p><strong>Stock disponible:</strong> <?php echo $articulo['stock'] ?? 0; ?></p>
                <p><strong>Vendido por:</strong> <?php echo htmlspecialchars($articulo['nombre_tienda'] ?? $articulo['nombre_vendedor'] ?? 'Tienda oficial'); ?></p>

                <a href="detalle_articulo.php?id=<?php echo $articulo['id_articulo']; ?>">
                    Ver detalles y contactar
                </a>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'comprador'): ?>
                    <form action="agregar_favorito.php" method="POST">
                        <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">
                        <button type="submit">Agregar a Favoritos</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if ($total_paginas > 1): ?>
            <div class="paginacion">
                <?php if ($pagina > 1): ?>
                    <a href="<?php echo $link_base; ?>pagina=<?php echo $pagina - 1; ?>" class="pag-item">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="<?php echo $link_base; ?>pagina=<?php echo $i; ?>" class="pag-item <?php echo ($i === $pagina) ? 'activo' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="<?php echo $link_base; ?>pagina=<?php echo $pagina + 1; ?>" class="pag-item">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p class="sin-resultados">No se encontraron productos con los criterios seleccionados.</p>
    <?php endif; ?>
</main>

<!-- SECCIÓN FLOTANTE DEL CHAT: SOLO APARECE SI EL USUARIO ESTÁ LOGUEADO -->
<?php if (isset($_SESSION['usuario_id']) && $usuario_id > 0): ?>
    <div class="contenedor-chat-flotante">
        <button id="btn-toggle-chat" class="btn-abrir-chat">
            💬 Mis Mensajes
            <?php if ($num_no_leidos > 0): ?>
                <span class="badge-chat-total"><?php echo $num_no_leidos; ?></span>
            <?php endif; ?>
        </button>

        <div id="panel-chats" class="panel-chats-flotante">
            <div class="header-chat-flotante">
                <strong>Conversaciones (<?php echo ($rol_actual === 'vendedor') ? 'Vendedor' : 'Comprador'; ?>)</strong>
                <button id="btn-cerrar-chat" class="btn-cerrar-panel">✖</button>
            </div>
            <div class="body-chat-flotante">
                <?php if (!empty($chats_activos)): ?>
                    <?php foreach ($chats_activos as $chat): ?>
                        <div class="card-chat-item">
                            <div class="info-chat-item">
                                <strong><?php echo htmlspecialchars($chat['otro_usuario']); ?></strong>
                                <small>Producto: <em><?php echo htmlspecialchars($chat['producto']); ?></em></small>
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <span class="badge-unread-item"><?php echo $chat['unread_count']; ?> nuevo(s)</span>
                                <?php endif; ?>
                            </div>
                            <a href="chat.php?articulo=<?php echo $chat['id_articulo']; ?>&comprador=<?php echo $chat['id_comprador']; ?>" class="btn-abrir-conversacion">
                                Responder
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sin-chats">No tienes conversaciones iniciadas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="js/script.js"></script>
<script src="js/widget_chat.js"></script>
</body>
</html>