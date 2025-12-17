<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;

class ExecutionContext extends LoggerContext
{
    /**
     * @param array $parameters
     * @param FileSystemInterface $fileSystem
     */
    public function __construct(protected array $parameters, protected FileSystemInterface $fileSystem)
    {
    }


    public function getLoggerContext(array $additionalContextData): array
    {
        return $additionalContextData + $this->loggerContext;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key, $default = null, string $separator = ".")
    {
        return AssociativeArray::getFromKey($this->parameters, $key, $default, $separator);
    }

    public function setParameter(string $key, $value, string $separator = "."): void
    {
        AssociativeArray::setFromKey($this->parameters, $key, $value, $separator);
    }

    public function getFileSystem(): FileSystemInterface
    {
        return $this->fileSystem;
    }
}
