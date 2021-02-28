<?php
include_once "vendor/autoload.php";
include_once "functions.php";
$start = date("Y-m-d");
$end = date("Y-m-d");
if (isset($_GET["start"])) {
    $start = $_GET["start"];
}
if (isset($_GET["end"])) {
    $end = $_GET["end"];
}
$employees = getEmployeesWithAttendanceCount($start, $end);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$documento = new Spreadsheet();
$nombreDelDocumento = "Reporte.xlsx";
$hoja = $documento->getActiveSheet();
$hoja->setTitle("Hoja 1");
$hoja->setCellValueByColumnAndRow(1, 1, sprintf("Desde %s a %s", $start, $end));
$hoja->setCellValueByColumnAndRow(1, 2, "Empleado");
$hoja->setCellValueByColumnAndRow(2, 2, "Asistencias");
$hoja->setCellValueByColumnAndRow(3, 2, "Ausencias");
$y = 3;
foreach ($employees as $employee) {
    $hoja->setCellValueByColumnAndRow(1, $y, $employee->name);
    $hoja->setCellValueByColumnAndRow(2, $y, $employee->presence_count);
    $hoja->setCellValueByColumnAndRow(3, $y, $employee->absence_count);
    $y++;
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $nombreDelDocumento . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($documento, 'Xlsx');
$writer->save('php://output');
exit;
