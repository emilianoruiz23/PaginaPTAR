<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Filtros
$estado = $_GET['estado'] ?? '';
$equipo = $_GET['equipo'] ?? '';
$sql = "SELECT 
            m.*, 
            u.nombre as usuario_registro
        FROM mantenimientos m
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE 1=1 " .
        ($estado ? " AND m.estado = '$estado'" : "") .
        ($equipo ? " AND m.equipo LIKE '%$equipo%'" : "") .
        " ORDER BY m.fecha_programada ASC";

$result = $conn->query($sql);

// Estadísticas para tarjetas
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(estado = 'Completado') as completados,
        SUM(estado = 'Pendiente') as pendientes
    FROM mantenimientos
")->fetch_assoc();
$porcentaje = $stats['total'] > 0 ? round(($stats['completados'] / $stats['total']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Mantenimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .progress-bar {
            transition: width 0.5s;
        }
        .card-stat {
            border-left: 4px solid;
        }
        .card-stat.completados {
            border-color: #28a745;
        }
        .card-stat.pendientes {
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">🛠️ Gestión de Mantenimientos</h1>
        
        <!-- Tarjetas de Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-stat completados">
                    <div class="card-body">
                        <h5 class="card-title">✅ Completados</h5>
                        <h2><?= $stats['completados'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat pendientes">
                    <div class="card-body">
                        <h5 class="card-title">⏳ Pendientes</h5>
                        <h2><?= $stats['pendientes'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">📅 Progreso Total</h5>
                        <div class="progress mt-2" style="height: 30px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?= $porcentaje ?>%" 
                                aria-valuenow="<?= $porcentaje ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                <?= $porcentaje ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Estado:</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="Pendiente" <?= $estado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="En progreso" <?= $estado == 'En progreso' ? 'selected' : '' ?>>En progreso</option>
                            <option value="Completado" <?= $estado == 'Completado' ? 'selected' : '' ?>>Completado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Equipo:</label>
                        <input type="text" name="equipo" class="form-control" placeholder="Filtrar por equipo..." value="<?= $equipo ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Mantenimientos -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">📋 Listado de Mantenimientos</h5>
                    <a href="agregar_mantenimiento.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Tipo</th>
                                <th>Fecha Programada</th>
                                <th>Estado</th>
                                <th>Registrado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['equipo'] ?></td>
                                <td><?= $row['tipo'] ?></td>
                                <td><?= date("d/m/Y", strtotime($row['fecha_programada'])) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $row['estado'] == 'Completado' ? 'bg-success' : 
                                           ($row['estado'] == 'Pendiente' ? 'bg-warning' : 'bg-info') ?>">
                                        <?= $row['estado'] ?>
                                    </span>
                                </td>
                                <td><?= $row['usuario_registro'] ?></td>
                                <td>
                                    <a href="detalle_mantenimiento.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
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