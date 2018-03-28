<?php

namespace Oliverde8\Component\PhpEtl\Load\File;

use Oliverde8\Component\PhpEtl\Model\File\AbstractCsvFile;

/**
 * Class Writer
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Model\File\Csv
 */
class Csv extends AbstractCsvFile implements FileWriterInterface
{
    /** @var bool */
    protected $hasHeader;

    /**
     * Writer constructor.
     *
     * @param $filePath
     * @param bool $hasHeaders
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct($filePath, $hasHeaders = true, $delimiter = ';', $enclosure = '"', $escape = '\\')
    {
        $this->hasHeader = $hasHeaders;
        parent::__construct($filePath, $delimiter, $enclosure, $escape);
    }


    /**
     * Initialize the write of the file.
     */
    protected function init($rowData)
    {
        if (is_null($this->file)) {
            $this->file = fopen($this->filePath, 'w');

            if ($this->hasHeader) {
                fputcsv($this->file, array_keys($rowData), $this->delimiter, $this->enclosure, $this->escape);
            }
        }
    }

    /**
     * Write row data in the csv file.
     *
     * @param array $rowData data to wrinte in a line of the csv.
     */
    public function write($rowData) {
        $this->init($rowData);

        fputcsv($this->file, $rowData, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * Close the connection and create empty file.
     */
    public function close()
    {
        // Create an empty file.
        $this->init([]);

        fclose($this->file);
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        $this->close();
    }
}
