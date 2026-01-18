<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

/**
 * Class Implode
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Implode extends AbstractRule
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

        $data = [];
        foreach ($options['values'] as $ruleData) {
            $value = $this->ruleApplier->apply($rowData, $transformedData, $ruleData, $subOptions);

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

        return implode($options['with'], $data);
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