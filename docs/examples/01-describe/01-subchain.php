<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$symfonyOutput = new \Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput($output);

$chainProcessor->process(
    new ArrayIterator([getProcessFilePath(__DIR__, "/customers.csv")]),
    [],
    function (array $operationStates) use ($symfonyOutput) {
        $symfonyOutput->output($operationStates);
        // Slow down to make output more "interesting".
        usleep(500000);
    }
);
