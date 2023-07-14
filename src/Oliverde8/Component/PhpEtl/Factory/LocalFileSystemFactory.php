<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Factory;

use Oliverde8\Component\PhpEtl\ChainWorkDirManager;
use Oliverde8\Component\PhpEtl\Model\ExecutionInterface;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

class LocalFileSystemFactory implements FileSystemFactoryInterface
{
    private ChainWorkDirManager $chainWorkDirManager;

    /** @var FileSystemInterface[] */
    private array $fileSystems;

    public function __construct(ChainWorkDirManager $chainWorkDirManager)
    {
        $this->chainWorkDirManager = $chainWorkDirManager;
    }

    public function get(ExecutionInterface $execution): FileSystemInterface
    {
        if (!isset($this->fileSystems[$execution->getId()])) {
            $this->fileSystems[$execution->getId()] = new LocalFileSystem($this->chainWorkDirManager->getLocalTmpWorkDir($execution));
        }

        return $this->fileSystems[$execution->getId()];
    }
}