<?php
$host     = "localhost";
$usuario  = "root";
$password = "";          
$database = "Sgardent";  

// Crear la conexión usando MySQLi
$pdo = new mysqli($host, $usuario, $password, $database);

// Verificar la conexión
if ($pdo->connect_error) {
    die("Error al conectar a la base de datos: " . $pdo->connect_error);
}

// Establecer el conjunto de caracteres a utf8
$pdo->set_charset("utf8");
?>