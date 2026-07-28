<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\RuleConfig\ImplodeRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Class Implode
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Implode extends AbstractRule implements ConfigurableRuleInterface
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
        $subOptions = $options;
        unset($subOptions['values']);
        unset($subOptions['with']);

        $resolved = [];
        foreach ($options['values'] as $ruleData) {
            $resolved[] = $this->ruleApplier->apply($rowData, $transformedData, $ruleData, $subOptions);
        }

        return $this->combine($resolved, $options['with']);
    }

    #[\Override]
    public function getConfigClass(): string
    {
        return ImplodeRuleConfig::class;
    }

    #[\Override]
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof ImplodeRuleConfig);

        $resolved = [];
        foreach ($config->values as $valueConfig) {
            $resolved[] = $this->ruleApplier->applyConfig($rowData, $transformedData, $valueConfig);
        }

        return $this->combine($resolved, $config->with);
    }

    /**
     * Flatten and join already-resolved values, matching Implode's legacy behaviour.
     */
    private function combine(array $resolvedValues, string $with): string
    {
        $data = [];
        foreach ($resolvedValues as $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    foreach ($this->flatten($value) as $v) {
                        $data[] = $v;
                    }
                } else {
                    $data[] = $value;
                }
            }
        }

        return implode($with, $data);
    }

    /**
     * Flatten a multidimensional array.
     *
     * @param array $array
     *
     * @return \Generator
     */
    protected function flatten(array $array): \Generator
    {
        foreach ($array as $v) {
            if (is_array($v)) {
                foreach ($this->flatten($v) as $value) {
                    yield $value;
                };
            } else {
                yield $v;
            }
        }
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function validate(array $options): void
    {
        $this->requireOption('values', $options);
        $this->requireOption('with', $options);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getRuleCode(): string
    {
        return 'implode';
    }
}