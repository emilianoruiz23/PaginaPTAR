<?php
session_start();
include 'conexion.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $cantidad = floatval($_POST['cantidad']);
    $unidad = $_POST['unidad'];
    $proveedor = htmlspecialchars($_POST['proveedor'] ?? '');
    $lote = htmlspecialchars($_POST['lote'] ?? '');
    $ubicacion = htmlspecialchars($_POST['ubicacion'] ?? '');
    $fecha_caducidad = $_POST['fecha_caducidad'] ?: NULL;
    $observaciones = htmlspecialchars($_POST['observaciones'] ?? '');
    $usuario_id = $_SESSION['user_id'];

    $sql = "INSERT INTO insumos (
        nombre, cantidad, unidad, proveedor, lote, ubicacion,
        fecha_caducidad, observaciones, usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sdssssssi",
        $nombre, $cantidad, $unidad, $proveedor, $lote,
        $ubicacion, $fecha_caducidad, $observaciones, $usuario_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Insumo registrado correctamente.";
        header("Location: ver_insumos.php");
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
    <title>Registrar Insumo</title>
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
            <h2 class="text-center mb-4">🧪 Registrar Nuevo Insumo</h2>
            <form method="POST">
                <!-- Nombre y Cantidad -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del Insumo:</label>
                        <input type="text" class="form-control" name="nombre" required 
                            placeholder="Ej: Cloro, Sulfato de aluminio">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cantidad:</label>
                        <input type="number" step="0.01" class="form-control" name="cantidad" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unidad:</label>
                        <select class="form-select" name="unidad" required>
                            <option value="kg">kg</option>
                            <option value="L">L</option>
                            <option value="mg">mg</option>
                            <option value="g">g</option>
                            <option value="ml">ml</option>
                        </select>
                    </div>
                </div>

                <!-- Proveedor y Lote -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Proveedor:</label>
                        <input type="text" class="form-control" name="proveedor">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número de Lote:</label>
                        <input type="text" class="form-control" name="lote">
                    </div>
                </div>

                <!-- Ubicación y Caducidad -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ubicación en Almacén:</label>
                        <input type="text" class="form-control" name="ubicacion" 
                            placeholder="Ej: Estante 2, Zona Reactivos">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha de Caducidad (opcional):</label>
                        <input type="date" class="form-control" name="fecha_caducidad">
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label">Observaciones:</label>
                    <textarea class="form-control" name="observaciones" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Guardar</button>
            </form>
        </div>
    </div>
</body>
</html>