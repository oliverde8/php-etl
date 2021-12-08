<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use oliverde8\AssociativeArraySimplified\AssociativeArray;

class LoggerContext
{
    protected array $loggerContext = [];

    protected function setLoggerContext($key, $value)
    {
        AssociativeArray::setFromKey($loggerContext, $key, $value, ".");
    }
}
