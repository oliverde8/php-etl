<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class CallbackTransformerOperation
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Transformer
 */
class CallbackTransformerOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    protected $callback;

    /**
     * CallbackTransformerOperation constructor.
     *
     * @param $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $method = $this->callback;
        return $method($item, $context);
    }
}