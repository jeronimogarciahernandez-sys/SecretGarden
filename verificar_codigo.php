<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: recuperar_password.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $email = $_SESSION['reset_email'];

    $stmt = $pdo->prepare("SELECT id FROM recuperacion_password WHERE email = ? AND codigo = ? AND expiracion > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['codigo_verificado'] = true;
        header('Location: cambiar_password.php');
        exit;
    } else {
        $mensaje = "Código incorrecto o ha expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código</title>
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Ingresar Código</h1>
    </div>
    <?php if (!empty($mensaje)): ?><p><?php echo $mensaje; ?></p><?php endif; ?>

    <form action="verificar_codigo.php" method="POST">
        <div>
            <label for="codigo">Código de 6 dígitos enviado a tu correo:</label><br>
            <input type="text" id="codigo" name="codigo" maxlength="6" required pattern="\d{6}">
        </div>
        <br>
        <button type="submit">Verificar</button>
    </form>
</body>
</html>