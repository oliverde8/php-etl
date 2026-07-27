<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Class Get
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
class Get extends AbstractRule implements ConfigurableRuleInterface
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
        if (!is_array($options['field'])) {
            $options['field'] = [$options['field']];
        }

        $fields = [];
        foreach ($options['field'] as $field) {
            $fields[] = AssociativeArray::getFromKey($rowData, '@column.' . $field, $field, '.');
        }

        return AssociativeArray::getFromKey($rowData, $fields);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function validate(array $options): void
    {
        $this->requireOption('field', $options);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getRuleCode(): string
    {
        return 'get';
    }

    #[\Override]
    public function getConfigClass(): string
    {
        return GetRuleConfig::class;
    }

    #[\Override]
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof GetRuleConfig);
        return $this->apply($rowData, $transformedData, ['field' => $config->field]);
    }
}
