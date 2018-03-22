<?php

namespace Oliverde8\Component\PhpEtl\Loader\File;

use Oliverde8\Component\PhpEtl\Model\File\AbstractFile;

/**
 * Class Csv
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Loader\File
 */
class Csv extends AbstractFile implements \Iterator
{
    /** @var string[] Current line */
    protected $current;

    /** @var int Number of lines read */
    protected $count = 0;

    /** @var string[] CSV file headers */
    protected $headers = null;

    /**
     * Initialize the read of the file.
     */
    protected function init()
    {
        if (is_null($this->file)) {
            $this->file = fopen($this->filePath, 'r');
            $headers = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure, $this->escape);

            if (is_null($this->headers)) {
                $this->headers = $headers;
            }

            $this->next();
        }
    }

    /**
     * Return file headers
     *
     * @return \string[]
     */
    public function getHeaders()
    {
        $this->init();

        return $this->headers;
    }

    /**
     * Set file headers
     *
     * @param array $headers File headers to set
     */
    public function setHeaders($headers)
    {
        $this->init();

        $this->headers = $headers;
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $this->init();

        return $this->current;
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->init();
        $this->count++;

        $current = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure, $this->escape);

        if ($current) {
            $this->current = array_combine($this->headers, $current);
            return;
        }

        $this->current = false;
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $this->init();

        return $this->count;
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        $this->init();

        return $this->file !== false && $this->current() != false;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        if (!is_null($this->file)) {
            fclose($this->file);
            $this->count = 0;
            $this->file = null;
        }

        $this->init();
    }
}
