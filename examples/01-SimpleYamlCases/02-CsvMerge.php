<?php

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilder $chainBuilder */

$fileName = __DIR__ . '/config/02-csv-merge.yaml';

$chainProcessor = $chainBuilder->buildChainProcessor(Yaml::parse(file_get_contents($fileName)),[]);

$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv',]), new DataItem(['file' => 'data/customers2.csv',])]),
    []
);
