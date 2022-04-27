<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Load\File\Csv;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

class SimpleEtl
{
    private string $dataType = "csv";

    private LocalFileSystem $to;

    private string $fileName;

    private ChainOperationInterface $operation;

    public function dataOfType(string $type): self
    {
        $this->dataOfType = $type;

        return $this;
    }

    public function to(string $type, array $options): self
    {
        if ($type == 'local'){
            $this->to = new LocalFileSystem(dirname($options["filepath"]));
            $this->fileName = basename($options["filepath"]);
        }

        return $this;
    }

    public function treatWith(callable $function): self
    {
        $this->operation = new CallbackTransformerOperation(function (\Oliverde8\Component\PhpEtl\Item\DataItem $item, ExecutionContext $context) use ($function) {
            $data = $function($item->getData(), $context);

            return new GroupedItem(new \ArrayIterator($data));
        });

        return $this;
    }

    public function go(array $input = [[]], array $parameters = [])
    {
        $chain = new ChainProcessor([
            $this->operation,
            new FileWriterOperation(new Csv($this->fileName))
        ], new ExecutionContextFactory());

        $chain->process(new \ArrayIterator($input), $parameters);
    }
}