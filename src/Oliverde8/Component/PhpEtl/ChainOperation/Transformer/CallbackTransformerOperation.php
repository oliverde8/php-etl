<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

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

    public function processData(DataItemInterface $item, array &$context): ItemInterface
    {
        $method = $this->callback;
        return $method($item, $context);
    }
}