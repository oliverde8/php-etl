<?php

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;

/**
 * Class RuleInterface
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
interface RuleInterface
{
    /**
     * Apply a certain rule and returns the result.
     *
     * @param array $rowData         Data that is being trasformed.
     * @param array $transformedData Transformed data at the current stage
     * @param array $options         Options to be used by the rule.
     *
     * @return string|null
     */
    public function apply($rowData, &$transformedData, $options = []);

    /**
     * Validates if a rule can be used with these options
     *
     * @param array $options Options to be used by the rule.
     */
    public function validate($options);

    /**
     * Get unique code that needs to be used to apply this rule.
     *
     * @return string
     */
    public function getRuleCode();

    /**
     * Set the rule applier.
     *
     * @param RuleApplier $ruleApplier
     *
     * @return $this
     */
    public function setApplier(RuleApplier $ruleApplier);
}