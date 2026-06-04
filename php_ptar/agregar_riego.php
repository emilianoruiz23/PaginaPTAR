<?php
session_start();
include 'conexion.php';

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger datos (campos requeridos primero)
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_termino = $_POST['hora_termino'];
    $zona_regada = $_POST['zona_regada'];
    $bomba_utilizada = $_POST['bomba_utilizada'];
    $volumen_total_m3 = (float)$_POST['volumen_total_m3']; // Campo obligatorio
    
    // 2. Campos opcionales (con valores por defecto)
    $nivel_tac_inicio = isset($_POST['nivel_tac_inicio']) ? (float)$_POST['nivel_tac_inicio'] : NULL;
    $nivel_tac_termino = isset($_POST['nivel_tac_termino']) ? (float)$_POST['nivel_tac_termino'] : NULL;
    $volumen_estadio_m3 = isset($_POST['volumen_estadio_m3']) ? (float)$_POST['volumen_estadio_m3'] : 0.00;
    $volumen_polvorin_m3 = isset($_POST['volumen_polvorin_m3']) ? (float)$_POST['volumen_polvorin_m3'] : 0.00;
    $volumen_practicas_m3 = isset($_POST['volumen_practicas_m3']) ? (float)$_POST['volumen_practicas_m3'] : 0.00;
    $observaciones = !empty($_POST['observaciones']) ? $_POST['observaciones'] : NULL;

    // Validar campos obligatorios
    if (empty($fecha) || empty($hora_inicio) || empty($hora_termino) || empty($zona_regada) || empty($bomba_utilizada) || empty($volumen_total_m3)) {
        die("<div class='alert alert-danger'>Error: Faltan campos obligatorios</div>");
    }

    // Insertar en la base de datos
   // En la parte de la inserción SQL:
    // 3. Consulta SQL optimizada (solo campos necesarios)
    $sql = "INSERT INTO riegos (
        fecha, hora_inicio, hora_termino, zona_regada, bomba_utilizada, volumen_total_m3,
        nivel_tac_inicio, nivel_tac_termino, volumen_estadio_m3, volumen_polvorin_m3,
        volumen_practicas_m3, observaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    // 4. Vincular parámetros (tipos exactos)
    $stmt->bind_param(
        "sssssdssssss", // Tipos: 5 strings, 1 double, 6 strings (NULLables)
        $fecha,
        $hora_inicio,
        $hora_termino,
        $zona_regada,
        $bomba_utilizada,
        $volumen_total_m3,
        $nivel_tac_inicio,
        $nivel_tac_termino,
        $volumen_estadio_m3,
        $volumen_polvorin_m3,
        $volumen_practicas_m3,
        $observaciones
    );

    if ($stmt->execute()) {
        header("Location: ver_riegos.php?success=1");
        exit();
    } else {
        die("Error al guardar: " . $stmt->error);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Riego - PTAR FES Acatlán</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .form-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title text-center">
                <i class="fas fa-tint me-2"></i>Registrar Nuevo Riego
            </h2>
            
            <form method="POST" action="">
                <div class="row g-3">
                    <!-- Fila 1: Fecha y Zona -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="fecha" name="fecha" required>
                            <label for="fecha" class="required-field">Fecha</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="zona_regada" name="zona_regada" required>
                                <option value="" selected disabled>Seleccione...</option>
                                <option value="Estadio">Estadio</option>
                                <option value="Polvorín">Polvorín</option>
                                <option value="Prácticas">Prácticas</option>
                                <option value="Otra">Otra área</option>
                            </select>
                            <label for="zona_regada" class="required-field">Zona a Regar</label>
                        </div>
                    </div>

                    <!-- Fila 2: Horas -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                            <label for="hora_inicio" class="required-field">Hora Inicio</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="time" class="form-control" id="hora_termino" name="hora_termino" required>
                            <label for="hora_termino" class="required-field">Hora Término</label>
                        </div>
                    </div>

                    <!-- Fila 3: Bomba y Volumen Total -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="bomba_utilizada" name="bomba_utilizada" required>
                                <option value="" selected disabled>Seleccione...</option>
                                <option value="BCM-03A">BCM-03A</option>
                                <option value="BCM-03B">BCM-03B</option>
                                <option value="BCM-03R">BCM-03R</option>
                            </select>
                            <label for="bomba_utilizada" class="required-field">Bomba Utilizada</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="volumen_total_m3" name="volumen_total_m3" required>
                            <label for="volumen_total_m3" class="required-field">Volumen Total (m³)</label>
                        </div>
                    </div>

                    <!-- Fila 4: Niveles TAC -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="nivel_tac_inicio" name="nivel_tac_inicio">
                            <label for="nivel_tac_inicio">Nivel TAC Inicio (m)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="nivel_tac_termino" name="nivel_tac_termino">
                            <label for="nivel_tac_termino">Nivel TAC Término (m)</label>
                        </div>
                    </div>

                    <!-- Fila 5: Volúmenes por área -->
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="volumen_estadio_m3" name="volumen_estadio_m3" value="0.00">
                            <label for="volumen_estadio_m3">Volumen Estadio (m³)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="volumen_polvorin_m3" name="volumen_polvorin_m3" value="0.00">
                            <label for="volumen_polvorin_m3">Volumen Polvorín (m³)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="volumen_practicas_m3" name="volumen_practicas_m3" value="0.00">
                            <label for="volumen_practicas_m3">Volumen Prácticas (m³)</label>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="observaciones" name="observaciones" style="height: 100px"></textarea>
                            <label for="observaciones">Observaciones</label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="col-12 mt-4">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times-circle me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Registro
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Validación de campos -->
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios.');
            }
        });
    </script>
</body>
</html>