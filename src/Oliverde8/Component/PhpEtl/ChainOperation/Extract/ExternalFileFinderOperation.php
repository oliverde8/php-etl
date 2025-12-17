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
    private readonly ExpressionLanguage $expressionLanguage;


    public function __construct(protected FileSystemInterface $fileSystem, protected string $directory)
    {
        $this->expressionLanguage = new ExpressionLanguage();

    }

    #[\Override]
    function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $pattern = $item->getData();
        $files = [];

        $directory = $this->directory;
        if (str_starts_with($this->directory, "@")) {
            $directory = ltrim($this->directory, '@');
            $directory = $this->expressionLanguage->evaluate($directory, ['context' => $context->getParameters()]);
        }

        foreach ($this->fileSystem->listContents($directory) as $file) {
            if (preg_match($pattern, (string) $file) !== 0) {
                $files[] = new ExternalFileItem($directory . "/" . $file, $this->fileSystem);
            }
        }

        return new MixItem($files);
    }
}
