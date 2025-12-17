<?php
declare(strict_types=1);

namespace ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;

class SimpleHttpOperationTest extends TestCase
{
    public function testTimeout()
    {
        $endOperation = new CallbackTransformerOperation(new CallBackTransformerConfig(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        }));

        $chain = $this->createChain(
            'http://www.google.com:81',
            'GET',
            [$endOperation],
        );

        $this->expectException(TransportException::class);
        $chain->process(new \ArrayIterator([['var' => 1]]), []);
    }

    protected function createChain(string $url, string $method, array $afterOperations, bool $responseIsJson = false): ChainProcessor
    {
        $httpClient = HttpClient::create(['timeout' => 1]);

        $executionFactory = new ExecutionContextFactory();
        $repeatOperation = new SimpleHttpOperation(
            $httpClient,
            new SimpleHttpConfig(
                method: $method,
                url: $url,
                responseIsJson: $responseIsJson,
                optionKey: 'options',
                responseKey: 'result'
            )
        );

        array_unshift($afterOperations, $repeatOperation);
        return new ChainProcessor($afterOperations, $executionFactory);
    }
}