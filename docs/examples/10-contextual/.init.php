<?php

use Oliverde8\Component\PhpEtl\Builder\Factories\ChainSplitFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\CsvExtractFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\JsonExtractFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Grouping\SimpleGroupingFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\CsvFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\JsonFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\FilterDataFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\RuleTransformFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SimpleHttpOperationFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\SplitItemFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\JsonExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SplitItemOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ChainWorkDirManager;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\Component\PhpEtl\Factory\LocalFileSystemFactory;
use Oliverde8\Component\PhpEtl\Factory\NullLoggerFactory;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . "/../.init.php";

function getExecutionContextFactory(): ExecutionContextFactoryInterface
{
    $workdir = __DIR__ . "/../../../var/";
    $dirManager = new ChainWorkDirManager($workdir);
    $loggerFactory = new NullLoggerFactory();
    $fileFactory = new LocalFileSystemFactory($dirManager);

    return new \Oliverde8\Component\PhpEtl\PerExecutionContextFactory($dirManager, $fileFactory, $loggerFactory);
}
