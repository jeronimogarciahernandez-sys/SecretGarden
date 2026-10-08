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
    <title>Publicar Nuevo Artículo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div id="bubbles-container"></div>
    <h1>Publicar Nuevo Artículo</h1>

    <?php if (!empty($mensaje)): ?>
        <p <?php echo ($tipo_mensaje === 'exito') ?>>
            <?php echo htmlspecialchars($mensaje); ?>
        </p>
    <?php endif; ?>

    <!-- IMPORTANTE: enctype="multipart/form-data" para subir archivos -->
    <form action="publicar_articulo.php" method="POST" enctype="multipart/form-data">
        <div>
            <label for="titulo">Título del Artículo:</label><br>
            <input type="text" id="titulo" name="titulo" required>
        </div>
        <br>
        <div>
            <label for="descripcion">Descripción:</label><br>
            <textarea id="descripcion" name="descripcion" rows="4"></textarea>
        </div>
        <br>
        <div>
            <label for="precio">Precio ($):</label><br>
            <input type="number" id="precio" name="precio" step="0.01" min="0.01" required>
        </div>
        <br>
        <div>
            <label for="stock">Cantidad disponible (Stock):</label><br>
            <input type="number" id="stock" name="stock" min="1" value="1" required>
        </div>
        <br>
        <div>
            <label for="imagen">Imagen del producto:</label><br>
            <input type="file" id="imagen" name="imagen" accept="image/*">
        </div>
        <br>
        <button type="submit">Publicar Artículo</button>
    </form>

    <br>
    <p><a href="panel_vendedor.php">Volver al Panel de Vendedor</a> | <a href="index.php">Ir a la Tienda</a></p>
    <script src="js/script.js"></script>
</body>
</html>