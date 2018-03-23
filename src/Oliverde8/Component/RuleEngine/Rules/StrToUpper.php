<?php

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\Exceptions\RuleOptionMissingException;

/**
 * Class StrToLower
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class StrToUpper extends AbstractRule
{
    /**
     * @inheritdoc
     */
    public function apply($rowData, &$transformedData, $options = [])
    {
        $value = $options['value'];
        unset($options['value']);

        return strtoupper($this->ruleApplier->apply($rowData, $transformedData, $value, $options));
    }

    /**
     * @inheritdoc
     */
    public function validate($options)
    {
        $this->requireOption('value', $options);
    }

    /**
     * @inheritdoc
     */
    public function getRuleCode()
    {
        return 'str_upper';
    }
}