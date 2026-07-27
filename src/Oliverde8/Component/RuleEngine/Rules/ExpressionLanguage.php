<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\RuleConfig\ExpressionRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Class ExpressionLanguage
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class ExpressionLanguage extends AbstractRule implements ConfigurableRuleInterface
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
        $values = [
            'rowData' => $rowData,
            'transformedData' => $transformedData,
        ];

        if (isset($options['values'])) {
            $newOptions = $options;
            unset($newOptions['values']);
            foreach ($options['values'] as $valueKey => $value) {
                $values[$valueKey] = $this->ruleApplier->apply($rowData, $transformedData, $value, $newOptions);
            }
        }

        $expressionLanguage = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();
        return $expressionLanguage->evaluate($options['expression'], $values);
    }

    /**
     * Get unique code that needs to be used to apply this rule.
     *
     * @return string
     */
    #[\Override]
    public function getRuleCode(): string
    {
        return 'expression_language';
    }


    /**
     * @inheritdoc
     */
    #[\Override]
    public function validate(array $options): void
    {
        $this->requireOption('expression', $options);
    }

    #[\Override]
    public function getConfigClass(): string
    {
        return ExpressionRuleConfig::class;
    }

    #[\Override]
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof ExpressionRuleConfig);

        $values = [
            'rowData' => $rowData,
            'transformedData' => $transformedData,
        ];

        foreach ($config->values as $valueKey => $valueConfig) {
            $values[$valueKey] = $this->ruleApplier->applyConfig($rowData, $transformedData, $valueConfig);
        }

        $expressionLanguage = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();
        return $expressionLanguage->evaluate($config->expression, $values);
    }
}