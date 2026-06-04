<?php
$host = "localhost";
$user = "root";       // Usuario por defecto XAMPP
$password = "";       // Contraseña vacía por defecto
$database = "ptar_acatlan"; // Nombre de tu BD
$puerto = 3307;

$conn = new mysqli($host, $user, $password, $database, $puerto);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>