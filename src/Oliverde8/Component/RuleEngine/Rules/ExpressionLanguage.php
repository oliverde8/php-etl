<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

/**
 * Class ExpressionLanguage
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class ExpressionLanguage extends AbstractRule
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
}