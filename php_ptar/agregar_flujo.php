<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del formulario
    $nivel_inicial = $_POST['nivel_inicial'];
    $nivel_final = $_POST['nivel_final'];
    $tiempo_inicio = $_POST['tiempo_inicio'];
    $tiempo_fin = $_POST['tiempo_fin'];
    $observaciones = $_POST['observaciones'];
    $usuario_id = $_SESSION['user_id'];

    // Cálculos (similares a las fórmulas del Excel)
    $diferencia_nivel = $nivel_inicial - $nivel_final;
    $volumen = 4 * 4.5 * $diferencia_nivel; // Ajusta según tu fórmula real
    $diferencia_tiempo_min = (strtotime($tiempo_fin) - strtotime($tiempo_inicio)) / 60;
    $caudal_m3s = $volumen / ($diferencia_tiempo_min * 60); // m³/s

    // Insertar en la base de datos
    $sql = "INSERT INTO flujos (
        nivel_inicial, nivel_final, diferencia_nivel, volumen_generado,
        tiempo_inicio, tiempo_fin, diferencia_tiempo_min, caudal_m3s,
        observaciones, usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ddddssddsi",
        $nivel_inicial, $nivel_final, $diferencia_nivel, $volumen,
        $tiempo_inicio, $tiempo_fin, $diferencia_tiempo_min, $caudal_m3s,
        $observaciones, $usuario_id
    );

    if ($stmt->execute()) {
        header("Location: dashboard.php?success=1");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Flujo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>📊 Registrar Flujo de Agua</h2>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nivel Inicial (m):</label>
                <input type="number" step="0.01" class="form-control" name="nivel_inicial" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nivel Final (m):</label>
                <input type="number" step="0.01" class="form-control" name="nivel_final" required>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">Hora Inicio:</label>
                    <input type="time" class="form-control" name="tiempo_inicio" required>
                </div>
                <div class="col">
                    <label class="form-label">Hora Fin:</label>
                    <input type="time" class="form-control" name="tiempo_fin" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones:</label>
                <textarea class="form-control" name="observaciones"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</body>
</html>