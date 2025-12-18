<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Psr\Log\LoggerInterface;

class PerExecutionExecutionContext extends ExecutionContext
{
    public function __construct(array $parameters, FileSystemInterface $fileSystem, LoggerInterface $logger, protected string $workDir)
    {
        parent::__construct($parameters, $fileSystem);
        $this->logger = $logger;
    }

    #[\Override]
    protected function finalise(): void
    {
        if (class_exists("Monolog\Logger") && $this->logger instanceof \Monolog\Logger) {
            foreach ($this->logger->getHandlers() as $handler) {
                $handler->close();
            }
        }

        if ($this->fileSystem instanceof LocalFileSystem && $this->fileSystem->getRootPath() == $this->workDir) {
            // Local file system needs no moving of the log file.
            return;
        }

        $logPath = $this->workDir . "/execution.log";
        if(file_exists($logPath)) {
            $this->fileSystem->writeStream("execution.log", fopen($logPath, 'r'));
        }
    }

}