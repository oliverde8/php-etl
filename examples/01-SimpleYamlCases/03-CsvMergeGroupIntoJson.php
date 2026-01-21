<?php

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilder $chainBuilder */

$fileName = __DIR__ . '/config/03-json-grouped-merge.yml';

$chainProcessor = $chainBuilder->buildChainProcessor(Yaml::parse(file_get_contents($fileName)),[]);

$chainProcessor->process(
    new DataItem([
        'file' => 'data/customers.csv',
    ]),
    []
);
