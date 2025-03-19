<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

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
    protected array $chainLinks = [];

    protected ExecutionContextFactoryInterface $contextFactory;

    /** @var string[] */
    protected array $chainLinkNames = [];

    protected array $asyncItems = [];

    protected int $maxAsynchronousItems = 10;

    /**
     * ChainProcessor constructor.
     *
     * @param ChainOperationInterface[] $chainLinks
     */
    public function __construct(
        array $chainLinks,
        ExecutionContextFactoryInterface $contextFactory,
        int $maxAsynchronousItems = 10
    )
    {
        $this->contextFactory = $contextFactory;
        $this->maxAsynchronousItems = $maxAsynchronousItems;
        $this->chainLinkNames = array_keys($chainLinks);
        $this->chainLinks = array_values($chainLinks);
    }

    public function process(\Iterator $items, array $parameters)
    {
        $context = $this->contextFactory->get($parameters);
        $context->replaceLoggerContext($parameters);
        $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, '');

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
            if ($this->maxAsynchronousItems !== 0) {
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
            } else {
                while ($item->isRunning()) {
                    usleep(1000);
                }
                return $item->getItem();
            }
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
        foreach ($this->asyncItems as $id => $item) {
            if (!$item['item']->isRunning()) {
                // Item has finished.
                unset($this->asyncItems[$id]);
                $this->processItemWithChain($item['item']->getItem(), $item['chain_number'] + 1, $item['context']);
            }
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
            return $this->chainLinks[$chainNumber]->process($item, $context);
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
}
