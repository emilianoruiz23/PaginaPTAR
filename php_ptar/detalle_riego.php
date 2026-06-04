<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (!isset($_GET['id'])) {
    die("ID no especificado");
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM riegos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$riego = $result->fetch_assoc();

if (!$riego) {
    die("Registro no encontrado");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Riego</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Detalles del Riego #<?= $riego['id'] ?></h2>
        <div class="card">
            <div class="card-body">
                <p><strong>Fecha:</strong> <?= $riego['fecha'] ?></p>
                <p><strong>Hora Inicio:</strong> <?= $riego['hora_inicio'] ?></p>
                <p><strong>Zona:</strong> <?= $riego['zona_regada'] ?></p>
                <!-- Agrega más campos según necesites -->
            </div>
        </div>
        <a href="ver_riegos.php" class="btn btn-secondary mt-3">Volver</a>
    </div>
</body>
</html>