<?php

declare(strict_types=1);

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilder $chainBuilder */

$fileName = __DIR__ . '/config/09-api-to-csv2.yml';

$chainProcessor = $chainBuilder->buildChainProcessor(Yaml::parse(file_get_contents($fileName)), []);

$chainProcessor->process(
    new ArrayIterator([
        ["id" => 1],
        ["id" => 2],
        ["id" => 3],
        ["id" => 4],
        ["id" => 5],
        ["id" => 6],
        ["id" => 7],
        ["id" => 8],
        ["id" => 9],
        ["id" => 10],
        ["id" => 11],
        ["id" => 12],
        ["id" => 13],
        ["id" => 14],
        ["id" => 15],
        ["id" => 16],
        ["id" => 17],
        ["id" => 18],
        ["id" => 19],
    ]),
    []
);
