<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/products.json"]),
    [
        'locales' => ['fr_FR', 'en_US']
    ]
);