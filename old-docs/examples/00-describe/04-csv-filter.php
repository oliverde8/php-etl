<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$chainProcessor->process(
    new ArrayIterator(["/customers.csv", "/customers2.csv"]),
    []
);