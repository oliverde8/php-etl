<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$symfonyOutput = new \Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput($output);

$chainProcessor->process(
    new ArrayIterator([getProcessFilePath(__DIR__, "/customers.csv")]),
    [],
    function (array $operationStates, int $itemsProcessed, int $itemsReturned, bool $hasEnded) use ($symfonyOutput) {
        $symfonyOutput->output($operationStates, $hasEnded);
    }
);
