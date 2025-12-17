<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Factory\FileSystemFactoryInterface;
use Oliverde8\Component\PhpEtl\Factory\LoggerFactoryInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\ExecutionInterface;
use Oliverde8\Component\PhpEtl\Model\PerExecutionExecutionContext;

class PerExecutionContextFactory implements ExecutionContextFactoryInterface
{
    public function __construct(private readonly ChainWorkDirManager $dirManager, private readonly FileSystemFactoryInterface $fileSystemFactory, private readonly LoggerFactoryInterface $loggerFactory)
    {
    }


    #[\Override]
    public function get(array $parameters): ExecutionContext
    {
        $execution = $parameters['etl']['execution'] ?? null;
        if (!$execution instanceof ExecutionInterface) {
            throw new \LogicException("Etl execution needs to have a unique execution object of type ExecutionInterface as per Execution Context is used");
        }

        $fileSystem = $this->fileSystemFactory->get($execution);
        $logger = $this->loggerFactory->get($execution);
        $workDir = $this->dirManager->getLocalTmpWorkDir($execution);

        return new PerExecutionExecutionContext($parameters, $fileSystem, $logger, $workDir);
    }
}