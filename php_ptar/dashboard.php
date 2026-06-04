<?php
session_start();
include 'conexion.php'; // Asegúrate de incluir la conexión

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Consulta para el gráfico (últimos 7 días)
$sql = "SELECT 
            fecha, 
            SUM(volumen_total_m3) as total_agua 
        FROM riegos 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY fecha 
        ORDER BY fecha ASC";

$result = $conn->query($sql);
$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = date("d M", strtotime($row['fecha'])); // Formato corto: "16 Abr"
    $data[] = $row['total_agua'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PTAR FES Acatlán</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .module-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .chart-container {
            height: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Barra superior -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Bienvenido, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span></h1>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
        <!-- Gráfico de consumo -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="module-title">Consumo de Agua (Últimos 7 días)</h2>
                <div class="chart-container">
                    <canvas id="waterChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tarjetas de módulos -->
        <div class="row">
            <!-- Módulo de Riegos -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="module-title">Módulo de Riegos</h2>
                        <div class="d-grid gap-2">
                            <a href="agregar_riego.php" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-circle"></i> Nuevo Registro
                            </a>
                            <a href="ver_riegos.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-list-ul"></i> Ver Registros
                            </a>
                            <?php if ($_SESSION['user_rol'] === 'admin'): ?>
                                <a href="exportar_riegos.php" class="btn btn-info btn-lg">
                                    <i class="bi bi-file-excel"></i> Exportar a Excel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo de Flujos -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="module-title">Módulo de Flujos</h2>
                        <div class="d-grid gap-2">
                            <a href="agregar_flujo.php" class="btn btn-success btn-lg">
                                <i class="bi bi-water"></i> Registrar Flujo
                            </a>
                            <a href="ver_flujos.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-graph-up"></i> Ver Histórico
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Módulo de Calidad del Agua -->
<div class="col-md-4">
    <div class="card">
        <div class="card-body">
            <h2 class="module-title">Calidad del Agua</h2>
            <div class="d-grid gap-2">
                <a href="agregar_calidad.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Nuevo Análisis
                </a>
                <a href="ver_calidad.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-graph-up"></i> Ver Reportes
                </a>
                <?php if ($_SESSION['user_rol'] === 'admin'): ?>
                    <a href="exportar_calidad.php" class="btn btn-info btn-lg">
                        <i class="bi bi-file-excel"></i> Exportar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Módulo de Mantenimientos -->
<div class="col-md-4">
    <div class="card">
        <div class="card-body">
            <h2 class="module-title">Mantenimientos</h2>
            <div class="d-grid gap-2">
                <a href="agregar_mantenimiento.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Nuevo
                </a>
                <a href="ver_mantenimientos.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-list-check"></i> Ver Listado
                </a>
            </div>
            <!-- Widget pequeño de pendientes -->
            <?php 
            $pendientes = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE estado = 'Pendiente'")->fetch_assoc()['total'];
            ?>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong><?= $pendientes ?> mantenimientos pendientes</strong>
            </div>
        </div>
    </div>
</div>
<!-- Módulo de Insumos -->
<div class="col-md-4">
    <div class="card">
        <div class="card-body">
            <h2 class="module-title">Insumos</h2>
            <div class="d-grid gap-2">
                <a href="agregar_insumo.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Nuevo
                </a>
                <a href="ver_insumos.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-seam"></i> Ver Inventario
                </a>
            </div>
            <!-- Alerta de stock bajo -->
            <?php
            $stock_bajo = $conn->query("SELECT COUNT(*) as total FROM insumos WHERE cantidad <= 10")->fetch_assoc()['total'];
            if ($stock_bajo > 0): ?>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong><?= $stock_bajo ?> insumos con stock bajo</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Módulo de Muestreos -->
<div class="col-md-4">
    <div class="card">
        <div class="card-body">
            <h2 class="module-title">Muestreos</h2>
            <div class="d-grid gap-2">
                <a href="calendario_muestreos.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-calendar-check"></i> Calendario
                </a>
                <a href="agregar_muestreo.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Programar
                </a>
            </div>
            <!-- Contador de muestreos atrasados -->
            <?php 
            $atrasados = $conn->query("SELECT COUNT(*) as total FROM muestreos WHERE estado = 'Atrasado'")->fetch_assoc()['total'];
            if ($atrasados > 0): ?>
                <div class="alert alert-danger mt-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong><?= $atrasados ?> muestreos atrasados</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Módulo de Incidentes -->
<div class="col-md-4">
    <div class="card">
        <div class="card-body">
            <h2 class="module-title">Incidentes</h2>
            <div class="d-grid gap-2">
                <a href="reportar_incidente.php" class="btn btn-danger btn-lg">
                    <i class="bi bi-exclamation-triangle"></i> Reportar
                </a>
                <a href="ver_incidentes.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-list-ul"></i> Ver Todos
                </a>
            </div>
            <!-- Contador de incidentes críticos -->
            <?php 
            $criticos = $conn->query("SELECT COUNT(*) as total FROM incidentes WHERE severidad = 'Crítica' AND estado != 'Resuelto'")->fetch_assoc()['total'];
            if ($criticos > 0): ?>
                <div class="alert alert-danger mt-3">
                    <i class="bi bi-exclamation-octagon"></i>
                    <strong><?= $criticos ?> incidentes críticos activos</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <!-- Script del gráfico -->
    <script>
        const ctx = document.getElementById('waterChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Consumo (m³)',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Metros cúbicos (m³)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                }
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>