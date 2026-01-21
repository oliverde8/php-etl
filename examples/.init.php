<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\JsonExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\ExternalFileProcessorOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\LogOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SplitItemOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;

use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Oliverde8\Component\RuleEngine\Rules\Get;
use Oliverde8\Component\RuleEngine\Rules\Implode;
use Oliverde8\Component\RuleEngine\Rules\StrToLower;
use Oliverde8\Component\RuleEngine\Rules\StrToUpper;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;

require __DIR__ . '/../vendor/autoload.php';

// Simple logger that outputs to console
class ConsoleLogger extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        echo "[{$level}] {$message}{$contextStr}\n";
    }
}

$ruleApplier = new RuleApplier(
    new NullLogger(),
    [
        new Get(new ConsoleLogger()),
        new Implode(new NullLogger()),
        new StrToLower(new NullLogger()),
        new StrToUpper(new NullLogger()),
        new ExpressionLanguage(new NullLogger()),
    ]
);

$client = HttpClient::create(['headers' => ['Accept' => 'application/json']]);

if (!function_exists('getEtlExecutionContextFactory')) {
    function getEtlExecutionContextFactory() {
        return new ExecutionContextFactory();
    }
}

$chainBuilder = new ChainBuilderV2(
    getEtlExecutionContextFactory(),
    [
        new GenericChainFactory(CsvExtractOperation::class, CsvExtractConfig::class),
        new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class),
        new GenericChainFactory(RuleTransformOperation::class, RuleTransformConfig::class, injections: ['ruleApplier' => $ruleApplier]),
        new GenericChainFactory(FileWriterOperation::class, CsvFileWriterConfig::class),
        new GenericChainFactory(SimpleGroupingOperation::class, SimpleGroupingConfig::class),
        new GenericChainFactory(FilterDataOperation::class, FilterDataConfig::class, injections: ['ruleApplier' => $ruleApplier]),
        new GenericChainFactory(ChainMergeOperation::class, ChainMergeConfig::class),
        new GenericChainFactory(ChainRepeatOperation::class, ChainRepeatConfig::class),
        new GenericChainFactory(ChainSplitOperation::class, ChainSplitConfig::class),
        new GenericChainFactory(JsonExtractOperation::class, JsonExtractConfig::class),
        new GenericChainFactory(SimpleHttpOperation::class, SimpleHttpConfig::class, injections: ['client' => $client]),
        new GenericChainFactory(SplitItemOperation::class, SplitItemConfig::class),
        new GenericChainFactory(LogOperation::class, LogConfig::class),
        new GenericChainFactory(FailSafeOperation::class, FailSafeConfig::class),
        new GenericChainFactory(ExternalFileFinderOperation::class, ExternalFileFinderConfig::class, injections: ['fileSystem' => new LocalFileSystem("/")]),
        new GenericChainFactory(ExternalFileProcessorOperation::class, ExternalFileProcessorConfig::class),
    ],
);
