<?php

use Oliverde8\Component\PhpEtl\Model\State\OperationState;

require_once __DIR__ . '/../.init.php';

$startTime = microtime(true);

$observer = function (array $operationStates, int $processed, int $returned, bool $ended) use ($startTime): void {
    if (!$ended) {
        return;
    }

    printf("%-30s %12s %12s %10s\n", 'Operation', 'Processed', 'Returned', 'Time (ms)');
    /** @var OperationState $state */
    foreach ($operationStates as $state) {
        printf("%-30s %12d %12d %10d\n", $state->getOperationName(), $state->getItemsProcessed(), $state->getItemsReturned(), $state->getTimeSpent());
    }

    printf("\nTotal: %.2fs, peak memory: %.1f MB\n", microtime(true) - $startTime, memory_get_peak_usage(true) / 1024 / 1024);
};
