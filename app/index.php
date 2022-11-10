<?php

require '../vendor/autoload.php';


$inputFileType = 'Xlsx';
$inputFileName = './files/5000_1.xlsx';

/**  Define a Read Filter class implementing \PhpOffice\PhpSpreadsheet\Reader\IReadFilter  */

echo "start => " . date("H:i:s") . "<br>";

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);


$chunkFilter = new App\ChunkReadFilter();

$headers = array(
    'id',
    "firstname",
    "lastname",
    "gender",
    "country",
    "age",
    "date",
    "row_id"
);

$chunkFilter->setHeaders($headers);

$worksheetData = $reader->listWorksheetInfo($inputFileName);

$maxCol = $worksheetData[0]["lastColumnLetter"];
$totalRows = $worksheetData[0]["totalRows"];

/**  Tell the Reader that we want to use the Read Filter  **/
$reader->setReadFilter($chunkFilter);

$reader->setReadDataOnly(true);
$reader->setReadEmptyCells(false);




$dbh = new App\Database();
$dbh = $dbh->getConnection();



/**  Loop to read our worksheet in "chunk size" blocks  **/
for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkFilter->chunkSize) {

    $chunkFilter->setRows($startRow);
    /**  Load only the rows that match our filter  **/
    $spreadsheet = $reader->load($inputFileName);

    $maxRow = $startRow + $chunkFilter->chunkSize;

    $records = $spreadsheet->getActiveSheet()->rangeToArray('A' . $startRow . ':' . $maxCol . $maxRow, NULL, TRUE, true);

    $data = array_filter(array_map('array_filter', array_filter($records)));
    $dataWithHeaders = array_map(array($chunkFilter, 'mergeWithHeader'), $data);

    foreach ($dataWithHeaders as $value) {
        $sql = "INSERT INTO users (firstname, lastname, gender,country,age,row_id) VALUES (?,?,?,?,?,?)";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([$value['firstname'], $value['lastname'], $value['gender'], $value['country'], $value['age'], $value['row_id']]);

        if ($dbh->lastInsertId() < 0) {
            var_dump($value);
        }
    }
}



echo "end => " . date("H:i:s") . "<br>";
