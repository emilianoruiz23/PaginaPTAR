<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar token y tiempo
    $sql = "SELECT email FROM reset_tokens WHERE token = ? AND expira > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        if ($new_password === $confirm_password) {
            // Actualizar contraseña
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();

            // Eliminar token usado
            $sql = "DELETE FROM reset_tokens WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();

            echo "<div class='success-message'>Contraseña actualizada. <a href='index.html'>Iniciar Sesión</a></div>";
        } else {
            echo "<div class='error-message'>Las contraseñas no coinciden.</div>";
        }
    } else {
        echo "<div class='error-message'>Enlace inválido o expirado.</div>";
    }
} else {
    $token = $_GET['token'] ?? '';
    // Verificar token antes de mostrar el formulario
    $sql = "SELECT email FROM reset_tokens WHERE token = ? AND expira > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("<div class='error-message'>Enlace inválido o expirado.</div>");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <div class="login-box">
        <h1>Nueva Contraseña</h1>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="new_password" placeholder="Nueva contraseña (mín. 8 caracteres)" minlength="8" required>
            <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
            <button type="submit">Guardar</button>
        </form>
    </div>
</body>
</html>