<?php

namespace Oliverde8\Component\PhpEtl\Item;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AsyncHttpClientResponseItem implements AsyncItemInterface
{
    private HttpClientInterface $client;

    private ResponseInterface $response;

    private bool $responseIsJson;

    private ?string $responseKey;

    private array $baseData;

    private bool $isRunning = true;

    /**
     * @param ResponseInterface $response
     * @param bool $responseIsJson
     * @param string|null $responseKey
     * @param array $baseData
     */
    public function __construct(
        HttpClientInterface $client,
        ResponseInterface $response,
        bool $responseIsJson,
        ?string $responseKey,
        array $baseData
    ) {
        $this->client = $client;
        $this->response = $response;
        $this->responseIsJson = $responseIsJson;
        $this->responseKey = $responseKey;
        $this->baseData = $baseData;
    }


    public function isRunning(): bool
    {
        try {
            foreach ($this->client->stream($this->response, 0.01) as $chunk) {
                if ($chunk->isLast()) {
                    return false;
                }
            }
        } catch (TimeoutException $exception) {
            // This is normal, we have used a very low stream timeout because we wish to continue processing
            // other items while this item is being downloaded.
            return true;
        }
    }

    public function getItem(): ItemInterface
    {
        $responseData = [
            'content' => $this->response->getContent(),
            'headers' => $this->response->getHeaders(),
            'status_code' => $this->response->getStatusCode(),
        ];
        if ($this->responseIsJson) {
            $responseData['content'] = $this->response->toArray();
        }

        $data = $this->baseData;
        if ($this->responseKey) {
            AssociativeArray::setFromKey($data, $this->responseKey, $responseData);
        } else {
            $data = $responseData;
        }

        return new DataItem($data);
    }
}
