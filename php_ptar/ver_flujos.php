<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Filtros por fecha (opcional)
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

// Consulta SQL con filtros
$sql = "SELECT 
            id, fecha, nivel_inicial, nivel_final, 
            diferencia_nivel, volumen_generado, 
            tiempo_inicio, tiempo_fin, diferencia_tiempo_min,
            caudal_m3s, observaciones
        FROM flujos
        WHERE fecha BETWEEN ? AND ?
        ORDER BY fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$result = $stmt->get_result();

// Datos para el gráfico (últimos 7 días)
$sql_grafico = "SELECT 
                    DATE(fecha) as fecha_dia, 
                    AVG(caudal_m3s) as caudal_promedio
                FROM flujos
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY fecha_dia
                ORDER BY fecha_dia ASC";
$result_grafico = $conn->query($sql_grafico);
$labels = [];
$data = [];

while ($row = $result_grafico->fetch_assoc()) {
    $labels[] = date("d M", strtotime($row['fecha_dia']));
    $data[] = $row['caudal_promedio'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Flujos - PTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Barra superior -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-graph-up"></i> Histórico de Flujos</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Filtros por fecha -->
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
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-4 align-self-end text-end">
                        <a href="exportar_flujos.php?fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" 
                           class="btn btn-success">
                            <i class="bi bi-file-excel"></i> Exportar a Excel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gráfico de caudal -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title"><i class="bi bi-speedometer2"></i> Caudal Promedio (Últimos 7 días)</h2>
                <div class="chart-container">
                    <canvas id="flowChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card">
            <div class="card-body">
                <h2 class="card-title"><i class="bi bi-table"></i> Registros</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Nivel Inicial (m)</th>
                                <th>Nivel Final (m)</th>
                                <th>Δ Nivel (m)</th>
                                <th>Volumen (m³)</th>
                                <th>Tiempo (min)</th>
                                <th>Caudal (m³/s)</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date("d/m/Y H:i", strtotime($row['fecha'])) ?></td>
                                <td><?= $row['nivel_inicial'] ?></td>
                                <td><?= $row['nivel_final'] ?></td>
                                <td><?= round($row['diferencia_nivel'], 3) ?></td>
                                <td><?= round($row['volumen_generado'], 2) ?></td>
                                <td><?= round($row['diferencia_tiempo_min'], 1) ?></td>
                                <td><?= round($row['caudal_m3s'], 4) ?></td>
                                <td><?= $row['observaciones'] ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-info" title="Editar">
                                        <i class="bi bi-pencil"></i>
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

    <!-- Script del gráfico -->
    <script>
        const flowCtx = document.getElementById('flowChart').getContext('2d');
        new Chart(flowCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Caudal Promedio (m³/s)',
                    data: <?= json_encode($data) ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${context.raw.toFixed(4)} m³/s`
                        }
                    }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'm³/s' }
                    }
                }
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>