<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LogOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    protected readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        protected readonly string $message,
        protected readonly string $level,
        protected readonly array $context,
    ){
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = new AssociativeArray($item->getData());
        $message = $this->message;
        if (strpos($message, "@") === 0) {
            $message = ltrim($message, "@");
            $message = $this->expressionLanguage->evaluate($message, ['data' => $item->getData(), 'context' => $context->getParameters()]);
        }

        $logContext = [];
        foreach ($this->context as $key => $valueKey) {
            $logContext[$key] = $data->get($valueKey);
        }

        switch ($this->level) {
            case 'debug':
                $context->getLogger()->debug($message, $logContext);
                break;
            case 'info':
                $context->getLogger()->info($message, $logContext);
                break;
            case 'notice':
                $context->getLogger()->notice($message, $logContext);
                break;
            case 'warning':
                $context->getLogger()->warning($message, $logContext);
                break;
            case 'error':
                $context->getLogger()->error($message, $logContext);
                break;
            case 'critical':
                $context->getLogger()->critical($message, $logContext);
                break;
            case 'alert':
                $context->getLogger()->alert($message, $logContext);
                break;
            case 'emergency':
                $context->getLogger()->emergency($message, $logContext);
                break;
        }

        return $item;
    }
}
