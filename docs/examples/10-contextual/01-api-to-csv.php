<?php

use Oliverde8\Component\PhpEtl\Model\PockExecution;

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$options = [
    'etl' => [
        'execution' => new PockExecution(new DateTime())
    ]
];

$chainProcessor->process(
    new ArrayIterator([[]]),
    $options
);
