<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Extract;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class ExternalFileFinderFactory extends AbstractFactory
{
    public function __construct(string $operation, string $class, protected FileSystemInterface $fileSystem)
    {
        parent::__construct($operation, $class);
    }


    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create($this->fileSystem, $options['directory']);
    }

    #[\Override]
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'directory' => new Assert\NotBlank(),
        ]);
    }
}