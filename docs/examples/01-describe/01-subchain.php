<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$chainProcessor->process(
    new ArrayIterator([getProcessFilePath(__DIR__, "/customers.csv")]),
    []
);
