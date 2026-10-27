<?php
session_start();
require_once 'conexion.php';

// Validar sesión de vendedor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'vendedor') {
    header('Location: login_vendedor.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $id_vendedor = $_SESSION['usuario_id'];

    // Manejo de la carga de la imagen
    $nombre_imagen = 'default.jpg'; // Imagen por defecto si no sube ninguna

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName    = $_FILES['imagen']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Extensiones permitidas
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($fileExtension, $extensiones_permitidas)) {
            // Nombre único para evitar sobrescribir imágenes existentes
            $nuevo_nombre_imagen = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './uploads/';
            $dest_path = $uploadFileDir . $nuevo_nombre_imagen;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $nombre_imagen = $nuevo_nombre_imagen;
            } else {
                $mensaje = "Error al mover el archivo subido al servidor.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Formato de imagen no válido. Solo se permiten JPG, JPEG, PNG, WEBP y GIF.";
            $tipo_mensaje = "error";
        }
    }

    if (empty($mensaje) && !empty($titulo) && $precio !== false && $precio > 0 && $stock !== false && $stock >= 0) {
        if ($conexion_db) {
            $sql = "INSERT INTO articulo (id_vendedor, titulo, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion_db->prepare($sql);

            if ($stmt) {
                // 'issdis' representa: i=int, s=string, s=string, d=float, i=int, s=string
                $stmt->bind_param("issdis", $id_vendedor, $titulo, $descripcion, $precio, $stock, $nombre_imagen);

                if ($stmt->execute()) {
                    $mensaje = "¡Artículo e imagen publicados con éxito!";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al guardar en la base de datos: " . $stmt->error;
                    $tipo_mensaje = "error";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Nuevo Artículo - Secret Garden</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/stylepublicararticulo.css?v=<?php echo filemtime('css/stylepublicararticulo.css'); ?>">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>
    <div id="bubbles-container"></div>

    <!-- Encabezado con Logo y Título unificados -->
    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Publicar Nuevo Artículo</h1>
    </div>

    <?php if (!empty($mensaje)): ?>
        <p class="<?php echo ($tipo_mensaje === 'exito') ? 'exito-msg' : 'error-msg'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </p>
    <?php endif; ?>

    <form action="publicar_articulo.php" method="POST" enctype="multipart/form-data">
                    <div class="header-theme-container">
        <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>
            </div>
        <div>
            <label for="titulo">Título del Artículo:</label>
            <input type="text" id="titulo" name="titulo" required>
        </div>

        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4"></textarea>
        </div>

        <div>
            <label for="precio">Precio ($ COP):</label>
            <input type="number" id="precio" name="precio" step="1" min="1" placeholder="Ej. 25000" required>
        </div>
        <div>
            <label for="stock">Cantidad disponible:</label>
            <input type="number" id="stock" name="stock" min="1" value="1" required>
        </div>

        <div>
            <label for="imagen">Imagen del producto:</label>
            <input type="file" id="imagen" name="imagen" accept="image/*">
        </div>

        <button type="submit">Publicar Artículo</button>
    </form>

    <!-- Enlaces inferiores con el estilo unificado -->
    <p>
        <a href="panel_vendedor.php">Volver al Panel de Vendedor</a> | 
        <a href="index.php">Ir a la Tienda</a>
    </p>

    <script src="js/script1.js"></script>
    <script src="js/detalle.js"></script>
</body>
</html>