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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Vendedores</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>
    <div id="bubbles-container"></div>
    
    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Iniciar Sesión (Vendedor)</h1>
    </div>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="login_vendedor.php" method="POST">
        <div>
            <label for="email">✉️ Correo institucional:</label><br>
            <input type="email" id="email" name="email" required>
        </div>
        <br>
        <div>
            <label for="password">🔒 Contraseña:</label><br>
            <input type="password" id="password" name="password" required>
            <br>
            <label class="toggle-password-label">
                <input type="checkbox" id="show-password" onclick="viewPassword()"> 
                <span>Mostrar contraseña</span>
            </label>
        </div>
        <br>

        <!-- Enlace para recuperar contraseña (apunta al mismo flujo general) -->
        <div class="contenedor-olvido">
            <a href="recuperar_password.php" class="link-olvido">¿Olvidaste tu contraseña?</a>
        </div>
        <br>

        <button type="submit">Ingresar al Panel</button>
    </form>

    <br>
    <p>¿Quieres vender y no tienes cuenta? <a href="registro_vendedor.php">Regístrate como Vendedor aquí</a></p>

    <script src="js/script1.js"></script>
</body>
</html>