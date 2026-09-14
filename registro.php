<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
// Define aquí el dominio permitido
$dominio_permitido = '@inemjose.edu.co'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($nombre) && !empty($email) && !empty($password)) {

        // 1. Validar formato de email y dominio institucional
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "El correo electrónico no tiene un formato válido.";
        } elseif (!str_ends_with(strtolower($email), $dominio_permitido)) {
            $mensaje = "Debes registrarte con ($dominio_permitido).";
        } else {
            
            // 2. Verificar si el correo ya existe en la BD
            $stmt_check = $pdo->prepare("SELECT id_comprador FROM comprador WHERE email = :email");
            $stmt_check->execute(['email' => $email]);

            if ($stmt_check->rowCount() > 0) {
                $mensaje = "El correo electrónico ya está registrado.";
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                $sql = "INSERT INTO comprador (nombre, email, password) VALUES (:nombre, :email, :password)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute(['nombre' => $nombre, 'email' => $email, 'password' => $password_hash])) {
                    $mensaje = "Registro exitoso. Ya puedes <a href='login.php'>Iniciar Sesión</a>.";
                } else {
                    $mensaje = "Ocurrió un error al registrar el usuario.";
                }
            }
        }
    } else {
        $mensaje = "Por favor completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="css/style2.css">
</head>
<body>
    <div id="bubbles-container"></div>
    <h1>Crear Cuenta</h1>

    <?php if (!empty($mensaje)): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form action="registro.php" method="POST">
        <div>
            <label for="nombre">Nombre completo:</label><br>
            <input type="text" id="nombre" name="nombre" required>
        </div>
        <br>
        <div>
            <label for="email">Correo electrónico:</label><br>
            <!-- Se añade pattern y title para la validación en el cliente -->
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="usuario@inemjose.edu.co"
                pattern=".+@inemjose\.edu.co$"
                title="Debes ingresar el correo institucional @inemjose.edu.co"
                required
            >
        </div>
        <br>
        <div>
            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <button type="submit">Registrarse</button>
    </form>

    <br>
    <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión aquí</a></p>

    <script src="js/script2.js"></script>
</body>
</html>