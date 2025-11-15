<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Oliverde8\Component\RuleEngine\Rules\Get;
use Oliverde8\Component\RuleEngine\Rules\Implode;
use Oliverde8\Component\RuleEngine\Rules\StrToLower;
use Oliverde8\Component\RuleEngine\Rules\StrToUpper;
use Psr\Log\NullLogger;

require __DIR__ . '/../vendor/autoload.php';

$ruleApplier = new RuleApplier(
    new NullLogger(),
    [
        new Get(new NullLogger()),
        new Implode(new NullLogger()),
        new StrToLower(new NullLogger()),
        new StrToUpper(new NullLogger()),
        new ExpressionLanguage(new NullLogger()),
    ]
);

$chainBuilder = new ChainBuilderV2(
    new ExecutionContextFactory(),
    [
        new GenericChainFactory(CsvExtractOperation::class, CsvExtractConfig::class),
        new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class),
        new GenericChainFactory(RuleTransformOperation::class, RuleTransformConfig::class, injections: ['ruleApplier' => $ruleApplier]),
        new GenericChainFactory(FileWriterOperation::class, CsvFileWriterConfig::class),
    ],
);