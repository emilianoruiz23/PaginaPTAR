<?php
session_start();
include 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Consulta SQL para obtener todos los registros
$sql = "SELECT * FROM riegos ORDER BY fecha DESC, hora_inicio DESC";
$result = $conn->query($sql);


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registros de Riego - PTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            margin: 20px auto;
            max-width: 1200px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container table-container">
        <h1 class="my-4 text-center">Registros de Riego</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">¡Registro guardado correctamente!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Término</th>
                        <th>Zona</th>
                        <th>Bomba</th>
                        <th>Volumen (m³)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fecha']) ?></td>
                                <td><?= htmlspecialchars($row['hora_inicio']) ?></td>
                                <td><?= htmlspecialchars($row['hora_termino']) ?></td>
                                <td><?= htmlspecialchars($row['zona_regada']) ?></td>
                                <td><?= htmlspecialchars($row['bomba_utilizada']) ?></td>
                                <td><?= htmlspecialchars($row['volumen_total_m3']) ?></td>
                                <td>
                                    <a href="detalle_riego.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay registros de riegos aún</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Volver al Dashboard</a>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>