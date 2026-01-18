<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Model\ExecutionInterface;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

class ChainWorkDirManager
{
    private readonly LocalFileSystem $fileSystem;

    public function __construct(private readonly string $baseDir)
    {
        $this->fileSystem = new LocalFileSystem('/');
    }

    public function getLocalTmpWorkDir(ExecutionInterface $execution, $createIfMissing = true): string
    {
        $currentTime = $execution->getCreateTime()->format("y/m/d");
        $dir = $this->baseDir . "/" . $currentTime . "/id-" . $execution->getId();

        if ($createIfMissing) {
            $this->fileSystem->createDirectory($dir);
        }

        return $dir;
    }
}