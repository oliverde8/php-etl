<?php

function getEtlExecutionContextFactory() {
    $workdir = __DIR__ . "/../../var/";
    $dirManager = new \Oliverde8\Component\PhpEtl\ChainWorkDirManager($workdir);
    $loggerFactory = new \Oliverde8\Component\PhpEtl\Factory\NullLoggerFactory();
    $fileFactory = new \Oliverde8\Component\PhpEtl\Factory\LocalFileSystemFactory($dirManager);

    return new \Oliverde8\Component\PhpEtl\PerExecutionContextFactory($dirManager, $fileFactory, $loggerFactory);
}

require_once __DIR__ . "/../.init.php";

$options = [
    'etl' => [
        'execution' => new \Oliverde8\Component\PhpEtl\Model\PockExecution(new DateTime())
    ]
];