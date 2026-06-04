<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $punto_muestreo = htmlspecialchars($_POST['punto_muestreo']);
    $ph = floatval($_POST['ph']);
    $turbidez = floatval($_POST['turbidez']);
    $oxigeno = floatval($_POST['oxigeno']);
    $conductividad = floatval($_POST['conductividad']);
    $temperatura = floatval($_POST['temperatura']);
    $dbo5 = floatval($_POST['dbo5']);
    $solidos = floatval($_POST['solidos']);
    $observaciones = htmlspecialchars($_POST['observaciones']);
    $usuario_id = $_SESSION['user_id'];

    // Validar rangos (ejemplo para pH)
    if ($ph < 0 || $ph > 14) {
        die("Error: El pH debe estar entre 0 y 14");
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO calidad_agua (
        punto_muestreo, ph, turbidez, oxigeno_disuelto, 
        conductividad, temperatura, dbo5, solidos_suspendidos, 
        observaciones, usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sdddddddss",
        $punto_muestreo, $ph, $turbidez, $oxigeno,
        $conductividad, $temperatura, $dbo5, $solidos,
        $observaciones, $usuario_id
    );

    if ($stmt->execute()) {
        header("Location: ver_calidad.php?success=1");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Calidad del Agua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .parametro {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">🌊 Registro de Parámetros de Calidad</h2>
            <form method="POST">
                <!-- Punto de Muestreo -->
                <div class="mb-3">
                    <label class="form-label">Punto de Muestreo:</label>
                    <select class="form-select" name="punto_muestreo" required>
                        <option value="Entrada PTAR">Entrada PTAR</option>
                        <option value="Salida Reactor">Salida Reactor</option>
                        <option value="Humedal 1">Humedal 1</option>
                        <option value="Descarga Final">Descarga Final</option>
                    </select>
                </div>

                <!-- Parámetros Fisicoquímicos -->
                <div class="parametro">
                    <h5>📊 Parámetros Fisicoquímicos</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">pH (0-14):</label>
                            <input type="number" step="0.1" class="form-control" name="ph" min="0" max="14" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Turbidez (NTU):</label>
                            <input type="number" step="0.01" class="form-control" name="turbidez">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Oxígeno Disuelto (mg/L):</label>
                            <input type="number" step="0.1" class="form-control" name="oxigeno">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Conductividad (µS/cm):</label>
                            <input type="number" step="1" class="form-control" name="conductividad">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Temperatura (°C):</label>
                            <input type="number" step="0.1" class="form-control" name="temperatura">
                        </div>
                    </div>
                </div>

                <!-- Parámetros Químicos -->
                <div class="parametro">
                    <h5>🧪 Parámetros Químicos</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">DBO5 (mg/L):</label>
                            <input type="number" step="0.01" class="form-control" name="dbo5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sólidos Suspendidos (mg/L):</label>
                            <input type="number" step="0.01" class="form-control" name="solidos">
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label">Observaciones:</label>
                    <textarea class="form-control" name="observaciones" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Guardar Datos</button>
            </form>
        </div>
    </div>
</body>
</html>