<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'laboratorio'])) {
    die("Acceso restringido");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $normativa = htmlspecialchars($_POST['normativa']);
    $parametro = htmlspecialchars($_POST['parametro']);
    $punto_muestreo = htmlspecialchars($_POST['punto_muestreo']);
    $frecuencia = $_POST['frecuencia'];
    $proxima_fecha = $_POST['proxima_fecha'];
    $responsable_id = $_POST['responsable_id'];
    $metodo = htmlspecialchars($_POST['metodo'] ?? '');
    $limite = floatval($_POST['limite'] ?? 0);
    $observaciones = htmlspecialchars($_POST['observaciones'] ?? '');

    $sql = "INSERT INTO muestreos (
        normativa, parametro, punto_muestreo, frecuencia,
        proxima_fecha, responsable_id, metodo_analisis,
        limite_maximo, observaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssisds",
        $normativa, $parametro, $punto_muestreo, $frecuencia,
        $proxima_fecha, $responsable_id, $metodo,
        $limite, $observaciones
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Muestreo programado correctamente.";
        header("Location: calendario_muestreos.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Obtener responsables (usuarios con rol 'laboratorio' o 'admin')
$responsables = $conn->query("SELECT id, nombre FROM usuarios WHERE rol IN ('admin', 'laboratorio')");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programar Muestreo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .normativa-card {
            border-left: 4px solid #3498db;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">📅 Programar Muestreo Normativo</h2>
            <form method="POST">
                <!-- Normativa y Parámetro -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Normativa:</label>
                        <select class="form-select" name="normativa" required>
                            <option value="NOM-001-SEMARNAT-2021">NOM-001-SEMARNAT-2021</option>
                            <option value="NOM-002-SEMARNAT-1996">NOM-002-SEMARNAT-1996</option>
                            <option value="NMX-AA-003-1980">NMX-AA-003-1980</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parámetro a Medir:</label>
                        <input type="text" class="form-control" name="parametro" 
                            placeholder="Ej: Coliformes totales" required>
                    </div>
                </div>

                <!-- Punto y Frecuencia -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Punto de Muestreo:</label>
                        <select class="form-select" name="punto_muestreo" required>
                            <option value="Entrada PTAR">Entrada PTAR</option>
                            <option value="Salida Reactor">Salida Reactor</option>
                            <option value="Descarga Final">Descarga Final</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Frecuencia:</label>
                        <select class="form-select" name="frecuencia" required>
                            <option value="Diario">Diario</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Quincenal">Quincenal</option>
                            <option value="Mensual" selected>Mensual</option>
                            <option value="Trimestral">Trimestral</option>
                        </select>
                    </div>
                </div>

                <!-- Fechas y Responsable -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Próxima Fecha:</label>
                        <input type="date" class="form-control" 
                            name="proxima_fecha" id="proxima_fecha" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Responsable:</label>
                        <select class="form-select" name="responsable_id" required>
                            <?php while ($row = $responsables->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Método y Límite -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Método de Análisis:</label>
                        <input type="text" class="form-control" name="metodo"
                            placeholder="Ej: SM 4500-NH3 D">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Límite Máximo (Norma):</label>
                        <input type="number" step="0.01" class="form-control" 
                            name="limite" placeholder="Ej: 0.5">
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label">Observaciones:</label>
                    <textarea class="form-control" name="observaciones" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Programar Muestreo</button>
            </form>
        </div>
    </div>

    <!-- Script para calendario -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#proxima_fecha", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });
    </script>
</body>
</html>