<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Filtros
$estado = $_GET['estado'] ?? '';
$severidad = $_GET['severidad'] ?? '';
$tipo = $_GET['tipo'] ?? '';

// Función para obtener incidentes por estado con filtros
function getIncidentesPorEstado($conn, $estado_incidente, $filtros = []) {
    $where = ["i.estado = '$estado_incidente'"];
    
    if (!empty($filtros['severidad'])) {
        $where[] = "i.severidad = '{$filtros['severidad']}'";
    }
    if (!empty($filtros['tipo'])) {
        $where[] = "i.tipo = '{$filtros['tipo']}'";
    }
    if (!empty($filtros['estado'])) {
        $where[] = "i.estado = '{$filtros['estado']}'";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT 
                i.*, 
                ur.nombre as reportado_por,
                ua.nombre as asignado_a
            FROM incidentes i
            LEFT JOIN usuarios ur ON i.usuario_reporta_id = ur.id
            LEFT JOIN usuarios ua ON i.usuario_asignado_id = ua.id
            $whereClause
            ORDER BY 
                CASE i.severidad
                    WHEN 'Crítica' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Media' THEN 3
                    ELSE 4
                END,
                i.fecha_reporte DESC";

    return $conn->query($sql);
}

// Configurar filtros
$filtros = [];
if ($severidad) $filtros['severidad'] = $severidad;
if ($tipo) $filtros['tipo'] = $tipo;
if ($estado) $filtros['estado'] = $estado;

// Obtener datos para cada columna
$reportados = getIncidentesPorEstado($conn, 'Reportado', $filtros);
$en_revision = getIncidentesPorEstado($conn, 'En revisión', $filtros);
$en_reparacion = getIncidentesPorEstado($conn, 'En reparación', $filtros);
$resueltos = getIncidentesPorEstado($conn, 'Resuelto', $filtros);

// Estadísticas
$stats_where = [];
if ($estado) $stats_where[] = "estado = '$estado'";
if ($severidad) $stats_where[] = "severidad = '$severidad'";
if ($tipo) $stats_where[] = "tipo = '$tipo'";

$stats_whereClause = !empty($stats_where) ? 'WHERE ' . implode(' AND ', $stats_where) : '';

$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(estado = 'Reportado') as reportados,
                SUM(estado = 'En revisión') as en_revision,
                SUM(estado = 'En reparación') as en_reparacion,
                SUM(estado = 'Resuelto') as resueltos,
                SUM(severidad = 'Crítica') as criticos
            FROM incidentes
            $stats_whereClause";

$stats = $conn->query($stats_sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Incidentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kanban-column {
            min-height: 600px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .incidente-card {
            cursor: grab;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .incidente-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .severidad-critica {
            border-left: 4px solid #dc3545;
        }
        .severidad-alta {
            border-left: 4px solid #fd7e14;
        }
        .severidad-media {
            border-left: 4px solid #ffc107;
        }
        .severidad-baja {
            border-left: 4px solid #28a745;
        }
        .badge-estado {
            font-size: 0.8em;
        }
        .img-thumbnail {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">🚨 Gestión de Incidentes</h1>
        
        <!-- Tarjetas de Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total</h5>
                        <h2><?= $stats['total'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning bg-opacity-25">
                    <div class="card-body text-center">
                        <h5 class="card-title">Reportados</h5>
                        <h2><?= $stats['reportados'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info bg-opacity-25">
                    <div class="card-body text-center">
                        <h5 class="card-title">En Revisión</h5>
                        <h2><?= $stats['en_revision'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-primary bg-opacity-25">
                    <div class="card-body text-center">
                        <h5 class="card-title">En Reparación</h5>
                        <h2><?= $stats['en_reparacion'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success bg-opacity-25">
                    <div class="card-body text-center">
                        <h5 class="card-title">Resueltos</h5>
                        <h2><?= $stats['resueltos'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger bg-opacity-25">
                    <div class="card-body text-center">
                        <h5 class="card-title">Críticos</h5>
                        <h2><?= $stats['criticos'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado:</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="Reportado" <?= $estado == 'Reportado' ? 'selected' : '' ?>>Reportado</option>
                            <option value="En revisión" <?= $estado == 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                            <option value="En reparación" <?= $estado == 'En reparación' ? 'selected' : '' ?>>En reparación</option>
                            <option value="Resuelto" <?= $estado == 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Severidad:</label>
                        <select name="severidad" class="form-select">
                            <option value="">Todas</option>
                            <option value="Crítica" <?= $severidad == 'Crítica' ? 'selected' : '' ?>>Crítica</option>
                            <option value="Alta" <?= $severidad == 'Alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="Media" <?= $severidad == 'Media' ? 'selected' : '' ?>>Media</option>
                            <option value="Baja" <?= $severidad == 'Baja' ? 'selected' : '' ?>>Baja</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo:</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="Fuga" <?= $tipo == 'Fuga' ? 'selected' : '' ?>>Fuga</option>
                            <option value="Falla eléctrica" <?= $tipo == 'Falla eléctrica' ? 'selected' : '' ?>>Falla eléctrica</option>
                            <option value="Alerta de calidad" <?= $tipo == 'Alerta de calidad' ? 'selected' : '' ?>>Alerta de calidad</option>
                            <option value="Equipo dañado" <?= $tipo == 'Equipo dañado' ? 'selected' : '' ?>>Equipo dañado</option>
                            <option value="Seguridad" <?= $tipo == 'Seguridad' ? 'selected' : '' ?>>Seguridad</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        <a href="ver_incidentes.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tablero Kanban -->
        <div class="row">
            <!-- Columna: Reportado -->
            <div class="col-md-3">
                <div class="kanban-column">
                    <h5 class="text-center mb-3">📝 Reportado</h5>
                    <?php while ($incidente = $reportados->fetch_assoc()): 
                        $severidad_class = strtolower(str_replace(' ', '-', "severidad-".$incidente['severidad']));
                    ?>
                        <div class="card incidente-card <?= $severidad_class ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= $incidente['titulo'] ?></h6>
                                <span class="badge bg-secondary badge-estado mb-2">
                                    <?= $incidente['estado'] ?>
                                </span>
                                <p class="card-text small">
                                    <strong>Ubicación:</strong> <?= $incidente['ubicacion'] ?><br>
                                    <strong>Reportado por:</strong> <?= $incidente['reportado_por'] ?><br>
                                    <strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($incidente['fecha_reporte'])) ?>
                                </p>
                                <a href="detalle_incidente.php?id=<?= $incidente['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Columna: En Revisión -->
            <div class="col-md-3">
                <div class="kanban-column">
                    <h5 class="text-center mb-3">🔍 En Revisión</h5>
                    <?php while ($incidente = $en_revision->fetch_assoc()): 
                        $severidad_class = strtolower(str_replace(' ', '-', "severidad-".$incidente['severidad']));
                    ?>
                        <div class="card incidente-card <?= $severidad_class ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= $incidente['titulo'] ?></h6>
                                <span class="badge bg-info text-dark badge-estado mb-2">
                                    <?= $incidente['estado'] ?>
                                </span>
                                <p class="card-text small">
                                    <strong>Asignado a:</strong> <?= $incidente['asignado_a'] ?? 'No asignado' ?><br>
                                    <strong>Severidad:</strong> <span class="badge bg-<?= 
                                        $incidente['severidad'] == 'Crítica' ? 'danger' : 
                                        ($incidente['severidad'] == 'Alta' ? 'warning' : 
                                        ($incidente['severidad'] == 'Media' ? 'info' : 'success')) ?>">
                                        <?= $incidente['severidad'] ?>
                                    </span>
                                </p>
                                <a href="detalle_incidente.php?id=<?= $incidente['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Columna: En Reparación -->
            <div class="col-md-3">
                <div class="kanban-column">
                    <h5 class="text-center mb-3">🛠️ En Reparación</h5>
                    <?php while ($incidente = $en_reparacion->fetch_assoc()): 
                        $severidad_class = strtolower(str_replace(' ', '-', "severidad-".$incidente['severidad']));
                    ?>
                        <div class="card incidente-card <?= $severidad_class ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= $incidente['titulo'] ?></h6>
                                <span class="badge bg-primary badge-estado mb-2">
                                    <?= $incidente['estado'] ?>
                                </span>
                                <p class="card-text small">
                                    <strong>Técnico:</strong> <?= $incidente['asignado_a'] ?><br>
                                    <strong>Días en reparación:</strong> 
                                    <?= round((time() - strtotime($incidente['fecha_reporte'])) / (60 * 60 * 24)) ?>
                                </p>
                                <a href="detalle_incidente.php?id=<?= $incidente['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    Ver Progreso
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Columna: Resuelto -->
            <div class="col-md-3">
                <div class="kanban-column">
                    <h5 class="text-center mb-3">✅ Resuelto</h5>
                    <?php while ($incidente = $resueltos->fetch_assoc()): 
                        $severidad_class = strtolower(str_replace(' ', '-', "severidad-".$incidente['severidad']));
                    ?>
                        <div class="card incidente-card <?= $severidad_class ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= $incidente['titulo'] ?></h6>
                                <span class="badge bg-success badge-estado mb-2">
                                    Resuelto
                                </span>
                                <p class="card-text small">
                                    <strong>Fecha cierre:</strong> <?= date("d/m/Y", strtotime($incidente['fecha_cierre'])) ?><br>
                                    <strong>Tiempo total:</strong> 
                                    <?= round((strtotime($incidente['fecha_cierre']) - strtotime($incidente['fecha_reporte'])) / (60 * 60 * 24)) ?> días
                                </p>
                                <a href="detalle_incidente.php?id=<?= $incidente['id'] ?>" class="btn btn-sm btn-outline-success w-100">
                                    Ver Solución
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Botón de Nuevo Incidente -->
        <div class="text-center mt-4">
            <a href="reportar_incidente.php" class="btn btn-danger btn-lg">
                <i class="bi bi-plus-circle"></i> Reportar Nuevo Incidente
            </a>
        </div>
    </div>
</body>
</html>