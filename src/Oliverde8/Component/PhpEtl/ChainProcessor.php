<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
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

    /**
     * ChainProcessor constructor.
     *
     * @param ChainOperationInterface[] $chainLinks
     */
    public function __construct(array $chainLinks, ExecutionContextFactoryInterface $contextFactory)
    {
        $this->contextFactory = $contextFactory;
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
        } catch (\Exception $e) {
            $parameters['exception'] = $e;
            $context->getLogger()->info("Failed during etl process!", $parameters);
            throw $e;
        } finally {
            $context->finalise();
        }
    }

    /**
     * Process list of items with chain starting at $startAt.
     */
    protected function processItems(\Iterator $items, int $startAt, ExecutionContext $context)
    {
        $identifierPrefix = $context->getParameter('etl.identifier');

        $count = 1;
        foreach ($items as $item) {
            $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, $identifierPrefix . $count++);

            $dataItem = new DataItem($item);
            $this->processItem($dataItem, $startAt, $context);
        }

        $stopItem = new StopItem();
        $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, $identifierPrefix . 'STOP');
        while ($this->processItem($stopItem, $startAt, $context) !== $stopItem) {
            // Executing stop until the system stops.
        }

        return $stopItem;
    }

    public function processItem(ItemInterface $item, int $startAt, ExecutionContext $context): ItemInterface
    {
        for ($chainNumber = $startAt; $chainNumber < count($this->chainLinks); $chainNumber++) {
            $item = $this->processItemWithOperation($item, $chainNumber, $context);

            if ($item instanceof GroupedItemInterface) {
                $context->setLoggerContext(self::KEY_LOGGER_ETL_IDENTIFIER, "chain link:{$this->chainLinkNames[$chainNumber]}-");
                $this->processItems($item->getIterator(), $chainNumber + 1, $context);

                return new StopItem();
            } else if ($item instanceof ChainBreakItem) {
                return $item;
            }
        }

        return $item;
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
