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

    public function getRootPath(): string
    {
        return "/";
    }

    public function fileExists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    public function write(string $path, string $contents, array $config = []): void
    {
        $this->filesystem->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, array $config = []): void
    {
        $this->filesystem->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->filesystem->read($path);
    }

    public function readStream(string $path)
    {
        return $this->filesystem->readStream($path);
    }

    public function delete(string $path): void
    {
       $this->filesystem->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->filesystem->deleteDirectory($path);
    }

    public function createDirectory(string $path, array $config = []): void
    {
        $this->filesystem->createDirectory($path, $config);
    }

    public function listContents(string $path): array
    {
        $listing = $this->filesystem->listContents($path);
        return $listing->toArray();
    }

    public function move(string $source, string $destination, array $config = [])
    {
        $this->filesystem->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, array $config = [])
    {
        $this->filesystem->copy($source, $destination, $config);
    }
}
