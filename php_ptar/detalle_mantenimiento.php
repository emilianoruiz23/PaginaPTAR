<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$id = $_GET['id'] ?? die("ID no especificado");
$sql = "SELECT 
            m.*, 
            u.nombre as usuario_registro
        FROM mantenimientos m
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$mantenimiento = $stmt->get_result()->fetch_assoc();

if (!$mantenimiento) die("Mantenimiento no encontrado");

// Actualizar estado (si se envía el formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_estado = $_POST['estado'];
    $fecha_realizacion = $nuevo_estado == 'Completado' ? date('Y-m-d H:i:s') : NULL;
    
    $update_sql = "UPDATE mantenimientos 
                   SET estado = ?, fecha_realizacion = ?
                   WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $nuevo_estado, $fecha_realizacion, $id);
    $stmt->execute();
    
    $_SESSION['success'] = "Estado actualizado correctamente.";
    header("Refresh:0"); // Recargar la página
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Mantenimiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🔍 Detalle de Mantenimiento</h2>
            </div>
            <div class="card-body">
                <!-- Datos Básicos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Equipo: <span class="text-primary"><?= $mantenimiento['equipo'] ?></span></h5>
                        <p><strong>Tipo:</strong> <?= $mantenimiento['tipo'] ?></p>
                        <p><strong>Estado:</strong> 
                            <span class="badge 
                                <?= $mantenimiento['estado'] == 'Completado' ? 'bg-success' : 
                                   ($mantenimiento['estado'] == 'Pendiente' ? 'bg-warning' : 'bg-info') ?>">
                                <?= $mantenimiento['estado'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha Programada:</strong> <?= date("d/m/Y", strtotime($mantenimiento['fecha_programada'])) ?></p>
                        <?php if ($mantenimiento['fecha_realizacion']): ?>
                            <p><strong>Fecha Realización:</strong> <?= date("d/m/Y H:i", strtotime($mantenimiento['fecha_realizacion'])) ?></p>
                        <?php endif; ?>
                        <p><strong>Registrado por:</strong> <?= $mantenimiento['usuario_registro'] ?></p>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-4">
                    <h5>📝 Descripción</h5>
                    <div class="card bg-light">
                        <div class="card-body">
                            <?= nl2br($mantenimiento['descripcion']) ?>
                        </div>
                    </div>
                </div>

                <!-- Evidencia -->
                <?php if ($mantenimiento['evidencia']): ?>
                <div class="mb-4">
                    <h5>📎 Evidencia Adjunta</h5>
                    <a href="<?= $mantenimiento['evidencia'] ?>" class="btn btn-outline-secondary" target="_blank">
                        Ver Archivo
                    </a>
                </div>
                <?php endif; ?>

                <!-- Formulario de Actualización -->
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Cambiar Estado:</label>
                            <select name="estado" class="form-select" required>
                                <option value="Pendiente" <?= $mantenimiento['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="En progreso" <?= $mantenimiento['estado'] == 'En progreso' ? 'selected' : '' ?>>En progreso</option>
                                <option value="Completado" <?= $mantenimiento['estado'] == 'Completado' ? 'selected' : '' ?>>Completado</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                Actualizar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <a href="ver_mantenimientos.php" class="btn btn-secondary mt-3">Volver al Listado</a>
    </div>
</body>
</html>