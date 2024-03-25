<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$symfonyOutput = new \Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput($output);

$chainProcessor->process(
    new ArrayIterator(["/customers.csv", "/customers2.csv"]),
    [],
    function (array $operationStates) use ($symfonyOutput) {
        $symfonyOutput->output($operationStates);
        // Just so that the output is nicer.
        usleep(100000);
    }
);
