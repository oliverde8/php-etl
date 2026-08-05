<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

class RuleTransformConfig extends AbstractOperationConfig
{
    protected array $rules = [];

    public function __construct(public readonly bool $add = true, string $flavor = 'default')
    {
        parent::__construct($flavor);
    }

    /**
     * @param RuleConfigInterface|array $rules A typed RuleConfigInterface (see Oliverde8\Component\RuleEngine\RuleConfig),
     *                                           or the legacy array-based rule engine syntax (deprecated, triggers a
     *                                           deprecation notice at runtime).
     */
    public function addColumn(string $columnName, RuleConfigInterface|array $rules): self
    {
        if (is_array($rules)) {
            trigger_deprecation(
                'oliverde8/php-etl',
                '2.1',
                'Passing an array of rules to RuleTransformConfig::addColumn() for column "%s" is deprecated, pass a RuleConfigInterface instead (e.g. new GetRuleConfig(...)). See Oliverde8\Component\RuleEngine\RuleConfig.',
                $columnName,
            );
        }

        $this->rules[$columnName]['rules'] = $rules;
        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
    }
}
