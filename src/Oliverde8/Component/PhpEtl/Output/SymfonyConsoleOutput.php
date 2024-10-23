<?php

namespace Oliverde8\Component\PhpEtl\Output;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\Model\State\OperationState;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SymfonyConsoleOutput
{
    protected AssociativeArray $progressIndicators;

    protected int $lastUpdatedAt = 0;

    public function __construct(
        protected readonly OutputInterface $output,
        protected readonly int $updateFrequency = 1
    ) {
        $updateFrequency = $updateFrequency > 0 ? $updateFrequency : 1;
        $this->progressIndicators = new AssociativeArray();
    }

    public function output(array $operationStates, $ended)
    {
        if ((($this->lastUpdatedAt + $this->updateFrequency) >= time()) && !$ended) {
            // Don't update output.
            return;
        }

        if (empty($this->progressIndicators->getArray())) {
            $this->initProgressIndicators($operationStates);
        }

        $this->outputProgressIndicators($operationStates);

        // Update last, so it's slow we still wait a bit in between.
        $this->lastUpdatedAt = time();
    }

    public function outputProgressIndicators(array $operationStates, array $parentIds = [], int $level = 0)
    {
        foreach ($operationStates as $id => $state) {
            /** @var OperationState $state */
            $ids = $parentIds;
            $ids[] = $id . "P";

            $progressBar = $this->progressIndicators->get($ids);
            $progressBar->setMessage($this->getMessage($id, $state, $level));
            $progressBar->advance();


            foreach ($state->getSubStates() as $key => $subStates) {
                $ids = $parentIds;
                $ids[] = $id;
                $ids[] = $key;

                $this->outputProgressIndicators($subStates, $ids, $level+1);
            }
        }
    }

    private function initProgressIndicators(array $operationStates, array $parentIds = [], int $level = 0)
    {
        ProgressBar::setFormatDefinition('custom', '-  %message%');

        foreach ($operationStates as $id => $state) {
            /** @var OperationState $state */
            $section = $this->output->section();
            $ids = $parentIds;
            $ids[] = $id . "P";

            $progressBar = new ProgressBar($section, 0, 0);
            $progressBar->setMessage($this->getMessage($id, $state, $level));
            $progressBar->setFormat("custom");
            $progressBar->start();
            $this->progressIndicators->set($ids, $progressBar);

            foreach ($state->getSubStates() as $key => $subStates) {
                $this->output->section()->writeln(str_repeat("    ", $level + 1) . "# Branch No° $key :");

                $ids = $parentIds;
                $ids[] = $id;
                $ids[] = $key;
                $this->initProgressIndicators($subStates, $ids, $level + 1);
            }
        }
    }

    private function getMessage($id, OperationState $operationState, int $level)
    {
        $pad = str_repeat("    ", $level);
        if ($level > 0) {
            $pad .= "- ";
        }

        $state = $operationState->getState()->label();
        $name = $operationState->getOperationName();
        $processed = $operationState->getItemsProcessed();
        $returned = $operationState->getItemsReturned();
        $asyncWaiting = $operationState->getAsyncWaiting();
        $timeSpent = $this->formatTimeSpent($operationState->getTimeSpent());
        return "$pad$state - $name ($processed IN / $asyncWaiting ASYNC / $returned OUT) $timeSpent";
    }

    private function formatTimeSpent(int $time): string
    {
        $time = abs($time);
        $cent = str_pad((string) ($time % 1000), 3, '0', STR_PAD_LEFT);
        $time = floor($time / 1000);
        $sec = str_pad((string) ($time % 60), 2, '0', STR_PAD_LEFT);
        $min = str_pad((string) floor($time / 60), 2, '0', STR_PAD_LEFT);
        $hour = str_pad((string) floor($time / 60 / 60), 1, '0');

        $textTime = $min.':'.$sec;
        if (floor($time / 60 / 60) > 0) {
            $textTime = $hour."'".$textTime;
        }

        $textTime = $textTime.'.'.$cent;

        return $textTime;
    }
}
