<?php

namespace Oliverde8\Component\PhpEtl\Item;

class FileLoadedItem extends FileItem
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
