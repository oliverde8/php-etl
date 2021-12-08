<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Loader;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class FileWriter
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Loader
 */
class FileWriterOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    /** @var FileWriterInterface */
    protected $writer;

    /**
     * FileWriter constructor.
     *
     * @param FileWriterInterface $writer
     */
    public function __construct(FileWriterInterface $writer)
    {
        $this->writer = $writer;
    }


    /**
     * @inheritdoc
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): DataItemInterface
    {
        $this->writer->write($item->getData());

        return $item;
    }
}
