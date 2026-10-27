<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['codigo_verificado'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email            = $_SESSION['reset_email'];

    // 1. Validar que ambas contraseñas coincidan
    if ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        // 2. Mismo patrón de seguridad usado en el registro
        $patron_password = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,16}$/';

        if (!preg_match($patron_password, $password)) {
            $mensaje = "La contraseña debe tener entre 8 y 16 caracteres, incluir una mayúscula, una minúscula, un número y un símbolo ($@$!%*?&).";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // 3. Consultar rol para actualizar la tabla correspondiente
            $stmt_check = $pdo->prepare("SELECT rol FROM recuperacion_password WHERE email = ? ORDER BY id DESC LIMIT 1");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $res = $stmt_check->get_result()->fetch_assoc();
            $rol = $res['rol'] ?? 'comprador';

            if ($rol === 'vendedor') {
                $stmt_upd = $pdo->prepare("UPDATE vendedor SET password = ? WHERE email = ?");
            } else {
                $stmt_upd = $pdo->prepare("UPDATE comprador SET password = ? WHERE email = ?");
            }

            $stmt_upd->bind_param("ss", $password_hash, $email);

            if ($stmt_upd->execute()) {
                // Eliminar tokens de recuperación de la base de datos
                $stmt_del = $pdo->prepare("DELETE FROM recuperacion_password WHERE email = ?");
                $stmt_del->bind_param("s", $email);
                $stmt_del->execute();

                unset($_SESSION['reset_email'], $_SESSION['codigo_verificado']);
                echo "<script>alert('Contraseña actualizada correctamente.'); window.location.href='login.php';</script>";
                exit;
            } else {
                $mensaje = "Error al actualizar la contraseña.";
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
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/jpeg" href="img/LogoSg.jpeg">
</head>
<body>
    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Restablecer Contraseña</h1>
    </div>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="cambiar_password.php" method="POST" onsubmit="return validarPasswordMatch();">
        <div>
            <label for="password">Nueva Contraseña:</label><br>
            <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Nueva Contraseña"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,16}"
                title="Debe tener entre 8 y 16 caracteres, incluir una mayúscula, una minúscula, un número y un carácter especial ($@$!%*?&)."
                required
            >
        </div>
        <br>
        <div>
            <label for="confirm_password">Confirmar Contraseña:</label><br>
            <input 
                type="password" 
                id="confirm_password" 
                name="confirm_password" 
                placeholder="Repite la contraseña"
                required
            >
            <br>
            <label class="toggle-password-label">
                <input type="checkbox" id="show-password" onclick="viewPasswords()"> 
                <span>Mostrar contraseña</span>
            </label>
        </div>
        <br>

        <button type="submit">Guardar Nueva Contraseña</button>
    </form>

    <script>
        // Función para mostrar/ocultar ambas contraseñas
        function viewPasswords() {
            var pass1 = document.getElementById("password");
            var pass2 = document.getElementById("confirm_password");
            var type = pass1.type === "password" ? "text" : "password";
            pass1.type = type;
            pass2.type = type;
        }

        // Validación en cliente antes de enviar
        function validarPasswordMatch() {
            var pass1 = document.getElementById("password").value;
            var pass2 = document.getElementById("confirm_password").value;
            if (pass1 !== pass2) {
                alert("Las contraseñas no coinciden. Por favor verifícalas.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>