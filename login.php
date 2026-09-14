<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {

        $stmt = $pdo->prepare("SELECT id_comprador, nombre, password FROM comprador WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id']     = $usuario['id_comprador'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];

            header('Location: index.php');
            exit;
        } else {
            $mensaje = "Correo o contraseña incorrectos.";
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
            <label for="email">Correo electrónico:</label><br>
            <input type="email" id="email" name="email" required>
        </div>
        <br>
        <div>
            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <button type="submit">Ingresar</button>
    </form>

    <br>
    <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>

        <script src="js/script1.js"></script>
</body>
</html>