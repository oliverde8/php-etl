<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model\File;

use League\Flysystem\Filesystem;

class FlySystemFileSystem implements FileSystemInterface
{
    protected Filesystem $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    #[\Override]
    public function getRootPath(): string
    {
        return "/";
    }

    #[\Override]
    public function fileExists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    #[\Override]
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->filesystem->write($path, $contents, $config);
    }

    #[\Override]
    public function writeStream(string $path, $contents, array $config = []): void
    {
        $this->filesystem->writeStream($path, $contents, $config);
    }

    #[\Override]
    public function read(string $path): string
    {
        return $this->filesystem->read($path);
    }

    #[\Override]
    public function readStream(string $path)
    {
        return $this->filesystem->readStream($path);
    }

    #[\Override]
    public function delete(string $path): void
    {
       $this->filesystem->delete($path);
    }

    #[\Override]
    public function deleteDirectory(string $path): void
    {
        $this->filesystem->deleteDirectory($path);
    }

    #[\Override]
    public function createDirectory(string $path, array $config = []): void
    {
        $this->filesystem->createDirectory($path, $config);
    }

    #[\Override]
    public function listContents(string $path): array
    {
        $listing = $this->filesystem->listContents($path);
        return $listing->toArray();
    }

    #[\Override]
    public function move(string $source, string $destination, array $config = [])
    {
        $this->filesystem->move($source, $destination, $config);
    }

    #[\Override]
    public function copy(string $source, string $destination, array $config = [])
    {
        $this->filesystem->copy($source, $destination, $config);
    }
}
