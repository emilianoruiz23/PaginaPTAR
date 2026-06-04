<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$punto_muestreo = $_GET['punto_muestreo'] ?? '';

// Consulta con filtros
$sql = "SELECT * FROM calidad_agua 
        WHERE fecha_muestreo BETWEEN ? AND ? 
        " . ($punto_muestreo ? "AND punto_muestreo = ?" : "") . "
        ORDER BY fecha_muestreo DESC";

$stmt = $conn->prepare($sql);
if ($punto_muestreo) {
    $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $punto_muestreo);
} else {
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
}
$stmt->execute();
$result = $stmt->get_result();

// Datos para gráficos (últimos 7 días)
$sql_grafico = "SELECT 
                    DATE(fecha_muestreo) as fecha, 
                    AVG(ph) as ph_promedio,
                    AVG(oxigeno_disuelto) as oxigeno_promedio
                FROM calidad_agua
                WHERE fecha_muestreo >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY fecha
                ORDER BY fecha ASC";
$result_grafico = $conn->query($sql_grafico);
$labels = [];
$ph_data = [];
$oxigeno_data = [];

while ($row = $result_grafico->fetch_assoc()) {
    $labels[] = date("d M", strtotime($row['fecha']));
    $ph_data[] = $row['ph_promedio'];
    $oxigeno_data[] = $row['oxigeno_promedio'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monitoreo de Calidad del Agua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }
        .card-parametro {
            border-left: 4px solid #3498db;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">📈 Monitoreo de Calidad del Agua</h1>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin:</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Punto de Muestreo:</label>
                        <select name="punto_muestreo" class="form-select">
                            <option value="">Todos</option>
                            <option value="Entrada PTAR" <?= $punto_muestreo == 'Entrada PTAR' ? 'selected' : '' ?>>Entrada PTAR</option>
                            <option value="Salida Reactor" <?= $punto_muestreo == 'Salida Reactor' ? 'selected' : '' ?>>Salida Reactor</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">📉 pH (Últimos 7 días)</h5>
                        <div class="chart-container">
                            <canvas id="phChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">📈 Oxígeno Disuelto (Últimos 7 días)</h5>
                        <div class="chart-container">
                            <canvas id="oxigenoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">📋 Registros Recientes</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Punto</th>
                                <th>pH</th>
                                <th>O₂ (mg/L)</th>
                                <th>Temp (°C)</th>
                                <th>DBO5</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date("d/m/Y H:i", strtotime($row['fecha_muestreo'])) ?></td>
                                <td><?= $row['punto_muestreo'] ?></td>
                                <td><?= $row['ph'] ?></td>
                                <td><?= $row['oxigeno_disuelto'] ?></td>
                                <td><?= $row['temperatura'] ?></td>
                                <td><?= $row['dbo5'] ?? 'N/A' ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-info">Detalles</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de gráficos -->
    <script>
        // Gráfico de pH
        const phCtx = document.getElementById('phChart').getContext('2d');
        new Chart(phCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'pH',
                    data: <?= json_encode($ph_data) ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        min: 0,
                        max: 14,
                        ticks: {
                            stepSize: 2
                        }
                    }
                }
            }
        });

        // Gráfico de Oxígeno Disuelto
        const oxigenoCtx = document.getElementById('oxigenoChart').getContext('2d');
        new Chart(oxigenoCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Oxígeno Disuelto (mg/L)',
                    data: <?= json_encode($oxigeno_data) ?>,
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>