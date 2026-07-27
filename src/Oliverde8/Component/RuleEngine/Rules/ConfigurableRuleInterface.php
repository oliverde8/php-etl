<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Implemented by rules that can additionally be applied from a typed RuleConfigInterface object instead of the
 * legacy array-based options. Purely additive: existing rules that only implement RuleInterface keep working
 * through RuleApplier::apply() exactly as before.
 */
interface ConfigurableRuleInterface
{
    /**
     * The RuleConfigInterface class this rule knows how to apply.
     *
     * @return class-string<RuleConfigInterface>
     */
    public function getConfigClass(): string;

    /**
     * Apply this rule from a typed config instead of an array of options.
     *
     * @param array $rowData Data that is being transformed.
     * @param array $transformedData Transformed data at the current stage.
     * @param RuleConfigInterface $config Typed configuration for this rule.
     *
     * @return mixed
     */
    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed;
}
