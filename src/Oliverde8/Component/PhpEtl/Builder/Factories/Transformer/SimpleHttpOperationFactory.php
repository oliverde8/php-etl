<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class SimpleHttpOperationFactory extends AbstractFactory
{
    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        if (!class_exists(HttpClient::class)) {
            throw new \LogicException("You can not used SimpleHttpOperation as symfony/http-client is not installed");
        }

        $httpClient = HttpClient::create($options['options']);

        $httpConfig = new SimpleHttpConfig(
            $options['method'],
            $options['url'],
            $options['response_is_json'] ?? false,
            $options['option_key'] ?? null,
            $options['response_key'] ?? null,
        );

        return new SimpleHttpOperation(
            $httpClient,
            $httpConfig
        );
    }

    #[\Override]
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'method' => [
                new Assert\Choice(
                    choices: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD']
                ),
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
