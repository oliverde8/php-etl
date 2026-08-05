<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;
use Oliverde8\Component\RuleEngine\RuleConfig\StrToLowerRuleConfig;

/**
 * Class StrToLower
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class StrToLower extends AbstractRule implements ConfigurableRuleInterface
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = []): string
    {
        $value = $options['value'];
        unset($options['value']);

        return strtolower((string) $this->ruleApplier->apply($rowData, $transformedData, $value, $options));
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function validate(array $options): void
    {
        $this->requireOption('value', $options);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getRuleCode(): string
    {
        return 'str_lower';
    }

    #[\Override]
    public function getConfigClass(): string
    {
        return StrToLowerRuleConfig::class;
    }

    #[\Override]
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof StrToLowerRuleConfig);
        return strtolower((string) $this->ruleApplier->applyConfig($rowData, $transformedData, $config->value));
    }
}