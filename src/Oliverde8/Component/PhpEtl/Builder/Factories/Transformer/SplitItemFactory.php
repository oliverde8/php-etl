<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2022 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Transformer
 */
class SplitItemFactory extends AbstractFactory
{


    public function __construct(string $operation, string $class)
    {
        parent::__construct($operation, $class);
    }

    /**
     * @inheritdoc
     */
    public function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            $options['singleElement'] ?? true,
            $options['keys'],
            $options['keepKeys'] ?? false,
            $options['keyName'] ?? null,
            $options['duplicateKeys'] ?? []
        );
    }

    /**
     * @inheritdoc
     */
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection(
            [
                'keys' => [
                    new Assert\Type(["type" => "array"]),
                    new Assert\NotBlank(),
                ],
                'keyName' => new Assert\Type(["type" => "string"]),
                'singleElement' => new Assert\Type(["type" => "boolean"]),
                'keepKeys' => new Assert\Type(["type" => "boolean"]),
                'duplicateKeys' => [
                    new Assert\Type(["type" => "array"])
                ],
            ],
            allowMissingFields: true
        );
    }
}
