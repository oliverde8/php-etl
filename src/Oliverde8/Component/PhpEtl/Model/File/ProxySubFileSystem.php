<?php

namespace Oliverde8\Component\PhpEtl\Model\File;

class ProxySubFileSystem implements FileSystemInterface
{
    protected $subDir;

    /**
     * @param $subDir
     * @param FileSystemInterface $fileSystem
     */
    public function __construct($subDir, protected FileSystemInterface $fileSystem)
    {
        $this->subDir = "/" . trim((string) $subDir, "/") . "/";
    }

    #[\Override]
    public function getRootPath(): string
    {
        return $this->fileSystem->getRootPath() . $this->subDir;
    }

    #[\Override]
    public function fileExists(string $path): bool
    {
        return $this->fileSystem->fileExists($this->subDir . $path);
    }

    #[\Override]
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->fileSystem->write($this->subDir . $path, $contents, $config);
    }

    #[\Override]
    public function writeStream(string $path, $contents, array $config = []): void
    {
        $this->fileSystem->writeStream($this->subDir . $path, $contents, $config);
    }

    #[\Override]
    public function read(string $path): string
    {
        return $this->fileSystem->read($this->subDir . $path);
    }

    #[\Override]
    public function readStream(string $path)
    {
        return $this->fileSystem->readStream($this->subDir . $path);
    }

    #[\Override]
    public function delete(string $path): void
    {
        $this->fileSystem->delete($this->subDir . $path);
    }

    #[\Override]
    public function deleteDirectory(string $path): void
    {
        $this->fileSystem->deleteDirectory($this->subDir . $path);
    }

    #[\Override]
    public function createDirectory(string $path, array $config = []): void
    {
        $this->fileSystem->createDirectory($this->subDir . $path, $config);
    }

    #[\Override]
    public function listContents(string $path): array
    {
        return $this->fileSystem->listContents($this->subDir . $path);
    }

    #[\Override]
    public function move(string $source, string $destination, array $config = [])
    {
        $this->fileSystem->move($this->subDir . $source, $this->subDir . $destination, $config);
    }

    #[\Override]
    public function copy(string $source, string $destination, array $config = [])
    {
        $this->fileSystem->copy($this->subDir . $source, $this->subDir . $destination, $config);
    }
}