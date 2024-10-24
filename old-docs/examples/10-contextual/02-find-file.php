<?php

use Oliverde8\Component\PhpEtl\Model\PockExecution;

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$dir = __DIR__ . "/02-find-file";
copy("$dir/file1-demo.csv", "$dir/file1.csv");

$options = [
    'etl' => [
        'execution' => new PockExecution(new DateTime())
    ],
    'dir' => $dir,
];

$chainProcessor->process(
    new ArrayIterator(['/^file[0-9]\.csv$/']),
    $options
);
