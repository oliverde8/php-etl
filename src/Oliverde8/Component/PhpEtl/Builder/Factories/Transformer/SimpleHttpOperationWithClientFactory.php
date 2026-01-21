<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SimpleHttpOperationWithClientFactory extends AbstractFactory
{
    public function __construct(string $operation, private readonly HttpClientInterface $httpClient)
    {
        parent::__construct($operation, SimpleHttpOperation::class);
    }

    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        $httpConfig = new SimpleHttpConfig(
            $options['method'],
            $options['url'],
            $options['response_is_json'] ?? false,
            $options['option_key'] ?? null,
            $options['response_key'] ?? null,
        );

        return new SimpleHttpOperation(
            $this->httpClient,
            $httpConfig
        );
    }

    #[\Override]
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'method' => [
                new Assert\Choice(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD']),
                new Assert\NotBlank(),
            ],
            'url' => [
                new Assert\Type(["type" => "string"]),
                new Assert\NotBlank(),
            ],
            'response_is_json' => [
                new Assert\Type(["type" => "boolean"]),
            ],
            'option_key' => [
                new Assert\Type(["type" => "string"]),
            ],
            'response_key' => [
                new Assert\Type(["type" => "string"]),
            ],
            'options' => [
                new Assert\Type(["type" => "array"]),
            ],
        ]);
    }
}