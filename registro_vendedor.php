<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$dominio_permitido = '@inemjose.edu.co'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $nombre_tienda = trim($_POST['nombre_tienda'] ?? '');
    $telefono      = trim($_POST['telefono'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = $_POST['password'] ?? '';

    if (!empty($nombre) && !empty($email) && !empty($password)) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "El correo electrónico no tiene un formato válido.";
        } elseif (!str_ends_with(strtolower($email), $dominio_permitido)) {
            $mensaje = "Debes registrarte con tu correo institucional ($dominio_permitido).";
        } else {

            $patron_password = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,16}$/';

            if (!preg_match($patron_password, $password)) {
                $mensaje = "La contraseña debe tener entre 8 y 16 caracteres, incluir mayúscula, minúscula, número y símbolo.";
            } else {

                // Verificar si ya existe en la tabla vendedor
                $stmt_check = $pdo->prepare("SELECT id_vendedor FROM vendedor WHERE email = ?");
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $mensaje = "Este correo ya está registrado como vendedor.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insertar en la tabla vendedor
                    $sql = "INSERT INTO vendedor (nombre, email, password, telefono, nombre_tienda) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bind_param("sssss", $nombre, $email, $password_hash, $telefono, $nombre_tienda);
                    
                    if ($stmt->execute()) {
                        $mensaje = "¡Registro exitoso como vendedor! Ya puedes <a href='login_vendedor.php'>Iniciar Sesión aquí</a>.";
                    } else {
                        $mensaje = "Ocurrió un error al registrar la cuenta de vendedor.";
                    }
                }
            }
        }
    } else {
        $mensaje = "Por favor completa todos los campos requeridos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Vendedores</title>
    <link rel="stylesheet" href="css/styleregistroV.css?v=<?php echo filemtime('css/stylepublicararticulo.css'); ?>">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>
    <div id="bubbles-container"></div>

    <div class="card-container">
        <div class="header-registro">
            <div class="titulo-con-logo">
                <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
                <h1>Registro para Vendedores</h1>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form action="registro_vendedor.php" method="POST">
            <div class="form-grid">
                <div class="input-group">
                    <label for="nombre">👤 Nombre completo:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="input-group">
                    <label for="nombre_tienda">🏪 Nombre de tu Tienda / Jardín:</label>
                    <input type="text" id="nombre_tienda" name="nombre_tienda" placeholder="Ej. Jardín Encantado" required>
                </div>

                <div class="input-group">
                    <label for="telefono">📞 Teléfono / WhatsApp de contacto:</label>
                    <input type="text" id="telefono" name="telefono">
                </div>

                <div class="input-group">
                    <label for="email">✉️ Correo electrónico institucional:</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="vendedor@inemjose.edu.co"
                        pattern=".+@inemjose\.edu.co$"
                        title="Debes ingresar el correo institucional @inemjose.edu.co"
                        required
                    >
                </div>

                <div class="input-group full-width">
                    <label for="password">🔒 Contraseña:</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,16}"
                        title="La contraseña debe tener entre 8 y 16 caracteres, incluir una mayúscula, una minúscula, un número y un carácter especial ($@$!%*?&)."
                        required
                    >
                    <label class="toggle-password-label">
                        <input type="checkbox" id="show-password" onclick="viewPassword()"> 
                        <span>Mostrar contraseña</span>
                    </label>
                </div>
            </div>

            <button type="submit">Registrarme como Vendedor</button>
        </form>

        <div class="form-footer">
            <p>¿Ya tienes una cuenta de vendedor? <a href="login_vendedor.php">Inicia Sesión aquí</a></p>
            <p><a href="index.php">Volver a la tienda principal</a></p>
        </div>
    </div>

    <script src="js/script2.js"></script>
</body>
</html>