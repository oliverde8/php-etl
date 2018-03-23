<?php

namespace Oliverde8\Component\RuleEngine\Rules;

/**
 * Class Constant
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Constant extends AbstractRule
{

    /**
     * Apply a certain rule and returns the result.
     *
     * @param array $rowData Data that is being trasformed.
     * @param array $transformedData Transformed data at the current stage
     * @param array $options Options to be used by the rule.
     *
     * @return string|null
     */
    public function apply($rowData, &$transformedData, $options = [])
    {
        return $options['value'];
    }

    /**
     * @inheritdoc
     */
    public function validate($options)
    {
        $this->requireOption('value', $options);
    }

    /**
     * Get unique code that needs to be used to apply this rule.
     *
     * @return string
     */
    public function getRuleCode()
    {
        return 'constant';
    }
}