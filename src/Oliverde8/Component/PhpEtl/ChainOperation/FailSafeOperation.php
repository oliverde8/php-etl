<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class FailSafeOperation implements ChainOperationInterface, DetailedObservableOperation
{
    use SplittedChainOperationTrait;

    protected int $count = 0;

    public function __construct(
        protected readonly ChainProcessor $chainProcessor,
        protected readonly array $exceptionsToCatch,
        protected readonly int $nbAttempts,
    ){}

    #[\Override]
    public function process(ItemInterface $item, ExecutionContext $context)
    {
        if ($item instanceof StopItem) {
            foreach ($this->repeatOnItem($item, $context) as $newItem) {}
            return $item;
        }

        return new GroupedItem($this->repeatOnItem($item, $context));
    }

    public function repeatOnItem(ItemInterface $inputItem, ExecutionContext $context): \Generator
    {
        $nbAttempts = 0;
        do {
            try {
                foreach ($this->chainProcessor->processGenerator($inputItem, $context, withStop: false) as $newItem) {
                    yield $newItem;
                }
                return;
            } catch (\Exception $exception) {
                $nbAttempts++;
                $exceptionHandled = false;
                foreach ($this->exceptionsToCatch as $exceptionType) {
                    if ($exception instanceof $exceptionType) {
                        $exceptionHandled = true;
                    }
                }

                if (!$exceptionHandled || $nbAttempts >= $this->nbAttempts) {
                    $context->getLogger()->error("Failed to handle exception in fail safe!", ['exception' => $exception]);
                    throw $exception;
                } else {
                    $context->getLogger()->warning("Handling exception with fail safe!", ['exception' => $exception, 'nbAttempts' => $nbAttempts]);
                }
            }
        } while ($nbAttempts < $this->nbAttempts);
    }
}