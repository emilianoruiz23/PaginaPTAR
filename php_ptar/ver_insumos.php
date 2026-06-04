<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Filtros
$nombre = $_GET['nombre'] ?? '';
$stock_bajo = isset($_GET['stock_bajo']);

$sql = "SELECT * FROM insumos WHERE 1=1 " .
    ($nombre ? " AND nombre LIKE '%$nombre%'" : "") .
    ($stock_bajo ? " AND cantidad <= 10" : "") .
    " ORDER BY nombre ASC";

$result = $conn->query($sql);
$total_insumos = $conn->query("SELECT COUNT(*) as total FROM insumos")->fetch_assoc()['total'];
$stock_bajo_count = $conn->query("SELECT COUNT(*) as total FROM insumos WHERE cantidad <= 10")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Insumos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-stat {
            border-left: 4px solid;
        }
        .card-stat.stock {
            border-color: #ffc107;
        }
        .card-stat.total {
            border-color: #17a2b8;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .stock-bajo {
            background-color: #fff3cd !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">📦 Gestión de Insumos</h1>
        
        <!-- Tarjetas de Resumen -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card card-stat total">
                    <div class="card-body">
                        <h5 class="card-title">Insumos Registrados</h5>
                        <h2><?= $total_insumos ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-stat stock">
                    <div class="card-body">
                        <h5 class="card-title">Stock Bajo (<10)</h5>
                        <h2><?= $stock_bajo_count ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Buscar por Nombre:</label>
                        <input type="text" name="nombre" class="form-control" 
                            placeholder="Ej: Cloro" value="<?= $nombre ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="stock_bajo" 
                                id="stock_bajo" <?= $stock_bajo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="stock_bajo">Stock Bajo</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Insumos -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">📋 Listado de Insumos</h5>
                    <a href="agregar_insumo.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Ubicación</th>
                                <th>Caducidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?= $row['cantidad'] <= 10 ? 'stock-bajo' : '' ?>">
                                <td><?= $row['nombre'] ?></td>
                                <td><?= $row['cantidad'] ?></td>
                                <td><?= $row['unidad'] ?></td>
                                <td><?= $row['ubicacion'] ?? 'N/A' ?></td>
                                <td>
                                    <?= $row['fecha_caducidad'] ? date("d/m/Y", strtotime($row['fecha_caducidad'])) : 'N/A' ?>
                                    <?= $row['fecha_caducidad'] && strtotime($row['fecha_caducidad']) < time() ? '⚠️' : '' ?>
                                </td>
                                <td>
                                    <a href="editar_insumo.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>