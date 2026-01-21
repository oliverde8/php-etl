<?php

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilder $chainBuilder */

$fileName = __DIR__ . '/config/06-csv-transform.yml';

$inputOptions = ['filewriter' =>
    ['outputfile' =>
        ['name' => 'data/configured-output.csv']
    ]
];

$chainProcessor = $chainBuilder->buildChainProcessor(Yaml::parse(file_get_contents($fileName)), $inputOptions);

$chainProcessor->process(
    new DataItem([
        'file' => 'data/customers.csv',
    ]),
    []
);


