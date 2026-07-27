<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ExternalFileItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;

class ExternalFileFinderOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly FileSystemInterface $fileSystem,
        private readonly ExternalFileFinderConfig $config,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator = new ExpressionEvaluator(),
    ) {
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $pattern = $item->getData();
        $files = [];

        $directory = $this->expressionEvaluator->evaluateIfExpression(
            $this->config->directory,
            ['context' => $context->getParameters()],
        );

        // Ensure pattern has delimiters for preg_match
        if (!preg_match('/^[\/~#!]/', (string) $pattern)) {
            $pattern = '/' . $pattern . '/';
        }

        foreach ($this->fileSystem->listContents($directory) as $file) {
            if (preg_match($pattern, (string) $file) !== 0) {
                $files[] = new ExternalFileItem($directory . "/" . $file, $this->fileSystem);
            }
        }

        return new MixItem($files);
    }
}
