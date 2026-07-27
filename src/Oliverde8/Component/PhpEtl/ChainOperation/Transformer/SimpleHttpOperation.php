<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\Item\AsyncHttpClientResponseItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SimpleHttpOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly SimpleHttpConfig $config,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator = new ExpressionEvaluator(),
    ) {
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();
        if ($this->config->optionKey) {
            $options = AssociativeArray::getFromKey($data, $this->config->optionKey, []);
        } else {
            $options = $data;
        }

        $url = $this->expressionEvaluator->evaluateIfExpression(
            $this->config->url,
            ['data' => $data, 'context' => $context->getParameters()],
        );

        $response = $this->client->request($this->config->method, $url, $options);
        $response->getInfo();

        return new AsyncHttpClientResponseItem($this->client, $response, $this->config->responseIsJson, $this->config->responseKey, $data);
    }
}
