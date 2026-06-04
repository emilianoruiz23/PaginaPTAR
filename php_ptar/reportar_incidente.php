<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar formulario
    $titulo = htmlspecialchars($_POST['titulo']);
    $tipo = $_POST['tipo'];
    $ubicacion = htmlspecialchars($_POST['ubicacion']);
    $descripcion = htmlspecialchars($_POST['descripcion']);
    $severidad = $_POST['severidad'];
    $usuario_reporta_id = $_SESSION['user_id'];
    
    // Subir evidencia
    $evidencias = '';
    if (isset($_FILES['evidencias'])) {
        $targetDir = "uploads/incidentes/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileNames = [];
        foreach ($_FILES['evidencias']['tmp_name'] as $key => $tmpName) {
            $fileName = uniqid() . '_' . basename($_FILES['evidencias']['name'][$key]);
            if (move_uploaded_file($tmpName, $targetDir . $fileName)) {
                $fileNames[] = $fileName;
            }
        }
        $evidencias = implode(',', $fileNames);
    }

    $sql = "INSERT INTO incidentes (
        titulo, tipo, ubicacion, descripcion, 
        severidad, usuario_reporta_id, evidencias
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssis", $titulo, $tipo, $ubicacion, $descripcion, $severidad, $usuario_reporta_id, $evidencias);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Incidente reportado correctamente. ID: " . $stmt->insert_id;
        header("Location: ver_incidentes.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportar Incidente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .severidad-option {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        .severidad-option:hover {
            opacity: 0.8;
        }
        #preview-container img {
            max-width: 150px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">⚠️ Reportar Incidente</h2>
            <form method="POST" enctype="multipart/form-data">
                <!-- Información Básica -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Título del Incidente:</label>
                        <input type="text" class="form-control" name="titulo" 
                            placeholder="Ej: Fuga en tubería de salida" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo:</label>
                        <select class="form-select" name="tipo" required>
                            <option value="Fuga">Fuga</option>
                            <option value="Falla eléctrica">Falla eléctrica</option>
                            <option value="Alerta de calidad">Alerta de calidad</option>
                            <option value="Equipo dañado">Equipo dañado</option>
                            <option value="Seguridad">Seguridad</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <!-- Ubicación y Severidad -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ubicación Exacta:</label>
                        <input type="text" class="form-control" name="ubicacion" 
                            placeholder="Ej: Reactor Biológico, Nivel 2" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Severidad:</label>
                        <div class="d-flex flex-column">
                            <label class="severidad-option bg-danger text-white">
                                <input type="radio" name="severidad" value="Crítica" required> Crítica (Detiene operaciones)
                            </label>
                            <label class="severidad-option bg-warning text-dark">
                                <input type="radio" name="severidad" value="Alta"> Alta (Afecta procesos)
                            </label>
                            <label class="severidad-option bg-info text-white">
                                <input type="radio" name="severidad" value="Media"> Media (Reparación necesaria)
                            </label>
                            <label class="severidad-option bg-success text-white">
                                <input type="radio" name="severidad" value="Baja"> Baja (Mantenimiento menor)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label">Descripción Detallada:</label>
                    <textarea class="form-control" name="descripcion" rows="4" 
                        placeholder="Describa qué ocurrió, posibles causas y efectos observados..." required></textarea>
                </div>

                <!-- Evidencias -->
                <div class="mb-3">
                    <label class="form-label">Evidencias (Fotos/PDF):</label>
                    <input type="file" class="form-control" name="evidencias[]" multiple accept="image/*,.pdf">
                    <small class="text-muted">Máx. 5 archivos (JPEG, PNG o PDF)</small>
                    <div id="preview-container" class="mt-2"></div>
                </div>

                <button type="submit" class="btn btn-danger w-100">Reportar Incidente</button>
            </form>
        </div>
    </div>

    <script>
        // Vista previa de imágenes
        document.querySelector('input[name="evidencias[]"]').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('preview-container');
            previewContainer.innerHTML = '';
            
            for (const file of e.target.files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.title = file.name;
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } else if (file.type === 'application/pdf') {
                    const pdfBadge = document.createElement('span');
                    pdfBadge.className = 'badge bg-danger me-2';
                    pdfBadge.innerHTML = 'PDF: ' + file.name;
                    previewContainer.appendChild(pdfBadge);
                }
            }
        });
    </script>
</body>
</html>