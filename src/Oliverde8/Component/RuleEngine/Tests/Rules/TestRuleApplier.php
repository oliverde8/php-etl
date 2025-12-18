<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;


/**
 * Class TestRuleAPplier
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Smile\Components\RuleEngine\Tests\Rules
 */
class TestRuleApplier extends RuleApplier
{
    #[\Override]
    public function apply($rowData, $transformedData, $rules, $options = [], $identifier = [])
    {
        return $rules;
    }
}