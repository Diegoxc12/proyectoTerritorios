<?php
require '../vendor/autoload.php'; 
require '../includes/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$spreadsheet = new Spreadsheet();

// Días y tablas
$dias = [
    'Lunes' => 'territorio_lunes',
    'Martes' => 'territorio_martes',
    'Miércoles' => 'territorio_miercoles',
    'Jueves' => 'territorio_jueves',
    'Viernes' => 'territorio_viernes',
    'Sábado' => 'territorio_sabado',
    'Domingo' => 'territorio_domingo'
];

$index = 0;
foreach ($dias as $nombreHoja => $tabla) {
    if ($index > 0) {
        $spreadsheet->createSheet();
    }

    $sheet = $spreadsheet->setActiveSheetIndex($index);
    $sheet->setTitle($nombreHoja);

    try {
        $stmt = $conn->prepare("SELECT id_territorio, territorios_asignado, fecha_asignacion, visible, fecha_expiracion_territorio, tipo FROM $tabla WHERE visible = 1 ORDER BY territorios_asignado ASC");
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener datos de $tabla: " . $e->getMessage());
        $datos = [];
    }

    // Cabeceras
    $headers = ['Territorio Asignado', 'Fecha Asignación', 'Fecha Cuando se termino', 'Tipo'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Filas
    $rowNum = 2;
    foreach ($datos as $fila) {
        $sheet->setCellValue("A$rowNum", $fila['territorios_asignado']);
        $sheet->setCellValue("B$rowNum", $fila['fecha_asignacion']);
        $sheet->setCellValue("C$rowNum", $fila['fecha_expiracion_territorio']);
        $sheet->setCellValue("D$rowNum", $fila['tipo']);
        $rowNum++;
    }
    
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => Color::COLOR_WHITE]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'] // Azul 
        ]
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    
    $lastRow = $rowNum - 1; // Última fila con datos
    $sheet->setAutoFilter("A1:D{$lastRow}");
    
    foreach (range('A', 'D') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // 4. Mejorar visualización de fechas
    $sheet->getStyle('B2:C' . $lastRow)
          ->getNumberFormat()
          ->setFormatCode('dd/mm/yyyy');
    
    $sheet->freezePane('A2');

    $index++;
}

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="territorios_semana.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;