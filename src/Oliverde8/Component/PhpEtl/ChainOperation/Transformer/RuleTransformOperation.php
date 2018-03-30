<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\RuleEngine\RuleApplier;

/**
 * Class RuleTransformOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Transformer
 */
class RuleTransformOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    const VARIABLE_MATCH_REGEX = '/{(?<variables>[^{}]+)}/';

    /** @var string */
    protected $parsedColums = [];

    /** @var RuleApplier */
    protected $ruleApplier;

    /** @var array */
    protected $rules;

    /** @var boolean */
    protected $add;

    /**
     * RuleTransformOperation constructor.
     *
     * @param RuleApplier $ruleApplier
     * @param array $rules
     * @param boolean $add
     */
    public function __construct(RuleApplier $ruleApplier, array $rules, $add)
    {
        $this->ruleApplier = $ruleApplier;
        $this->rules = $rules;
        $this->add = $add;
    }

    /**
     * @param DataItemInterface $item
     * @param array $context
     *
     * @return ItemInterface
     * @throws \Oliverde8\Component\RuleEngine\Exceptions\RuleException
     * @throws \Oliverde8\Component\RuleEngine\Exceptions\UnknownRuleException
     */
    public function processData(DataItemInterface $item, array &$context)
    {
        $data = $item->getData();
        $newData = [];

        // We add data and don't send new data.
        if ($this->add) {
            $newData = $data;
        }

        foreach ($this->rules as $column => $rule) {
            // Add context to the data.
            $data['@context'] = array_merge($context, isset($rule['context']) ? $rule['context'] : []);

            $columnsValues = $this->resolveColumnVariables($column, $data, $newData);
            $possibleColumns = [];
            $this->getColumnPosssibleValues($column, $columnsValues, [], $possibleColumns);

            foreach ($possibleColumns as $column => $values) {
                $data['@column'] = $values;
                AssociativeArray::setFromKey($newData, $column, $this->ruleApplier->apply($data, $newData, $rule['rules'], []));
            }
        }

        return new DataItem($newData);
    }

    /**
     * Resolve list of variables.
     *
     * @param $columnString
     * @param $data
     * @param $newData
     *
     * @return array
     */
    protected function resolveColumnVariables($columnString, $data, $newData)
    {
        $variables = $this->getColumnVariables($columnString);
        $variableValues = [];

        foreach ($variables as $variable) {
            $data['@new'] = $newData;
            $variableValues[] = ['variable' => $variable,  'value' => AssociativeArray::getFromKey($data, $variable, "")];
        }

        return $variableValues;
    }

    /**
     * Get all possible values for the column.
     *
     * @param $columnString
     * @param $variableValues
     * @param $preparedValues
     * @param $valueCombinations
     */
    protected function getColumnPosssibleValues($columnString, $variableValues, $preparedValues, &$valueCombinations)
    {
        if (empty($variableValues)) {
            $key = $this->getColumnName($columnString, $preparedValues);
            $valueCombinations[$key] = $preparedValues;
            return;
        }

        // Shift elements in array.
        $firsVariable = reset($variableValues);
        array_shift($variableValues);

        // Handle possible multi values.
        if (is_array($firsVariable['value'])) {
            foreach ($firsVariable['value'] as $value) {
                $currentPreparedValues = $preparedValues;
                $currentPreparedValues[$firsVariable['variable']] = $value;

                $this->getColumnPosssibleValues($columnString, $variableValues, $currentPreparedValues, $valueCombinations);
            }
        } else {
            $currentPreparedValues[$firsVariable['variable']] = $firsVariable['value'];
            $this->getColumnPosssibleValues($columnString, $variableValues, $currentPreparedValues, $valueCombinations);
        }
    }

    /**
     * Get the name final name of the column
     *
     * @param $columnString
     * @param $values
     *
     * @return mixed
     */
    protected function getColumnName($columnString, $values)
    {
        $variables = [];
        $varValues = [];

        foreach ($values as $variableName => $value) {
            $variables[] = '{' . $variableName . '}';
            $varValues[] = $value;
        }

        return str_replace($variables, $varValues, $columnString);
    }

    /**
     * Get variables in a column.
     *
     * @param $columnsString
     *
     * @return mixed
     */
    protected function getColumnVariables($columnsString)
    {
        if (!isset($this->parsedColums[$columnsString])) {
            $matches = [];
            preg_match_all(self::VARIABLE_MATCH_REGEX, $columnsString, $matches);

            if (isset($matches['variables'])) {
                $this->parsedColums[$columnsString] = $matches['variables'];
            } else {
                $this->parsedColums[$columnsString] = [];
            }
        }

        return $this->parsedColums[$columnsString];
    }
}