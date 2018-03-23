<?php

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\Exceptions\Condition\UnSupportedOperationException;
use Oliverde8\Component\RuleEngine\Exceptions\RuleException;

/**
 * Class Condition
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Condition extends AbstractRule
{
    /**
     * @inheritdoc
     */
    public function apply($rowData, &$transformedData, $options = [])
    {
        $valueToCmp = $this->applyRules($rowData, $transformedData, $options['if'], $options);
        $value = $this->applyRules($rowData, $transformedData, $options['value'], $options);

        $result = $this->compare($valueToCmp, $value, $options['operation'])? $options['then'] : $options['else'];

        return $this->applyRules($rowData, $transformedData, $result, $options);
    }

    /**
     * Compare a value.
     *
     * @param mixed $value      The value to compare.
     * @param mixed $with       The value to compare the value with.
     * @param string $operation The operation to use for the comparison.
     *
     * @throws UnSupportedOperationException
     *
     * @return bool
     */
    protected function compare($value, $with, $operation)
    {
        switch ($operation) {
            case 'eq' :
                return $value == $with;
            case 'neq' :
                return $value != $with;
            case 'in' :
                return in_array($value, $with);
            default:
                throw new UnSupportedOperationException("Operation '$operation' is not supported!");
        }
    }

    /**
     * Apply sub rules.
     *
     * @param array $rowData         Row to apply the transformation on
     * @param array $transformedData Data already transformed.
     * @param array $rules           Rule definition to apply.
     * @param array $options         Options for the rules.
     *
     * @return null
     * @throws RuleException
     */
    protected function applyRules($rowData, $transformedData, $rules, $options)
    {
        // Remove fields used by the current rule and not needed by childs.
        unset($options['if']);
        unset($options['value']);
        unset($options['operation']);
        unset($options['then']);
        unset($options['else']);

        return $this->ruleApplier->apply($rowData, $transformedData, $rules, $options);
    }

    /**
     * @inheritdoc
     */
    public function validate($options)
    {
        $this->requireOption('if', $options);
        $this->requireOption('value', $options);
        $this->requireOption('operation', $options);
        $this->requireOption('then', $options);
        $this->requireOption('else', $options);
    }

    /**
     * @inheritdoc
     */
    public function getRuleCode()
    {
        return 'condition';
    }
}