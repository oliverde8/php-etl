<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;

class ExecutionContext extends LoggerContext
{
    protected array $parameters;

    protected FileSystemInterface $fileSystem;

    /**
     * @param array $parameters
     * @param FileSystemInterface $fileSystem
     */
    public function __construct(array $parameters, FileSystemInterface $fileSystem)
    {
        $this->parameters = $parameters;
        $this->fileSystem = $fileSystem;
    }


    public function getLoggerContext(array $additionalContextData): array
    {
        return $additionalContextData + $this->loggerContext;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key, $default = null)
    {
        return AssociativeArray::getFromKey($this->parameters, $key, $default, ".");
    }

    public function setParameter(string $key, $value): void
    {
        AssociativeArray::setFromKey($this->parameters, $key, $value, ".");
    }

    public function getFileSystem(): FileSystemInterface
    {
        return $this->fileSystem;
    }



}