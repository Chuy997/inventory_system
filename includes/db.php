<?php
$servername = "localhost";
$username = "root"; // O tu usuario de base de datos
$password = ""; // O tu contraseña de base de datos
$dbname = "inventory_system";

// Crear conexión
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Verificar conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
?>
