<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\Exceptions\RuleException;
use Oliverde8\Component\RuleEngine\RuleConfig\ConstantRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Class Constant
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Constant extends AbstractRule implements ConfigurableRuleInterface
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
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
        return $options['value'];
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function validate($options): void
    {
        $this->requireOption('value', $options);
    }

    /**
     * Get unique code that needs to be used to apply this rule.
     *
     * @return string
     */
    #[\Override]
    public function getRuleCode(): string
    {
        return 'constant';
    }

    #[\Override]
    public function getConfigClass(): string
    {
        return ConstantRuleConfig::class;
    }

    #[\Override]
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof ConstantRuleConfig);
        return $config->value;
    }
}