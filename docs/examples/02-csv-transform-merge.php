<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__DIR__ . '/01-csv-transform.yml');

$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv", __DIR__ . "/customers2.csv"]),
    []
);