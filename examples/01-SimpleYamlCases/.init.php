<?php

use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\CsvFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\FilterDataFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\LogFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\RuleTransformFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SplitItemFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\LogOperation;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SimpleHttpOperationFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SplitItemOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;

use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\CsvExtractFactory;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Oliverde8\Component\RuleEngine\Rules\Get;
use Oliverde8\Component\RuleEngine\Rules\Implode;
use Oliverde8\Component\RuleEngine\Rules\StrToLower;
use Oliverde8\Component\RuleEngine\Rules\StrToUpper;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

require_once __DIR__ . '/../../vendor/autoload.php';

// Simple logger that outputs to console
class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        echo "[{$level}] {$message}{$contextStr}\n";
    }
}

$ruleApplier = new RuleApplier(
    new ConsoleLogger(),
    [
        new Get(new ConsoleLogger()),
        new Implode(new ConsoleLogger()),
        new StrToLower(new ConsoleLogger()),
        new StrToUpper(new ConsoleLogger()),
        new ExpressionLanguage(new ConsoleLogger()),
    ]
);

if (!function_exists('getEtlExecutionContextFactory')) {
    function getEtlExecutionContextFactory(): ExecutionContextFactory {
        return new ExecutionContextFactory();
    }
}

$chainBuilder = new ChainBuilder(getEtlExecutionContextFactory());

$chainBuilder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
$chainBuilder->registerFactory(new LogFactory('log', LogOperation::class));
$chainBuilder->registerFactory(new SimpleHttpOperationFactory('http', SimpleHttpOperation::class));
$chainBuilder->registerFactory(new SplitItemFactory('split-item', SplitItemOperation::class));
$chainBuilder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));
$chainBuilder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
$chainBuilder->registerFactory(new FilterDataFactory('filter', FilterDataOperation::class, $ruleApplier));

