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
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $method,
        private readonly string $url,
        private readonly bool $responseIsJson,
        private readonly ?string $optionsKey,
        protected ?string $responseKey
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }


    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();
        if ($this->optionsKey) {
            $options = AssociativeArray::getFromKey($data, $this->optionsKey, []);
        } else {
            $options = $data;
        }

        $url = $this->url;
        if (str_starts_with($url, "@")) {
            $url = ltrim($url, '@');
            $url = $this->expressionLanguage->evaluate($url, ['data' => $data]);
        }

        $response = $this->client->request($this->method, $url, $options);
        $response->getInfo();

        return new AsyncHttpClientResponseItem($this->client, $response, $this->responseIsJson, $this->responseKey, $data);
    }
}
