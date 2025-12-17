<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$symfonyOutput = new \Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput($output);

$chainProcessor->process(
    new ArrayIterator(["/customers.csv"]),
    [],
    function (array $operationStates, int $itemsProcessed, int $itemsReturned, bool $hasEnded) use ($symfonyOutput): void {
        $symfonyOutput->output($operationStates, $hasEnded);
    }
);
