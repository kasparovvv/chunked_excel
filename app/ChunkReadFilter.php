<?php

namespace App;

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
