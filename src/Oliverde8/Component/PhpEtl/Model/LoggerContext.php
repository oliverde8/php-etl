<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerContext
{
    protected array $loggerContext = [];

    protected ?LoggerInterface $logger = null;

    protected function setLoggerContext($key, $value)
    {
        AssociativeArray::setFromKey($loggerContext, $key, $value, ".");
    }

    protected function replaceLoggerContext(array $loggerContext): void
    {
        $this->loggerContext = $loggerContext;
    }

    public function getLogger(): LoggerInterface
    {
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    protected function finalise(): void
    {

    }
}
