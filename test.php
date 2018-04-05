<?php

require_once __DIR__ . '/vendor/autoload.php';

use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\CsvFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\RuleTransformFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Grouping\SimpleGroupingFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Extract\File\Csv as CsvExtract;
use Oliverde8\Component\PhpEtl\Load\File\Csv as CsvLoad;
use Symfony\Component\Yaml\Yaml;

$ruleApplier = new \Oliverde8\Component\RuleEngine\RuleApplier(
    new \Psr\Log\NullLogger(),
    [
        new \Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\StrToUpper(new \Psr\Log\NullLogger()),
    ]
);


$builder = new \Oliverde8\Component\PhpEtl\ChainBuilder();
$builder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
$builder->registerFactory(new SimpleGroupingFactory('simple-grouping', SimpleGroupingOperation::class));
$builder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));

$inputIterator = new CsvExtract(__DIR__  . '/exemples/I-Service.csv');
$operations = [];

$chainProcessor = $builder->buildChainProcessor(Yaml::parse(file_get_contents(__DIR__ . '/exemples/etl_chain.yml')));
$chainProcessor->process($inputIterator, ['locales' => ['fr_FR', 'be_FR', 'nl_NL']]);
die();

// Cleanup the data to use akeneo attribute codes.
$operations[] = new RuleTransformOperation(
    $ruleApplier,
    Yaml::parse(file_get_contents(__DIR__ . '/exemples/transformation1.yml')),
    true
);

// Group products by sku.
$operations[] = new SimpleGroupingOperation(['sku'], ['locale']);

// Finalize transformation by having proper attribute codes taking locales into account.
$operations[] = new RuleTransformOperation(
    $ruleApplier,
    Yaml::parse(file_get_contents(__DIR__ . '/exemples/transformation2.yml')),
    false
);

// Write into files.
$operations[] = new FileWriterOperation(new CsvLoad(__DIR__ . '/exemples/output.csv'));

$chainProcessor = new ChainProcessor($operations);
$chainProcessor->process($inputIterator, ['locales' => ['fr_FR', 'be_FR', 'nl_NL']]);