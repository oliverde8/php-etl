<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\AsyncHttpClientResponseItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SimpleHttpOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    private HttpClientInterface $client;

    private string $method = "GET";
    private string $url;

    private bool $responseIsJson;

    private ?string $optionsKey;

    protected ?string $responseKey;

    private ExpressionLanguage $expressionLanguage;

    public function __construct(
        HttpClientInterface $client,
        string $method,
        string $url,
        bool $responseIsJson,
        ?string $optionsKey,
        ?string $responseKey
    ) {
        $this->client = $client;
        $this->method = $method;
        $this->url = $url;
        $this->responseIsJson = $responseIsJson;
        $this->optionsKey = $optionsKey;
        $this->responseKey = $responseKey;

        $this->expressionLanguage = new ExpressionLanguage();
    }


    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();
        if ($this->optionsKey) {
            $options = AssociativeArray::getFromKey($data, $this->optionsKey, []);
        } else {
            $options = $data;
        }

        $url = $this->url;
        if (strpos($url, "@") === 0) {
            $url = ltrim($url, '@');
            $url = $this->expressionLanguage->evaluate($url, ['data' => $data]);
        }

        $response = $this->client->request($this->method, $url, $options);
        $response->getInfo();

        return new AsyncHttpClientResponseItem($this->client, $response, $this->responseIsJson, $this->responseKey, $data);
    }
}
