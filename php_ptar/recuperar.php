<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Verificar si el correo existe
    $sql = "SELECT email FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generar token único (válido por 1 hora)
        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // Guardar token en la BD
        $sql = "INSERT INTO reset_tokens (email, token, expira) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $token, $expira);
        $stmt->execute();

        // **Simular envío de correo** (en producción usa PHPMailer o similar)
        $reset_link = "http://localhost/loginPtarUNAM/nueva_contraseña.php?token=$token";
        echo "<div class='success-message'>Correo enviado a $email. <a href='$reset_link'>Click aquí para probar</a> (en producción esto sería un correo real).</div>";
    } else {
        echo "<div class='error-message'>Correo no registrado.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <div class="login-box">
        <h1>Recuperar Contraseña</h1>
        <form method="POST">
            <input type="email" name="email" placeholder="Email @unam.mx" required>
            <button type="submit">Enviar Enlace</button>
        </form>
        <a href="index.html">Volver al Login</a>
    </div>
</body>
</html>