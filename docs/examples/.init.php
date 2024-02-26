<?php

use Oliverde8\Component\PhpEtl\Builder\Factories\ChainSplitFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\CsvExtractFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\ExternalFileFinderFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\JsonExtractFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Grouping\SimpleGroupingFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\CsvFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\JsonFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\ExternalFileProcessorFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\FilterDataFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\RuleTransformFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SimpleHttpOperationFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SplitItemFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\JsonExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\ExternalFileProcessorOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SplitItemOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . "/../../vendor/autoload.php";

function getBuilder(){
    $ruleApplier = new \Oliverde8\Component\RuleEngine\RuleApplier(
        new \Psr\Log\NullLogger(),
        [
            new \Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToUpper(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage(new \Psr\Log\NullLogger()),
        ]
    );

    $builder = new ChainBuilder(getExecutionContextFactory());
    $builder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
    $builder->registerFactory(new FilterDataFactory('filter', FilterDataOperation::class, $ruleApplier));
    $builder->registerFactory(new SimpleGroupingFactory('simple-grouping', SimpleGroupingOperation::class));
    $builder->registerFactory(new ChainSplitFactory('split', ChainSplitOperation::class, $builder));
    $builder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));
    $builder->registerFactory(new JsonFileWriterFactory('json-write', FileWriterOperation::class));
    $builder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
    $builder->registerFactory(new JsonExtractFactory('json-read', JsonExtractOperation::class));
    $builder->registerFactory(new SplitItemFactory('split-item', SplitItemOperation::class));
    $builder->registerFactory(new SimpleHttpOperationFactory('http', SimpleHttpOperation::class));
    $builder->registerFactory(new ExternalFileFinderFactory('external-file-finder-local', ExternalFileFinderOperation::class, new LocalFileSystem("/")));
    $builder->registerFactory(new ExternalFileProcessorFactory("external-file-processor", ExternalFileProcessorOperation::class));

    return $builder;
}

function getChainProcessor($fileName, $options = []): ChainProcessor
{
    $fileName = str_replace(".php", ".yml", $fileName);

    return getBuilder()->buildChainProcessor(
        Yaml::parse(file_get_contents($fileName)),
        $options,
        1
    );
}

function getProcessFilePath($dir, $filName): string
{
    $cwd = getcwd();
    return str_replace($cwd, "", $dir) . "$filName";
}
