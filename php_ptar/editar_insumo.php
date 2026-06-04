<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'almacen') {
    die("Acceso restringido al área de almacén");
}

$id = $_GET['id'] ?? die("ID no especificado");
$sql = "SELECT * FROM insumos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$insumo = $stmt->get_result()->fetch_assoc();

if (!$insumo) die("Insumo no encontrado");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $cantidad = floatval($_POST['cantidad']);
    $unidad = $_POST['unidad'];
    $proveedor = htmlspecialchars($_POST['proveedor'] ?? '');
    $lote = htmlspecialchars($_POST['lote'] ?? '');
    $ubicacion = htmlspecialchars($_POST['ubicacion'] ?? '');
    $fecha_caducidad = $_POST['fecha_caducidad'] ?: NULL;
    $observaciones = htmlspecialchars($_POST['observaciones'] ?? '');

    $update_sql = "UPDATE insumos SET
        nombre = ?, cantidad = ?, unidad = ?, proveedor = ?, lote = ?,
        ubicacion = ?, fecha_caducidad = ?, observaciones = ?
        WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param(
        "sdssssssi",
        $nombre, $cantidad, $unidad, $proveedor, $lote,
        $ubicacion, $fecha_caducidad, $observaciones, $id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Insumo actualizado correctamente.";
        header("Location: ver_insumos.php");
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Insumo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">✏️ Editar Insumo</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- Nombre y Cantidad -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre:</label>
                            <input type="text" class="form-control" name="nombre" 
                                value="<?= $insumo['nombre'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cantidad:</label>
                            <input type="number" step="0.01" class="form-control" 
                                name="cantidad" value="<?= $insumo['cantidad'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unidad:</label>
                            <select class="form-select" name="unidad" required>
                                <option value="kg" <?= $insumo['unidad'] == 'kg' ? 'selected' : '' ?>>kg</option>
                                <option value="L" <?= $insumo['unidad'] == 'L' ? 'selected' : '' ?>>L</option>
                                <option value="mg" <?= $insumo['unidad'] == 'mg' ? 'selected' : '' ?>>mg</option>
                                <option value="g" <?= $insumo['unidad'] == 'g' ? 'selected' : '' ?>>g</option>
                                <option value="ml" <?= $insumo['unidad'] == 'ml' ? 'selected' : '' ?>>ml</option>
                            </select>
                        </div>
                    </div>

                    <!-- Proveedor y Lote -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor:</label>
                            <input type="text" class="form-control" name="proveedor" 
                                value="<?= $insumo['proveedor'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lote:</label>
                            <input type="text" class="form-control" name="lote" 
                                value="<?= $insumo['lote'] ?>">
                        </div>
                    </div>

                    <!-- Ubicación y Caducidad -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ubicación:</label>
                            <input type="text" class="form-control" name="ubicacion" 
                                value="<?= $insumo['ubicacion'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Caducidad:</label>
                            <input type="date" class="form-control" name="fecha_caducidad" 
                                value="<?= $insumo['fecha_caducidad'] ?>">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label class="form-label">Observaciones:</label>
                        <textarea class="form-control" name="observaciones" rows="2"><?= $insumo['observaciones'] ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
        <a href="ver_insumos.php" class="btn btn-secondary mt-3">Volver al Listado</a>
    </div>
</body>
</html>