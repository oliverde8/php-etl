<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

class FailSafeOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface
{
    use SplittedChainOperationTrait;

    private ChainProcessor $chainProcessor;
    private array $exceptionsToCatch = [];
    private int $nbAttempts = 1;

    public function __construct(ChainBuilderV2 $chainBuilder, FailSafeConfig $config)
    {
        $this->chainProcessor = $chainBuilder->createChain($config->getChainConfig());
        $this->exceptionsToCatch = $config->exceptionsToCatch;
        $this->nbAttempts = $config->nbAttempts;
        $this->onSplittedChainOperationConstruct([$this->chainProcessor]);
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        return new GroupedItem($this->repeatOnItem($item, $context));
    }

    public function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->repeatOnItem($item, $context) as $ignored) {}
        return $item;
    }

    public function repeatOnItem(ItemInterface $inputItem, ExecutionContext $context): \Generator
    {
        $nbAttempts = 0;
        do {
            try {
                foreach ($this->chainProcessor->processGenerator($inputItem, $context, withStop: false) as $newItem) {
                    yield $newItem;
                }
                return; // success - stop retrying
            } catch (\Exception $exception) {
                $exceptionToHandle = $exception;
                if ($exception instanceof ChainOperationException) {
                    $exceptionToHandle = $exception->getPrevious() ?? $exception;
                }

                $nbAttempts++;
                $handled = false;
                foreach ($this->exceptionsToCatch as $exceptionType) {
                    if ($exceptionToHandle instanceof $exceptionType) {
                        $handled = true; break;
                    }
                }
                if (!$handled || $nbAttempts >= $this->nbAttempts) {
                    $context->getLogger()->error('FailSafeOperation giving up', ['attempts' => $nbAttempts, 'exception' => $exception]);
                    throw $exception;
                }
                $context->getLogger()->warning('FailSafeOperation retrying', ['attempts' => $nbAttempts, 'exception' => $exception]);
            }
        } while ($nbAttempts < $this->nbAttempts);
    }

    public function getConfigurationClass(): string
    {
        return FailSafeConfig::class;
    }
}