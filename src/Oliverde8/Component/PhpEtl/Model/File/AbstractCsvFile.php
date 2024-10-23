<?php

namespace Oliverde8\Component\PhpEtl\Model\File;

/**
 * Class AbstractFile
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Model\File
 */
class AbstractCsvFile
{
    /** @var String */
    protected $filePath;

    /** @var string */
    protected $delimiter;

    /** @var string */
    protected $enclosure;

    /** @var string */
    protected $escape;

    /** @var resource File pointer */
    protected $file = null;

    /**
     * Reader constructor.
     *
     * @param string|resource $filePath  Path to the csv file.
     * @param string $delimiter Delimiter to use for the csv file.
     * @param string $enclosure Enclosure to use for the csv file.
     * @param string $escape    Escape to use for the csv file.
     */
    public function __construct($filePath, string $delimiter = ';', string $enclosure = '"', string $escape = '\\')
    {
        $this->filePath = $filePath;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    public function getResource()
    {
        return $this->file;
    }
}
