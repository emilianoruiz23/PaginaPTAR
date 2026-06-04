<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener muestreos para el calendario (formato JSON para FullCalendar)
if (isset($_GET['get_events'])) {
    $muestreos = $conn->query("SELECT 
        id, normativa, parametro, 
        proxima_fecha as start,
        CONCAT(normativa, ': ', parametro) as title,
        CASE 
            WHEN estado = 'Pendiente' AND proxima_fecha < CURDATE() THEN '#dc3545'
            WHEN estado = 'Pendiente' THEN '#ffc107'
            ELSE '#28a745'
        END as backgroundColor
        FROM muestreos");

    $events = [];
    while ($row = $muestreos->fetch_assoc()) {
        $events[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}

// Actualizar estado de muestreos atrasados
$conn->query("UPDATE muestreos SET estado = 'Atrasado' 
              WHERE estado = 'Pendiente' AND proxima_fecha < CURDATE()");

// Muestreos pendientes para la tabla
$pendientes = $conn->query("SELECT 
    m.*, u.nombre as responsable
    FROM muestreos m
    JOIN usuarios u ON m.responsable_id = u.id
    WHERE m.estado IN ('Pendiente', 'Atrasado')
    ORDER BY m.proxima_fecha ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario de Muestreos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        #calendar {
            max-width: 900px;
            margin: 30px auto;
        }
        .fc-event {
            cursor: pointer;
        }
        .atrasado-badge {
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">📅 Calendario de Muestreos</h1>
        
        <!-- Calendario Interactivo -->
        <div class="card mb-4">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Listado de Muestreos Pendientes -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">📋 Muestreos Pendientes</h5>
                    <a href="agregar_muestreo.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Normativa</th>
                                <th>Parámetro</th>
                                <th>Fecha</th>
                                <th>Punto</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pendientes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['normativa'] ?></td>
                                <td><?= $row['parametro'] ?></td>
                                <td><?= date("d/m/Y", strtotime($row['proxima_fecha'])) ?></td>
                                <td><?= $row['punto_muestreo'] ?></td>
                                <td><?= $row['responsable'] ?></td>
                                <td>
                                    <span class="badge <?= $row['estado'] == 'Atrasado' ? 'bg-danger atrasado-badge' : 'bg-warning' ?>">
                                        <?= $row['estado'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="registrar_muestra.php?muestreo_id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        Registrar
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

    <!-- Scripts para FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'calendario_muestreos.php?get_events=true',
                eventClick: function(info) {
                    alert(
                        'Muestreo: ' + info.event.title + '\n' +
                        'Fecha: ' + info.event.start.toLocaleDateString()
                    );
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>