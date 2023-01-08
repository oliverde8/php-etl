<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$chainProcessor->process(
    new ArrayIterator([["id"=> 1],["id"=> 4],["id"=> 7],["id"=> 10]]),
    []
);