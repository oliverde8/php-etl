<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__DIR__ . '/01-csv-transform.yml');

$chainProcessor->process(
    new ArrayIterator(["/customers.csv", "/customers2.csv"]),
    []
);