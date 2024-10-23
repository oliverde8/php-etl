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
use Oliverde8\Component\PhpEtl\Item\GroupedItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\LoggerContext;

/**
 * Class ChainProcessor
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl
 */
class ChainProcessor extends LoggerContext implements ChainProcessorInterface
{
    const KEY_LOGGER_ETL_IDENTIFIER = 'etl.identifier';

    /** @var ChainOperationInterface[] */
    protected readonly array $chainLinks;

    /** @var string[] */
    protected readonly array $chainLinkNames;

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
    }

    public function process(\Iterator $items, array $parameters, ?callable $observerCallback = null)
    {
        $context = $this->contextFactory->get($parameters);
        $context->replaceLoggerContext($parameters);
        $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, '');

        $this->initObserver($observerCallback);

        try {
            $context->getLogger()->info("Starting etl process!");
            $this->processItems($items, 0, $context);
            $context->getLogger()->info("Finished etl process!");
            $context->finalise();
        } catch (\Exception $e) {
            $params['exception'] = $e;
            $context->getLogger()->info("Failed during etl process!", $params);
            $context->finalise();
            throw $e;
        }
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    /**
     * Process list of items with chain starting at $startAt.
     */
    protected function processItems(\Iterator $items, int $startAt, ExecutionContext $context, bool $withStop = true)
    {
        $identifierPrefix = $context->getParameter('etl.identifier');

        $count = 1;
        foreach ($items as $item) {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, $identifierPrefix . $count++);

            $dataItem = new DataItem($item);
            $this->processItemWithChain($dataItem, $startAt, $context);
        }

        $this->endAllAsyncOperations();

        $stopItem = new StopItem();
        if ($withStop) {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, $identifierPrefix . 'STOP');
            while ($this->processItemWithChain($stopItem, $startAt, $context) !== $stopItem) {
                // Executing stop until the system stops.
            }
        }

        return $stopItem;
    }

    public function processItemWithChain(ItemInterface $item, int $startAt, ExecutionContext $context): ItemInterface
    {
        $this->initObserver();

        for ($chainNumber = $startAt; $chainNumber < count($this->chainLinks); $chainNumber++) {
            $item = $this->processItemWithOperation($item, $chainNumber, $context);
            $item = $this->processItem($item, $chainNumber, $context);
        }

        return $item;
    }

    public function processItem(ItemInterface $item, int $chainNumber, ExecutionContext $context): ItemInterface
    {
        $this->processAsyncOperations();

        if ($item instanceof AsyncItemInterface) {
            while (count($this->asyncItems) >= $this->maxAsynchronousItems) {
                usleep(1000);
                $this->processAsyncOperations();
            }
            $this->asyncItems[] = [
                'item' => $item,
                'context' => $context,
                'chain_number' => $chainNumber,
            ];

            return new ChainBreakItem();
        } elseif ($item instanceof MixItem) {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, "chain link:{$this->chainLinkNames[$chainNumber]}-");

            foreach ($item->getItems() as $mixItem) {
                if ($mixItem instanceof AsyncItemInterface) {
                    $item = $this->processItemWithChain($mixItem, $chainNumber, $context);
                } elseif ($mixItem instanceof GroupedItemInterface) {
                    $item = $this->processItems($mixItem->getIterator(), $chainNumber + 1, $context, false);
                } else {
                    $item = $this->processItemWithChain($mixItem, $chainNumber + 1, $context);
                }
            }

            if ($item instanceof StopItem) {
                return $item;
            }
            return new ChainBreakItem();
        } elseif ($item instanceof GroupedItemInterface) {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, "chain link:{$this->chainLinkNames[$chainNumber]}-");
            $this->processItems($item->getIterator(), $chainNumber + 1, $context, false);

            return new StopItem();
        } else if ($item instanceof ChainBreakItem) {
            return $item;
        }

        return $item;
    }

    protected function processAsyncOperations()
    {
        $toProcess = [];

        foreach ($this->asyncItems as $id => $item) {
            if (!$item['item']->isRunning()) {
                // Item has finished.
                $chainNumber = $item['chain_number'];
                $newItem = $item['item']->getItem();

                // We consider that the process finished only once the async operation is done.
                $this->chainObserver->onAfterProcess($chainNumber, $this->chainLinks[$chainNumber], $newItem);
                unset($this->asyncItems[$id]);
                $toProcess[] = [$newItem, $chainNumber + 1, $item['context']];
            }
        }

        foreach ($toProcess as $arguments) {
            $this->processItemWithChain(...$arguments);
        }
    }

    protected function endAllAsyncOperations()
    {
        while (!empty($this->asyncItems)) {
            $this->processAsyncOperations();

            if (!empty($this->asyncItems)) {
                usleep(1000);
            }
        }
    }

    /**
     * Process an item and handle errors during the process.
     *
     * @throws ChainOperationException
     */
    protected function processItemWithOperation(ItemInterface $item, int $chainNumber, ExecutionContext &$context): ItemInterface
    {
        try {
            $this->chainObserver->onBeforeProcess($chainNumber, $this->chainLinks[$chainNumber], $item);
            $result = $this->chainLinks[$chainNumber]->process($item, $context);
            $this->chainObserver->onAfterProcess($chainNumber, $this->chainLinks[$chainNumber], $result);

            return $result;
        } catch (\Exception $exception) {
            throw new ChainOperationException(
                "An exception was thrown during the handling of the chain link : "
                    . "{$this->chainLinkNames[$chainNumber]} "
                    . "with the item {$context->getParameter(self::KEY_LOGGER_ETL_IDENTIFIER)}.",
                0,
                $exception,
                $this->chainLinkNames[$chainNumber]
            );
        }
    }

    protected function initObserver(?callable $observerCallback = null)
    {
        if ($this->chainObserver) {
            return;
        }

        // TODO init chain observer here with ideally factory.
        if (!$observerCallback) {
            $observerCallback = function (){};
        }
        $this->chainObserver = new ChainObserver($observerCallback);
        $this->chainObserver->init($this->chainLinks, $this->chainLinkNames);
    }
}
