<?php

require_once __DIR__ . "/.init.php";

$context = ['filewriter' =>
    ['outputfile' =>
        ['name' => 'configured-output.csv']
    ]
];

$chainProcessor = getChainProcessor(__FILE__, $context);

$chainProcessor->process(
    new ArrayIterator(["/customers.csv"]),
    $context
);