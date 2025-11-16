<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\AsyncHttpClientResponseItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SimpleHttpOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(private readonly HttpClientInterface $client, private readonly SimpleHttpConfig $config)
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();
        if ($this->config->optionKey) {
            $options = AssociativeArray::getFromKey($data, $this->config->optionKey, []);
        } else {
            $options = $data;
        }

        $url = $this->config->url;
        if (strpos($url, "@") === 0) {
            $url = ltrim($url, '@');
            $url = $this->expressionLanguage->evaluate($url, ['data' => $data]);
        }

        $response = $this->client->request($this->config->method, $url, $options);
        $response->getInfo();

        return new AsyncHttpClientResponseItem($this->client, $response, $this->config->responseIsJson, $this->config->responseKey, $data);
    }

    public function getConfigurationClass(): string
    {
        return SimpleHttpConfig::class;
    }
}
