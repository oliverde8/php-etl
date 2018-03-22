<?php

namespace Oliverde8\Component\PhpEtl\Model\File;

/**
 * Class FileWriterInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Model\File
 */
interface FileWriterInterface
{
    public function write($rowData);
}