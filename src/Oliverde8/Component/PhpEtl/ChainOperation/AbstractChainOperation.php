<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class AbstractChainOperation
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
abstract class AbstractChainOperation implements ChainOperationInterface
{
    private $methodResolutionCache = [];

    /**
     * @inheritdoc
     */
    final public function process(ItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if (!isset($this->methodResolutionCache[get_class($item)])) {
            $this->methodResolutionCache[get_class($item)] = $this->resolveMethodName($item);
        }
        $method = $this->methodResolutionCache[get_class($item)];

        if (!is_null($method)) {
            return $this->$method($item, $context);
        }

        return $item;
    }

    private function resolveMethodName(ItemInterface $item): ?string
    {
        $processReflection = new \ReflectionClass($this);
        foreach ($processReflection->getMethods() as $method) {
            if ($this->validateMethod($method)) {
                $firstParameter = $method->getParameters()[0];
                $expecting = $firstParameter->getType()->getName();

                if (interface_exists($expecting)) {
                    $itemReflection = new \ReflectionClass($item);
                    if ($itemReflection->implementsInterface($expecting)) {
                        return  $method->getName();
                    }
                    continue;
                }

                if ($this->checkIsA(get_class($item), $expecting)) {
                    return $method->getName();
                }
            };
        }

        return null;
    }

    private function checkIsA(string $class, string $targetClass): bool
    {
        if ($class == $targetClass) {
            return true;
        }

        $parentClass = get_parent_class($class);
        if ($parentClass) {
            return $this->getExtensionDistance($parentClass, $targetClass);
        }

        return false;
    }

    private function validateMethod(\ReflectionMethod $method)
    {
        if (count($method->getParameters()) != 2) {
            return false;
        }
        if (in_array($method->getName(), ['process', 'resolveMethodName'])) {
            return false;
        }
        if (strpos($method->getName(), "process") !== 0) {
            return false;
        }

        return true;
    }
}
