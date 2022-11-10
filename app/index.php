<?php

//namespace App;

//require __DIR__ . '/vendor/autoload.php';
require '../vendor/autoload.php';


$inputFileType = 'Xlsx';
$inputFileName = './files/5000_1.xlsx';

/**  Define a Read Filter class implementing \PhpOffice\PhpSpreadsheet\Reader\IReadFilter  */
class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;
    public $headers = [];

    public $chunkSize = 5000;

    /**  Set the list of rows that we want to read  */
    public function setRows($startRow)
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $this->chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        //  Only read the heading row, and the configured rows
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }
        return false;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function mergeWithHeader($v)
    {
        return array_combine($this->headers, $v);
    }
}


echo "start => " . date("H:i:s") . "<br>";

/**  Create a new Reader of the type defined in $inputFileType  **/
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

/**  Define how many rows we want to read for each "chunk"  **/

/**  Create a new Instance of our Read Filter  **/
$chunkFilter = new ChunkReadFilter();

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


$dbh = new PDO('mysql:host=localhost;dbname=CHUNK_EXCEL', 'root', '');


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
