<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;


class RuleTransformOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    const VARIABLE_MATCH_REGEX = '/{(?<variables>[^{}]+)}/';

    /** @var string[] */
    protected array $parsedColumns = [];

    public function __construct(private readonly RuleApplier $ruleApplier, private readonly RuleTransformConfig $config)
    {}

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): DataItemInterface
    {
        $data = $item->getData();
        $newData = [];

        // We add data and don't send new data.
        if ($this->config->add) {
            $newData = $data;
        }

        foreach ($this->config->getRules() as $column => $rule) {
            // Add context to the data.
            $data['@context'] = array_merge($context->getParameters(), $rule['context'] ?? []);

            $columnsValues = $this->resolveColumnVariables((string) $column, $data, $newData);
            $possibleColumns = [];
            $this->getColumnPossibleValues($column, $columnsValues, [], $possibleColumns);

            foreach ($possibleColumns as $column => $values) {
                $data['@column'] = $values;
                AssociativeArray::setFromKey($newData, $column, $this->ruleApplier->apply($data, $newData, $rule['rules'], []));
            }
        }

        return new DataItem($newData);
    }

    protected function resolveColumnVariables(string $columnString, array $data, array $newData): array
    {
        $variables = $this->getColumnVariables($columnString);
        $variableValues = [];

        foreach ($variables as $variable) {
            $data['@new'] = $newData;
            $variableValues[] = ['variable' => $variable,  'value' => AssociativeArray::getFromKey($data, $variable, "")];
        }

        return $variableValues;
    }

    protected function getColumnPossibleValues(
        string $columnString,
        array $variableValues,
        array $preparedValues,
        array &$valueCombinations
    ): void {
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

                $this->getColumnPossibleValues($columnString, $variableValues, $currentPreparedValues, $valueCombinations);
            }
        } else {
            $currentPreparedValues[$firsVariable['variable']] = $firsVariable['value'];
            $this->getColumnPossibleValues($columnString, $variableValues, $currentPreparedValues, $valueCombinations);
        }
    }

    protected function getColumnName(string $columnString, array $values): string
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
     */
    protected function getColumnVariables(string $columnsString): array
    {
        if (!isset($this->parsedColumns[$columnsString])) {
            $matches = [];
            preg_match_all(self::VARIABLE_MATCH_REGEX, $columnsString, $matches);

            if (isset($matches['variables'])) {
                $this->parsedColumns[$columnsString] = $matches['variables'];
            } else {
                $this->parsedColumns[$columnsString] = [];
            }
        }

        return $this->parsedColumns[$columnsString];
    }
}
