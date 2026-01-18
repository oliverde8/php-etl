<?php

declare(strict_types=1);

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
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = []): string
    {
        $value = $options['value'];
        unset($options['value']);

        return strtoupper((string) $this->ruleApplier->apply($rowData, $transformedData, $value, $options));
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
        return 'str_upper';
    }
}