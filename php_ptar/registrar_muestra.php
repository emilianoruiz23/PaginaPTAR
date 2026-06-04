<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'laboratorio'])) {
    die("Acceso restringido");
}

$muestreo_id = $_GET['muestreo_id'] ?? die("ID no especificado");

// Obtener datos del muestreo
$sql = "SELECT m.*, u.nombre as responsable 
        FROM muestreos m
        JOIN usuarios u ON m.responsable_id = u.id
        WHERE m.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $muestreo_id);
$stmt->execute();
$muestreo = $stmt->get_result()->fetch_assoc();

if (!$muestreo) die("Muestreo no encontrado");

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor_medido = floatval($_POST['valor_medido']);
    $fecha_realizacion = $_POST['fecha_realizacion'];
    $observaciones = htmlspecialchars($_POST['observaciones'] ?? '');
    $evidencia = '';

    // Subir archivo (opcional)
    if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/muestreos/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = uniqid() . '_' . basename($_FILES['evidencia']['name']);
        $targetPath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $targetPath)) {
            $evidencia = $targetPath;
        }
    }

    // 1. Actualizar el muestreo
    $update_sql = "UPDATE muestreos SET 
        estado = 'Realizado',
        observaciones = CONCAT(IFNULL(observaciones, ''), ?)
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $observacion_completa = "\n\n--- Registro del " . date('d/m/Y') . " ---\n" .
                            "Valor medido: $valor_medido\n" .
                            "Observaciones: $observaciones";
    $stmt->bind_param("si", $observacion_completa, $muestreo_id);
    $stmt->execute();

    // 2. Registrar en tabla de resultados (opcional, si existe)
    // ...

    $_SESSION['success'] = "Muestra registrada correctamente.";
    header("Location: calendario_muestreos.php");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Muestra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🧪 Registrar Muestra</h2>
            </div>
            <div class="card-body">
                <!-- Datos del Muestreo -->
                <div class="mb-4">
                    <h5>Información del Muestreo</h5>
                    <div class="card bg-light">
                        <div class="card-body">
                            <p><strong>Normativa:</strong> <?= $muestreo['normativa'] ?></p>
                            <p><strong>Parámetro:</strong> <?= $muestreo['parametro'] ?></p>
                            <p><strong>Límite Máximo:</strong> <?= $muestreo['limite_maximo'] ?? 'N/A' ?></p>
                            <p><strong>Punto:</strong> <?= $muestreo['punto_muestreo'] ?></p>
                            <p><strong>Responsable:</strong> <?= $muestreo['responsable'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Registro -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor Medido:</label>
                            <input type="number" step="0.0001" class="form-control" 
                                name="valor_medido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Realización:</label>
                            <input type="date" class="form-control" 
                                name="fecha_realizacion" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Evidencia (PDF/Imagen):</label>
                        <input type="file" class="form-control" name="evidencia" accept=".pdf,.jpg,.png">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones:</label>
                        <textarea class="form-control" name="observaciones" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar Registro</button>
                </form>
            </div>
        </div>
        <a href="calendario_muestreos.php" class="btn btn-secondary mt-3">Volver al Calendario</a>
    </div>
</body>
</html>