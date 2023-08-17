<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Item;

use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;

class ExternalFileItem implements ItemInterface
{
    const STATE_NEW = "new";
    const STATE_PROCESSING = "processing";

    const STATE_PROCESSED = "processed";

    protected string $filePath;

    protected FileSystemInterface $fileSystem;

    protected string $state;

    public function __construct(string $filePath, FileSystemInterface $fileSystem)
    {
        $this->fileSystem = $fileSystem;
        $this->filePath = $filePath;
        $this->state = self::STATE_NEW;
    }

    public function getFileSystem(): FileSystemInterface
    {
        return $this->fileSystem;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }
}