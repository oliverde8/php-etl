<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ExternalFileItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExternalFileFinderOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    protected FileSystemInterface $fileSystem;

    protected string $directory;

    private ExpressionLanguage $expressionLanguage;


    public function __construct(FileSystemInterface $fileSystem, string $directory)
    {
        $this->fileSystem = $fileSystem;
        $this->directory = $directory;

        $this->expressionLanguage = new ExpressionLanguage();

    }

    function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $pattern = $item->getData();
        $files = [];

        $directory = $this->directory;
        if (strpos($this->directory, "@") === 0) {
            $directory = ltrim($this->directory, '@');
            $directory = $this->expressionLanguage->evaluate($directory, ['context' => $context->getParameters()]);
        }

        foreach ($this->fileSystem->listContents($directory) as $file) {
            if (preg_match($pattern, $file) !== 0) {
                $files[] = new ExternalFileItem($directory . "/" . $file, $this->fileSystem);
            }
        }

        return new MixItem($files);
    }
}
