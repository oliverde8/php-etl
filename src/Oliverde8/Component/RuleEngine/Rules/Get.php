<?php

namespace Oliverde8\Component\RuleEngine\Rules;

use oliverde8\AssociativeArraySimplified\AssociativeArray;

/**
 * Class Get
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Get extends AbstractRule
{
    /**
     * @inheritdoc
     */
    public function apply($rowData, &$transformedData, $options = [])
    {
        return AssociativeArray::getFromKey($rowData, $options['field']);
    }

    /**
     * @inheritdoc
     */
    public function validate($options)
    {
        $this->requireOption('field', $options);
    }

    /**
     * @inheritdoc
     */
    public function getRuleCode()
    {
        return 'get';
    }
}
