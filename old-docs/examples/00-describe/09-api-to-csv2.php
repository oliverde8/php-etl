<?php

require_once __DIR__ . "/.init.php";

$chainProcessor = getChainProcessor(__FILE__);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$symfonyOutput = new \Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput($output);

$chainProcessor->process(
    new ArrayIterator(
        [
            ["id"=> 1],
            ["id"=> 2],
            ["id"=> 3],
            ["id"=> 4],
            ["id"=> 5],
            ["id"=> 6],
            ["id"=> 7],
            ["id"=> 8],
            ["id"=> 9],
            ["id"=> 10],
            ["id"=> 11],
            ["id"=> 12],
            ["id"=> 13],
            ["id"=> 14],
            ["id"=> 15],
            ["id"=> 16],
            ["id"=> 17],
            ["id"=> 18],
            ["id"=> 19],
        ]),
    [],
    function (array $operationStates, int $itemsProcessed, int $itemsReturned, bool $hasEnded) use ($symfonyOutput): void {
        $symfonyOutput->output($operationStates, $hasEnded);
    }
);
