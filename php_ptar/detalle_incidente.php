<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$id = $_GET['id'] ?? die("ID no especificado");

// Obtener datos del incidente
$sql = "SELECT 
            i.*, 
            ur.nombre as reportado_por, ur.rol as rol_reportero,
            ua.nombre as asignado_a, ua.rol as rol_asignado
        FROM incidentes i
        LEFT JOIN usuarios ur ON i.usuario_reporta_id = ur.id
        LEFT JOIN usuarios ua ON i.usuario_asignado_id = ua.id
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$incidente = $stmt->get_result()->fetch_assoc();

if (!$incidente) die("Incidente no encontrado");

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'];
    $usuario_asignado_id = $_POST['usuario_asignado_id'] ?? NULL;
    $solucion = htmlspecialchars($_POST['solucion'] ?? '');
    
    // Actualizar fecha de cierre si se marca como resuelto
    $fecha_cierre = ($estado == 'Resuelto') ? date('Y-m-d H:i:s') : NULL;

    $update_sql = "UPDATE incidentes SET 
        estado = ?, 
        usuario_asignado_id = ?, 
        solucion = ?,
        fecha_cierre = ?
        WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param(
        "sisss",
        $estado,
        $usuario_asignado_id,
        $solucion,
        $fecha_cierre,
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Incidente actualizado correctamente.";
        header("Refresh:0"); // Recargar la página
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Obtener técnicos disponibles (roles 'tecnico' o 'admin')
$tecnicos = $conn->query("SELECT id, nombre FROM usuarios WHERE rol IN ('tecnico', 'admin')");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Incidente #<?= $incidente['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .severidad-badge {
            font-size: 1em;
            padding: 8px 12px;
        }
        .evidencia-img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
        }
        .timeline-item:last-child {
            border-left: 2px solid transparent;
        }
        .timeline-dot {
            position: absolute;
            left: -9px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Incidente #<?= $incidente['id'] ?>: <?= $incidente['titulo'] ?></h2>
                <span class="badge 
                    <?= $incidente['estado'] == 'Reportado' ? 'bg-secondary' : 
                       ($incidente['estado'] == 'En revisión' ? 'bg-info' : 
                       ($incidente['estado'] == 'En reparación' ? 'bg-primary' : 'bg-success')) ?> 
                    severidad-badge">
                    <?= $incidente['estado'] ?>
                </span>
            </div>
            <div class="card-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>📋 Detalles Generales</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p><strong>Tipo:</strong> <?= $incidente['tipo'] ?></p>
                                <p><strong>Severidad:</strong> 
                                    <span class="badge bg-<?= 
                                        $incidente['severidad'] == 'Crítica' ? 'danger' : 
                                        ($incidente['severidad'] == 'Alta' ? 'warning' : 
                                        ($incidente['severidad'] == 'Media' ? 'info' : 'success')) ?>">
                                        <?= $incidente['severidad'] ?>
                                    </span>
                                </p>
                                <p><strong>Ubicación:</strong> <?= $incidente['ubicacion'] ?></p>
                                <p><strong>Reportado por:</strong> <?= $incidente['reportado_por'] ?> (<?= $incidente['rol_reportero'] ?>)</p>
                                <p><strong>Fecha reporte:</strong> <?= date("d/m/Y H:i", strtotime($incidente['fecha_reporte'])) ?></p>
                                <?php if ($incidente['fecha_cierre']): ?>
                                    <p><strong>Fecha cierre:</strong> <?= date("d/m/Y H:i", strtotime($incidente['fecha_cierre'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>📝 Descripción</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <?= nl2br($incidente['descripcion']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evidencias -->
                <?php if ($incidente['evidencias']): ?>
                <div class="mb-4">
                    <h5>📸 Evidencias</h5>
                    <div class="d-flex flex-wrap">
                        <?php 
                        $evidencias = explode(',', $incidente['evidencias']);
                        foreach ($evidencias as $evidencia): 
                            $ruta = "uploads/incidentes/" . $evidencia;
                            $extension = pathinfo($ruta, PATHINFO_EXTENSION);
                        ?>
                            <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <a href="<?= $ruta ?>" target="_blank">
                                    <img src="<?= $ruta ?>" class="evidencia-img" style="max-height: 150px;">
                                </a>
                            <?php elseif ($extension == 'pdf'): ?>
                                <a href="<?= $ruta ?>" target="_blank" class="btn btn-outline-danger me-2 mb-2">
                                    <i class="bi bi-file-earmark-pdf"></i> Ver PDF
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Formulario de Gestión -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🛠️ Gestión del Incidente</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Estado Actual:</label>
                                    <select name="estado" class="form-select" required>
                                        <option value="Reportado" <?= $incidente['estado'] == 'Reportado' ? 'selected' : '' ?>>Reportado</option>
                                        <option value="En revisión" <?= $incidente['estado'] == 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                                        <option value="En reparación" <?= $incidente['estado'] == 'En reparación' ? 'selected' : '' ?>>En reparación</option>
                                        <option value="Resuelto" <?= $incidente['estado'] == 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                        <option value="Cancelado" <?= $incidente['estado'] == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Asignar a:</label>
                                    <select name="usuario_asignado_id" class="form-select">
                                        <option value="">-- Seleccionar --</option>
                                        <?php while ($tecnico = $tecnicos->fetch_assoc()): ?>
                                            <option value="<?= $tecnico['id'] ?>" 
                                                <?= $incidente['usuario_asignado_id'] == $tecnico['id'] ? 'selected' : '' ?>>
                                                <?= $tecnico['nombre'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Solución Aplicada:</label>
                                <textarea class="form-control" name="solucion" rows="3"
                                    placeholder="Describa la solución técnica aplicada..."><?= $incidente['solucion'] ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                        </form>
                    </div>
                </div>

                <!-- Historial (simulado) -->
                <div class="card">
                    <div class="card-header">
                        <h5>🕒 Historial</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <strong>Reportado</strong> - <?= date("d/m/Y H:i", strtotime($incidente['fecha_reporte'])) ?>
                                <p>Por: <?= $incidente['reportado_por'] ?></p>
                            </div>
                            <?php if ($incidente['estado'] != 'Reportado'): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <strong>En revisión</strong> - <?= date("d/m/Y H:i", strtotime($incidente['fecha_reporte']) + 3600) ?>
                                <p>Asignado a: <?= $incidente['asignado_a'] ?? 'N/A' ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (in_array($incidente['estado'], ['En reparación', 'Resuelto'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <strong>En reparación</strong> - <?= date("d/m/Y H:i", strtotime($incidente['fecha_reporte']) + 7200) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($incidente['estado'] == 'Resuelto'): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <strong>Resuelto</strong> - <?= date("d/m/Y H:i", strtotime($incidente['fecha_cierre'])) ?>
                                <p><?= nl2br($incidente['solucion']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a href="ver_incidentes.php" class="btn btn-secondary mt-3">Volver al Listado</a>
    </div>
</body>
</html>