<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model\File;

interface FileSystemInterface
{
    public function getRootPath(): string;

    public function fileExists(string $path): bool;

    public function write(string $path, string $contents, array $config = []): void;

    public function writeStream(string $path, $contents, array $config = []): void;

    public function read(string $path): string;

    public function readStream(string $path);

    public function delete(string $path): void;

    public function deleteDirectory(string $path): void;

    public function createDirectory(string $path, array $config = []): void;

    public function listContents(string $path): array;

    public function move(string $source, string $destination, array $config = []);

    public function copy(string $source, string $destination, array $config = []);
}