<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'vendedor') {
    header('Location: login_vendedor.php');
    exit;
}
$conexion_db = $conn ?? $conexion ?? $pdo ?? null;
$id_vendedor = $_SESSION['usuario_id'];
$id_articulo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);

$mensaje = '';
if (!$id_articulo || !$conexion_db) {
    header('Location: panel_vendedor.php');
    exit;
}

// Obtener los datos del artículo asegurando que sea del vendedor actual
$sql = "SELECT * FROM articulo WHERE id_articulo = ? AND id_vendedor = ?";
$stmt = $conexion_db->prepare($sql);
$stmt->bind_param("ii", $id_articulo, $id_vendedor);
$stmt->execute();
$articulo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$articulo) {
    header('Location: panel_vendedor.php');
    exit;
}

// Procesar actualización (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $nombre_imagen = $articulo['imagen']; // Conservar la imagen actual por defecto

    // Si se subió una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['imagen']['tmp_name'];
        $fileName      = $_FILES['imagen']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($fileExtension, $extensiones_permitidas)) {
            $nuevo_nombre_imagen = md5(time() . $fileName) . '.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, './uploads/' . $nuevo_nombre_imagen)) {
                $nombre_imagen = $nuevo_nombre_imagen;
            }
        }
    }

    if (!empty($titulo) && $precio !== false && $precio > 0 && $stock !== false && $stock >= 0) {
        $sql_update = "UPDATE articulo SET titulo = ?, descripcion = ?, precio = ?, stock = ?, imagen = ? WHERE id_articulo = ? AND id_vendedor = ?";
        $stmt = $conexion_db->prepare($sql_update);
        if ($stmt) {
            $stmt->bind_param("ssdisii", $titulo, $descripcion, $precio, $stock, $nombre_imagen, $id_articulo, $id_vendedor);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: panel_vendedor.php?msg=editado');
                exit;
            } else {
                $mensaje = "Error al actualizar en la base de datos.";
            }
        }
    } else {
        $mensaje = "Por favor ingresa datos válidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Artículo - Secret Garden</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/styleeditararticulo.css">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>

    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Editar Artículo</h1>


    </div>

    <?php if (!empty($mensaje)): ?>
        <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="editar_articulo.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">

                    <div class="header-theme-container">
        <button id="theme-toggle" class="btn-theme" type="button">🌙 Modo Oscuro</button>
            </div>
        <div>
            <label for="titulo">Título del Artículo:</label>
            <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($articulo['titulo']); ?>" required>
        </div>

        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($articulo['descripcion']); ?></textarea>
        </div>

        <div>
            <label for="precio">Precio ($ COP):</label>
            <input type="number" id="precio" name="precio" step="1" min="1" value="<?php echo (int)$articulo['precio']; ?>" required>
        </div>

        <div>
            <label for="stock">Cantidad disponible (Stock):</label>
            <input type="number" id="stock" name="stock" min="0" value="<?php echo $articulo['stock']; ?>" required>
        </div>

        <div>
            <label>Imagen Actual:</label>
            <img src="uploads/<?php echo htmlspecialchars($articulo['imagen'] ?? 'default.jpg'); ?>" alt="Imagen actual">
            <label for="imagen">Cambiar Imagen (opcional):</label>
            <input type="file" id="imagen" name="imagen" accept="image/*">
        </div>

        <button type="submit">Guardar Cambios</button>
    </form>

    <p><a href="panel_vendedor.php">Cancelar y Volver al Panel</a></p>

    <script src="js/script1.js"></script>
    <script src="js/detalle.js"></script>
</body>
</html>