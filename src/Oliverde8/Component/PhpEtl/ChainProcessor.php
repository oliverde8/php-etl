<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainObserver\ChainObserver;
use Oliverde8\Component\PhpEtl\ChainObserver\ChainObserverInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\AsyncItemInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\LoggerContext;

final class ChainProcessor extends LoggerContext implements ChainProcessorInterface
{
    const KEY_LOGGER_ETL_IDENTIFIER = 'etl.identifier';

    /** @var ChainOperationInterface[] */
    protected readonly array $chainLinks;

    /** @var string[] */
    protected readonly array $chainLinkNames;

    protected readonly int $chainEnd;

    protected ?ChainObserverInterface $chainObserver = null;

    protected array $asyncItems = [];

    /**
     * ChainProcessor constructor.
     *
     * @param ChainOperationInterface[] $chainLinks
     */
    public function __construct(
        array $chainLinks,
        protected ExecutionContextFactoryInterface $contextFactory,
        protected int $maxAsynchronousItems = 10,
        protected bool $isShared = false,
    )
    {
        $this->chainLinkNames = array_keys($chainLinks);
        $this->chainLinks = array_values($chainLinks);
        $this->chainEnd = count($chainLinks) - 1;
    }


    public function process(\Iterator|ItemInterface $item, array $parameters, ?callable $observerCallback = null, $withStop = true): void
    {
        $context = $this->contextFactory->get($parameters);
        $context->replaceLoggerContext($parameters);
        $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, '');

        foreach ($this->processGenerator($item, $context, $observerCallback, $withStop) as $item) {}

