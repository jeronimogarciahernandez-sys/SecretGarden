<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$dominio_permitido = '@inemjose.edu.co'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email)) {
        if (!str_ends_with(strtolower($email), $dominio_permitido)) {
            $mensaje = "El correo debe ser institucional ($dominio_permitido).";
        } else {
            // Verificar si el correo existe en comprador o vendedor
            $rol = null;
            $stmt = $pdo->prepare("SELECT id_comprador FROM comprador WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $rol = 'comprador';
            } else {
                $stmt_v = $pdo->prepare("SELECT id_vendedor FROM vendedor WHERE email = ?");
                $stmt_v->bind_param("s", $email);
                $stmt_v->execute();
                if ($stmt_v->get_result()->num_rows > 0) {
                    $rol = 'vendedor';
                }
            }

            if ($rol) {
                // Generar código numérico de 6 dígitos (15 min de vigencia)
                $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Guardar en la base de datos
                $stmt_inst = $pdo->prepare("INSERT INTO recuperacion_password (email, codigo, expiracion, rol) VALUES (?, ?, ?, ?)");
                $stmt_inst->bind_param("ssss", $email, $codigo, $expiracion, $rol);
                $stmt_inst->execute();

                // ENVÍO DE CORREO VÍA API DE RESEND
                $apiKey = ''TU_RESEND_API_KEY''; // Reemplaza con tu API Key de Resend

                $data = [
                    'from'    => 'Secret Garden <onboarding@resend.dev>', // Correo de prueba provisto por Resend
                    'to'      => [$email],
                    'subject' => 'Código de Recuperación - Secret Garden',
                    'html'    => "<h2>Recuperación de Contraseña</h2>
                                  <p>Tu código de verificación es: <strong>$codigo</strong></p>
                                  <p>Este código vencerá en 15 minutos.</p>"
                ];

                $ch = curl_init('https://api.resend.com/emails');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 || $httpCode === 201) {
                    $_SESSION['reset_email'] = $email;
                    header('Location: verificar_codigo.php');
                    exit;
                } else {
                    $mensaje = "Error al enviar el correo vía API. Código de respuesta: " . $httpCode;
                }

            } else {
                $mensaje = "El correo no está registrado en el sistema.";
            }
        }
    } else {
        $mensaje = "Ingresa tu correo registrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div class="titulo-con-logo">
        <img src="img/LogoSg.jpeg" alt="Logo Secret Garden" class="logo-titulo">
        <h1>Recuperar Contraseña</h1>
    </div>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="recuperar_password.php" method="POST">
        <div>
            <label for="email">Ingresa tu correo institucional:</label><br>
            <input type="email" id="email" name="email" required placeholder="usuario@inemjose.edu.co">
        </div>
        <br>
        <button type="submit">Enviar Código</button>
    </form>
    <br>
    <a href="login.php">Volver al inicio de sesión</a>
</body>
</html>