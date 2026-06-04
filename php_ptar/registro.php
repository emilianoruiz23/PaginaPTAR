<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar correo @unam.mx
    if (!preg_match("/@unam.mx$/i", $email)) {
        die("Error: Solo correos @unam.mx permitidos");
    }

    // Validar contraseñas coincidentes
    if ($password !== $confirm_password) {
        die("Error: Las contraseñas no coinciden");
    }

    // Hashear contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar en BD
    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $hashed_password);

    if ($stmt->execute()) {
        header("Location: index.html?registro=exitoso");
        exit();
    } else {
        die("Error al registrar: " . $stmt->error);
    }
}
?>