        if ($this->chainObserver) {
            $this->chainObserver->onFinish();
        }
    }

    public function processGenerator(
        \Iterator|ItemInterface $item,
        ExecutionContext $context,
        ?callable $observerCallback = null,
        bool $withStop = true,
        bool $allowAsynchronous = true
    ): \Generator {
        $context->getLogger()->info("Starting etl process!");
        $this->initObserver($observerCallback);

        $originalMaxAsynchronousItems = $this->maxAsynchronousItems;
        if (!$allowAsynchronous) {
            $this->maxAsynchronousItems = 0;
        }

        if ($item instanceof \Iterator) {
            $item = new GroupedItem($item);
        }

        foreach($this->processItemAt($item, $context, 0) as $newItem) {
            yield $newItem;
        }

        // Finalise all remaining asynchronous items.
        foreach ($this->handleAsyncItems(0) as $newItem) {
            yield $newItem;
        }

        if ($withStop) {
            $stopItem = new StopItem();
            $newItem = $stopItem;
            do {
                foreach ($this->processItemAt($stopItem, $context, 0) as $newItem) {
                    yield $newItem;
                }
            } while ($newItem !== $stopItem);
        }

        $this->maxAsynchronousItems = $originalMaxAsynchronousItems;
    }

    protected function processItemAt(ItemInterface $item, ExecutionContext $context, int $chainNumber = 0): \Generator
    {
        if ($chainNumber > $this->chainEnd) {
            // End chain !!
            if ($item instanceof GroupedItemInterface) {
                foreach ($this->getItemsFromGroupItem($item) as $newItem) {
                    yield $newItem;
                }
            }
            elseif ($item instanceof MixItem) {
                foreach ($item->getItems() as $item) {
                    foreach ($this->processItemAt($item, $context, $chainNumber) as $newItem) {
                        yield $newItem;
                    }
                }
            } elseif ($item instanceof AsyncItemInterface) {
                $this->asyncItems[] = ['item' => $item, 'context' => $context, 'chain_number' => $chainNumber];
                $newItem = $this->handleAsyncItems();
                foreach($newItem as $resultItem) {
                    yield $resultItem;
                }
            }else {
                yield $item;
            }

        } else if ($item instanceof GroupedItemInterface) {
            foreach ($this->getItemsFromGroupItem($item) as $groupedItem) {
                foreach ($this->processItemAt($groupedItem, $context, $chainNumber) as $newItem) {
                    yield $newItem;
                }
            }
        } elseif ($item instanceof ChainBreakItem) {
            yield $item;
        } elseif ($item instanceof MixItem) {
            foreach ($item->getItems() as $mixItem) {
                foreach($this->processItemAt($mixItem, $context, $chainNumber) as $newItem) {
                    yield $newItem;
                }
            }
        } elseif ($item instanceof AsyncItemInterface) {
            $this->asyncItems[] = ['item' => $item, 'context' => $context, 'chain_number' => $chainNumber];
            $newItem = $this->handleAsyncItems();
            foreach($newItem as $resultItem) {
                yield $resultItem;
            }
        } else {
            $newItem = $this->processItemWithOperation($item, $context, $chainNumber);
            foreach($this->processItemAt($newItem, $context, $chainNumber + 1) as $resultItem) {
                yield $resultItem;
            }
        }
    }

    protected function getItemsFromGroupItem(GroupedItem $item): \Generator
    {
        foreach ($item->getIterator() as $item) {
            if (!is_object($item)) {
                $item = new DataItem($item);
            } elseif (!($item instanceof DataItemInterface)) {
                $item = new DataItem($item);
            }
            yield $item;
        }
    }

    protected function handleAsyncItems(int $maxItems = null): \Generator
    {
        if ($maxItems === null) {
            $maxItems = $this->maxAsynchronousItems;
        }

        // Start by checking if in item is finished.
        foreach ($this->checkAsyncItems() as $newItem) {
            yield $newItem;
        }

        // If we have to many items in queue, well wait until it improves.
        while (count($this->asyncItems) != 0 && count($this->asyncItems) >= $maxItems) {
            usleep(1000);
            foreach ($this->checkAsyncItems() as $newItem) {
                yield $newItem;
            }
        }
    }

    protected function checkAsyncItems(): \Generator
    {
        $toProcess = [];

        foreach ($this->asyncItems as $id => $item) {
            if (!$item['item']->isRunning()) {
                // Item has finished.
                $chainNumber = $item['chain_number'];
                $newItem = $item['item']->getItem();

                // We consider that the process finished only once the async operation is done.
                if (isset($this->chainLinks[$chainNumber])) {
                    $this->chainObserver->onAfterProcess($chainNumber, $this->chainLinks[$chainNumber], $newItem);
                }
                unset($this->asyncItems[$id]);
                $toProcess[] = [$newItem, $item['context'], $chainNumber];
            }
        }

        foreach ($toProcess as $arguments) {
            foreach ($this->processItemAt(...$arguments) as $newItem) {
                yield $newItem;
            }
        }
    }



    protected function processItemWithOperation(ItemInterface $item, ExecutionContext $context, int $chainNumber = 0): ItemInterface
    {
        try {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, "chain link:{$this->chainLinkNames[$chainNumber]}-");
            $this->chainObserver->onBeforeProcess($chainNumber, $this->chainLinks[$chainNumber], $item);
            $context->getLogger()->info("Starting etl process!");

            $newItem = $this->chainLinks[$chainNumber]->process($item, $context);

            $context->getLogger()->info("Finished etl process!");
            $this->chainObserver->onAfterProcess($chainNumber, $this->chainLinks[$chainNumber], $newItem);

            return $newItem;
        } catch (\Exception $exception) {
            throw new ChainOperationException(
                "An exception was thrown during the handling of the chain link : "
                . "{$this->chainLinkNames[$chainNumber]} "
                . "with the item {$context->getParameter(self::KEY_LOGGER_ETL_IDENTIFIER)}.",
                0,
                $exception,
                (string) $this->chainLinkNames[$chainNumber]
            );
        }
    }

    public function initObserver(?callable $observerCallback = null): ChainObserver
    {
        if ($this->chainObserver) {
            return $this->chainObserver;
        }

        if (!$observerCallback) {
            $observerCallback = function (){};
        }
        $this->chainObserver = new ChainObserver($observerCallback);
        $this->chainObserver->init($this->chainLinks, $this->chainLinkNames);

        return $this->chainObserver;
    }

    public function getChainLinkNames(): array
    {
        return $this->chainLinkNames;
    }

    public function getChainLinks(): array
    {
        return $this->chainLinks;
    }
}
