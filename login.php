<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {

        // 1. Usar el marcador posicional '?' en lugar de ':email'
        $stmt = $pdo->prepare("SELECT id_comprador, nombre, password FROM comprador WHERE email = ?");
        
        if ($stmt) {
            // 2. Vincular la variable ($email) a la consulta ('s' indica que es tipo String)
            $stmt->bind_param('s', $email);
            $stmt->execute();
            
            // 3. Obtener el resultado
            $resultado = $stmt->get_result();
            $usuario   = $resultado->fetch_assoc();

            // 4. Verificar la contraseña y crear la sesión
            if ($usuario && password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id']     = $usuario['id_comprador'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['rol']            = 'comprador';

                header('Location: index.php');
                exit;
            } else {
                $mensaje = "Correo o contraseña incorrectos.";
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
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div id="bubbles-container"></div>
    <h1>Iniciar Sesión</h1>

    <?php if (!empty($mensaje)): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
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
        <button type="submit">Ingresar</button>
    </form>

    <br>
    <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>

        <script src="js/script1.js"></script>
</body>
</html>