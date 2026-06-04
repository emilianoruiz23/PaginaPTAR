<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado");
}

// Consulta (similar a ver_calidad.php)
$sql = "SELECT * FROM calidad_agua ORDER BY fecha_muestreo DESC";
$result = $conn->query($sql);

// Configurar Excel
require 'vendor/autoload.php';
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$sheet->setCellValue('A1', 'Reporte de Calidad del Agua - PTAR');
$sheet->fromArray(
    ['Fecha', 'Punto', 'pH', 'Turbidez', 'O₂ Disuelto', 'Conductividad', 'Temperatura', 'DBO5', 'Sólidos'],
    null,
    'A3'
);

// Datos
$row = 4;
while ($data = $result->fetch_assoc()) {
    $sheet->fromArray([
        $data['fecha_muestreo'],
        $data['punto_muestreo'],
        $data['ph'],
        $data['turbidez'],
        $data['oxigeno_disuelto'],
        $data['conductividad'],
        $data['temperatura'],
        $data['dbo5'],
        $data['solidos_suspendidos']
    ], null, "A{$row}");
    $row++;
}

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Calidad_Agua_' . date('Y-m-d') . '.xlsx"');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');