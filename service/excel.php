<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function GetExcel($data) {
    require 'vendor/autoload.php';



    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'id карты');
    $sheet->setCellValue('B1', 'результат');
    $sheet->setCellValue('C1', 'Фамилия');
    $sheet->setCellValue('D1', 'Имя');
    $sheet->setCellValue('E1', 'Отчество');
    $sheet->setCellValue('F1', 'Дата');
    $sheet->setCellValue('G1', 'Наличие патологии интеллектуальной сферы');
    $sheet->setCellValue('H1', 'Наличие патологии физической сферы');
    $sheet->setCellValue('I1', 'Качество
    предоставления
    соответствующей
    медицинской помощи
    ');


    $count = 2;

    foreach($data as $row) {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A'. $count, $row['card_id']);
        $sheet->setCellValue('B'. $count, $row['result']);
        $sheet->setCellValue('C'. $count, $row['first_name']);
        $sheet->setCellValue('D'. $count, $row['last_name']);
        $sheet->setCellValue('E'. $count, $row['patronymic']);
        $sheet->setCellValue('F'. $count, $row['date']);
        $sheet->setCellValue('G'. $count, 'Диагноз установлен официально Вопрос по установлению диагноза ');
        $sheet->setCellValue('H'. $count, 'Здоров');
        $sheet->setCellValue('I'. $count, 'Несовершеннолетний
        состоит на
        соответствующем его
        заболеванию
        (нарушению) виде учета,
        регулярно проходит
        медицинские осмотры
        (обследования), в
        полном объеме получает
        полагающиеся
        лекарственные
        препараты, медицинские
        процедуры
        ');
        $count = $count + 1;


    }

    $writer = new Xlsx($spreadsheet);
    $uuid = uniqid();
    $name = '../excels/'. $uuid.'.xlsx';
    $writer->save($name);
    http_response_code(200);    
    echo json_encode(
        array(
            "path" => 'http://sochial-calc.io/api/excels/'. $uuid . '.xlsx',
            "status" => true
        )
    );
}