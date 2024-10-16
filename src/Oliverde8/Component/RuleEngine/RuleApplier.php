<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine;

use Oliverde8\Component\RuleEngine\Exceptions\RuleException;
use Oliverde8\Component\RuleEngine\Exceptions\UnknownRuleException;
use Oliverde8\Component\RuleEngine\Rules\RuleInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RuleApplier
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\RuleApplier
 */
class RuleApplier
{
    protected LoggerInterface $logger;

    /** @var RuleInterface[] */
    protected array $rules;

    protected bool $validate;

    protected array $currentIdentity;

    /**
     * RuleApplier constructor.
     *
     * @param LoggerInterface $logger   Standard logger to use to log errors or debug information.
     * @param RuleInterface[] $rules    List of rules defined.
     * @param boolean         $validate When validation is active rules are validated and clear messages are displayed
     *                                  when a rule is not well coded. But performance is worsened.
     */
    public function __construct(LoggerInterface $logger, array $rules, bool $validate = false)
    {
        $this->logger = $logger;
        $this->validate = $validate;

        foreach ($rules as $rule) {
            $this->registerRule($rule);
        }
    }

    /**
     * Register a rule to the rule engine.
     *
     * @param RuleInterface $rule
     */
    public function registerRule(RuleInterface $rule)
    {
        $this->rules[$rule->getRuleCode()] = $rule;
    }


    /**
     * Apply rules to data.
     *
     * @param array|string $rowData         Data that is being transformed.
     * @param array|string $transformedData Transformed data at the current stage
     * @param array|string $rules           Rules to apply, if string is given the string is returned.
     * @param array        $options         Options to be used by the rules.
     * @param array        $identifier      Identity of the line rule is applied on to log & display.
     *
     * @return null|string
     * @throws UnknownRuleException
     * @throws RuleException
     */
    public function apply($rowData, $transformedData, $rules, array $options = [], array $identifier = [])
    {
        // There is no rule apply.
        if (!is_array($rules)) {
            return $rules;
        }

        if (!empty($identifier)) {
            $this->currentIdentity = $identifier;
        }

        foreach ($rules as $ruleData) {
            // There is no rule apply.
            if (!is_array($ruleData)) {
                return $ruleData;
            }

            foreach ($ruleData as $rule => $ruleOptions) {
                $value = $this->applyRule($rule, $ruleOptions, $rowData, $transformedData, $options);

                if (!empty($value) || $value === 0 || $value === "0" || $value === false) {
                    return $value;
                }
            }
        }

        if (!empty($this->currentIdentity)) {
            $this->logger->warning("Rules were not resolved for line.", $this->currentIdentity);
        }

        return "";
    }

    /**
     * Apply a rule to a data set.
     *
     * @param string $rule            Code of the rule to apply.
     * @param array  $ruleOptions     Options to use when applying the rule
     * @param array  $rowData         Row that needs conversion.
     * @param array  $transformedData Current state of the transformed rule.
     * @param array  $options         Other options.
     *
     * @throws RuleException
     *
     * @return null|string
     * @throws UnknownRuleException
     */
    protected function applyRule(string $rule, array $ruleOptions, array $rowData, array $transformedData, array $options)
    {
        if (!isset($this->rules[$rule])) {
            throw new UnknownRuleException("Rule '$rule' was not found!");
        }
        $ruleObject = $this->rules[$rule];

        $options = array_merge($options, $ruleOptions);

        if ($this->validate) {
            try {
                $ruleObject->validate($options);
            } catch (RuleException $ruleException) {
                $this->logger->error(
                    "An exception accured when executing rules!",
                    array_merge(
                        $this->currentIdentity,
                        ['message' => $ruleException->getMessage()]
                    )
                );
                throw $ruleException;
            }
        }

        $ruleObject->setApplier($this);
        return $ruleObject->apply($rowData, $transformedData, $options);
    }
}
