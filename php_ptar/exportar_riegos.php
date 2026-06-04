<?php
session_start();
if ($_SESSION['user_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

include 'conexion.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=riegos_ptar.xls");

$sql = "SELECT fecha, hora_inicio, hora_termino, zona_regada, volumen_total_m3 FROM riegos ORDER BY fecha DESC";
$result = $conn->query($sql);

echo "Fecha\tHora Inicio\tHora Término\tZona\tVolumen (m³)\n";
while ($row = $result->fetch_assoc()) {
    echo $row['fecha'] . "\t" . $row['hora_inicio'] . "\t" . $row['hora_termino'] . "\t" . $row['zona_regada'] . "\t" . $row['volumen_total_m3'] . "\n";
}
?>