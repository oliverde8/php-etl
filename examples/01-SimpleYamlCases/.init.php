<?php

use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\LogFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\LogOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;

use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\CsvExtractFactory;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;

use Psr\Log\AbstractLogger;

require __DIR__ . '/../../vendor/autoload.php';

// Simple logger that outputs to console
class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        echo "[{$level}] {$message}{$contextStr}\n";
    }
}

if (!function_exists('getEtlExecutionContextFactory')) {
    function getEtlExecutionContextFactory(): ExecutionContextFactory {
        return new ExecutionContextFactory();
    }
}

$chainBuilder = new ChainBuilder(getEtlExecutionContextFactory());

$chainBuilder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
$chainBuilder->registerFactory(new LogFactory('log', LogOperation::class));
