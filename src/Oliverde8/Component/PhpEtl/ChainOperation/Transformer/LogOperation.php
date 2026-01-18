<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LogOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    protected readonly ExpressionLanguage $expressionLanguage;

    public function __construct(protected readonly LogConfig $config)
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = new AssociativeArray($item->getData());
        $message = $this->config->message;
        if (str_starts_with($message, "@")) {
            $message = ltrim($message, "@");
            $message = $this->expressionLanguage->evaluate($message, ['data' => $item->getData(), 'context' => $context->getParameters()]);
        }

        $logContext = [];
        foreach ($this->config->context as $key => $valueKey) {
            $logContext[$key] = $data->get($valueKey);
        }

        match ($this->config->level) {
            'debug' => $context->getLogger()->debug($message, $logContext),
            'info' => $context->getLogger()->info($message, $logContext),
            'notice' => $context->getLogger()->notice($message, $logContext),
            'warning' => $context->getLogger()->warning($message, $logContext),
            'error' => $context->getLogger()->error($message, $logContext),
            'critical' => $context->getLogger()->critical($message, $logContext),
            'alert' => $context->getLogger()->alert($message, $logContext),
            'emergency' => $context->getLogger()->emergency($message, $logContext),
            default => $item,
        };

        return $item;
    }
}
