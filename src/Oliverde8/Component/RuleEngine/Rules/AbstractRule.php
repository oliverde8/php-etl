<?php

namespace Oliverde8\Component\RuleEngine\Rules;

use Oliverde8\Component\RuleEngine\Exceptions\RuleOptionMissingException;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractRule
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\RuleEngine\Rules
 */
abstract class AbstractRule implements RuleInterface
{
    /** @var RuleApplier */
    protected $ruleApplier;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * AbstractRule constructor.
     *
     * @param LoggerInterface $logger Standard logger to log information.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the rule applier.
     *
     * @param RuleApplier $ruleApplier The rule applier that is using this rule.
     *
     * @return $this
     */
    public function setApplier(RuleApplier $ruleApplier)
    {
        $this->ruleApplier = $ruleApplier;
    }

    /**
     * Check if require option is set.
     *
     * @param string|string[] $option The option that needs to be set.
     *                                 if list is given then one of the options needs to be set.
     * @param array $options Options that were passed in parameter.
     *
     * @return bool
     * @throws RuleOptionMissingException
     */
    protected function requireOption($option, $options)
    {
        if (is_array($option)) {
            foreach ($option as $key) {
                if (isset($options[$key])) {
                    return true;
                }
            }

            $this->throwMissingFieldException(implode(' or ', $option));
        } elseif (!isset($options[$option])) {
            $this->throwMissingFieldException($option);
        }

        return true;
    }

    /**
     * Throw a nice missing field exception.
     *
     * @param string $option The option that is missing.
     *
     * @throws RuleOptionMissingException
     */
    protected function throwMissingFieldException($option)
    {
        throw new RuleOptionMissingException("Rule '{$this->getRuleCode()}' is missing the '$option' option!");
    }
}
