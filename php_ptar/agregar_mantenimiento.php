<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar datos
    $equipo = htmlspecialchars($_POST['equipo']);
    $tipo = $_POST['tipo'];
    $fecha_programada = $_POST['fecha_programada'];
    $descripcion = htmlspecialchars($_POST['descripcion']);
    $tecnico = htmlspecialchars($_POST['tecnico'] ?? '');
    $usuario_id = $_SESSION['user_id'];

    // Subir evidencia (opcional)
    $evidencia = '';
    if (isset($_FILES['evidencia'])) {
        $targetDir = "uploads/mantenimientos/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = uniqid() . '_' . basename($_FILES['evidencia']['name']);
        $targetPath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $targetPath)) {
            $evidencia = $targetPath;
        }
    }

    // Insertar en la BD
    $sql = "INSERT INTO mantenimientos (
        equipo, tipo, fecha_programada, descripcion, 
        usuario_id, tecnico_asignado, evidencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiss", $equipo, $tipo, $fecha_programada, $descripcion, $usuario_id, $tecnico, $evidencia);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Mantenimiento registrado correctamente.";
        header("Location: ver_mantenimientos.php");
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
    <title>Registrar Mantenimiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">🔧 Registrar Mantenimiento</h2>
            <form method="POST" enctype="multipart/form-data">
                <!-- Equipo y Tipo -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Equipo:</label>
                        <select class="form-select" name="equipo" required>
                            <option value="">Seleccionar...</option>
                            <option value="Bomba BCM-03A">Bomba BCM-03A</option>
                            <option value="Reactor Biológico">Reactor Biológico</option>
                            <option value="Sedimentador Primario">Sedimentador Primario</option>
                            <option value="Sistema de Cloración">Sistema de Cloración</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo:</label>
                        <select class="form-select" name="tipo" required>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Correctivo">Correctivo</option>
                            <option value="Calibración">Calibración</option>
                            <option value="Limpieza">Limpieza</option>
                        </select>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Fecha Programada:</label>
                        <input type="date" class="form-control" name="fecha_programada" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Técnico Asignado:</label>
                        <input type="text" class="form-control" name="tecnico" placeholder="Opcional">
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label">Descripción:</label>
                    <textarea class="form-control" name="descripcion" rows="3" required 
                    placeholder="Ej: Cambio de sellos, limpieza de filtros..."></textarea>
                </div>

                <!-- Evidencia -->
                <div class="mb-3">
                    <label class="form-label">Evidencia (Foto/PDF):</label>
                    <input type="file" class="form-control" name="evidencia" accept="image/*,.pdf">
                </div>

                <button type="submit" class="btn btn-primary w-100">Guardar</button>
            </form>
        </div>
    </div>
</body>
</html>