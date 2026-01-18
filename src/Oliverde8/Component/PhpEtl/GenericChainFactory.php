<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class GenericChainFactory
{
    public function __construct(
        private readonly string $operationClassName,
        private readonly string $configClassName,
        private readonly string $flavor = 'default',
        private readonly array $injections = [],
    ) {

        if (!$this->isOfType($this->operationClassName, ConfigurableChainOperationInterface::class)) {
            throw new ChainBuilderException("Operation class '{$this->operationClassName}' must implement ConfigurableChainOperationInterface");
        }
    }

    public function build(OperationConfigInterface $linkConfig, ChainBuilderV2 $chainBuilder): ConfigurableChainOperationInterface
    {
        $refClass = new \ReflectionClass($this->operationClassName);
        $constructor = $refClass->getConstructor();

        if ($constructor) {
            $params = $constructor->getParameters();

            // Build arguments in the correct order
            $args = [];
            foreach ($params as $param) {
                $name = $param->getName();
                if ($param->getType() !== null && $this->reflectionIsOfType($param->getType(), OperationConfigInterface::class)) {
                    $args[] = $linkConfig;
                } elseif ($param->getType() !== null && $param->getType()->getName() === 'string' && $name === 'flavor') {
                    $args[] = $this->flavor;
                } elseif ($param->getType() !== null && $this->reflectionIsOfType($param->getType(), ChainBuilderV2::class)) {
                    $args[] = $chainBuilder;
                } elseif (array_key_exists($name, $this->injections)) {
                    $args[] = $this->injections[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException("Missing parameter '$name' while creating instance of '{$this->operationClassName}' with flavor '{$this->flavor}'");
                }
            }

            return $refClass->newInstanceArgs($args);
        } else {
            return $refClass->newInstance();
        }
    }

    public function supports(OperationConfigInterface $linkConfig): bool
    {
        if (!$this->isOfType($linkConfig::class, $this->configClassName)) {
            return false;
        }
        if ($linkConfig->getFlavor() !== $this->flavor) {
            return false;
        }
        return true;
    }

    private function reflectionIsOfType(ReflectionType $type, string $expectedClassName): bool
    {
        foreach ($this->flattenTypes($type) as $reflectionType) {
            if ($this->isOfType($reflectionType->getName(), $expectedClassName)) {
                return true;
            }
        }
        return false;
    }

    private function isOfType(string $className, string $expectedClassName): bool
    {
        if (!class_exists($className) && !interface_exists($className)) {
            return false;
        }

        if (!class_exists($expectedClassName) && !interface_exists($expectedClassName)) {
            return false;
        }

        if (!is_a($className, $expectedClassName, true)) {
            return false;
        }

        return true;
    }


    private function flattenTypes(ReflectionType $type): array
    {
        if ($type instanceof ReflectionUnionType) {
            return array_values(array_filter($type->getTypes(), fn($t) => $t instanceof ReflectionNamedType));
        }
        if ($type instanceof ReflectionNamedType) {
            return [$type];
        }
        return [];
    }
}