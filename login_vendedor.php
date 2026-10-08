<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {

        // Consulta en la tabla vendedor
        $stmt = $pdo->prepare("SELECT id_vendedor, nombre, password, nombre_tienda FROM vendedor WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $vendedor  = $resultado->fetch_assoc();

            if ($vendedor && password_verify($password, $vendedor['password'])) {
                // Guardar la sesión como Vendedor
                $_SESSION['usuario_id']     = $vendedor['id_vendedor'];
                $_SESSION['usuario_nombre'] = $vendedor['nombre'];
                $_SESSION['nombre_tienda']  = $vendedor['nombre_tienda'];
                $_SESSION['rol']            = 'vendedor';

                // Redirigir al panel de vendedor
                header('Location: panel_vendedor.php');
                exit;
            } else {
                $mensaje = "Correo o contraseña de vendedor incorrectos.";
            }

            $stmt->close();
        } else {
            $mensaje = "Error al preparar la consulta en la base de datos.";
        }
    } else {
        $mensaje = "Por favor ingresa tu correo y contraseña.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Vendedores</title>
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div id="bubbles-container"></div>
    <h1>Iniciar Sesión (Vendedor)</h1>

    <?php if (!empty($mensaje)): ?>
        <p style="color: red;"><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form action="login_vendedor.php" method="POST">
        <div>
            <label for="email">Correo institucional:</label><br>
            <input type="email" id="email" name="email" required>
        </div>
        <br>
        <div>
            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password" required>
            <br>
            <label class="toggle-password-label">
                <input type="checkbox" id="show-password" onclick="viewPassword()"> 
                <span>Mostrar contraseña</span>
            </label>
        </div>
        <br>
        <button type="submit">Ingresar al Panel</button>
    </form>

    <br>
    <p>¿Quieres vender y no tienes cuenta? <a href="registro_vendedor.php">Regístrate como Vendedor aquí</a></p>
    <p><a href="index.php">
        <img src="img/WhatsApp Image 2026-09-08 at 2.28.42 PM.jpeg" alt="Volver a la página principal">
    </a></p>

    <script src="js/script1.js"></script>
</body>
</html>