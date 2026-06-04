<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: index.html");
    exit();
}

// Obtener fechas del filtro (si existen)
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

// Consulta SQL con filtros
$sql = "SELECT 
            fecha, nivel_inicial, nivel_final, 
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

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Flujos_PTAR_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Requerir la librería PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear un nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Estilo para el encabezado
$styleHeader = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '3498db']]
];

// Encabezados
$sheet->setCellValue('A1', 'FACULTAD DE ESTUDIOS SUPERIORES ACATLÁN');
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A2', 'PLANTA DE TRATAMIENTO DE AGUAS RESIDUALES - REGISTRO DE FLUJOS');
$sheet->mergeCells('A2:J2');
$sheet->setCellValue('A3', 'Fecha de exportación: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A3:J3');

// Títulos de columnas
$sheet->fromArray(
    [
        ['Fecha', 'Nivel Inicial (m)', 'Nivel Final (m)', 'Δ Nivel (m)', 
         'Volumen (m³)', 'Hora Inicio', 'Hora Fin', 'Tiempo (min)', 
         'Caudal (m³/s)', 'Observaciones']
    ],
    null,
    'A5'
);

// Aplicar estilo al encabezado
$sheet->getStyle('A5:J5')->applyFromArray($styleHeader);

// Llenar datos
$row = 6;
while ($fila = $result->fetch_assoc()) {
    $sheet->fromArray(
        [
            date("d/m/Y H:i", strtotime($fila['fecha'])),
            $fila['nivel_inicial'],
            $fila['nivel_final'],
            round($fila['diferencia_nivel'], 3),
            round($fila['volumen_generado'], 2),
            $fila['tiempo_inicio'],
            $fila['tiempo_fin'],
            round($fila['diferencia_tiempo_min'], 1),
            round($fila['caudal_m3s'], 4),
            $fila['observaciones']
        ],
        null,
        "A{$row}"
    );
    $row++;
}

// Autoajustar columnas
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Crear archivo y forzar descarga
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;