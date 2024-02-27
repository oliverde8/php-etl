<?php

namespace Oliverde8\Component\PhpEtl\Output;

use Oliverde8\Component\PhpEtl\Model\State\OperationState;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SymfonyConsoleOutput
{
    protected readonly OutputInterface $output;

    /** @var ProgressBar[] */
    protected array $progressIndicators = [];

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function output(array $operationStates)
    {
        if (empty($this->progressIndicators)) {
            $this->initProgressIndicators($operationStates);
        }

        foreach ($operationStates as $id => $state) {
            $this->progressIndicators[$id]->setMessage($this->getMessage($id, $state));
            $this->progressIndicators[$id]->advance();
        }
    }

    private function initProgressIndicators(array $operationStates)
    {
        ProgressBar::setFormatDefinition('custom', '-  %message%');

        foreach ($operationStates as $id => $state) {
            $section = $this->output->section();
            $this->progressIndicators[$id] = new ProgressBar($section, 0, 0);
            $this->progressIndicators[$id]->setMessage($this->getMessage($id, $state));
            $this->progressIndicators[$id]->setFormat("custom");
            $this->progressIndicators[$id]->start();
        }
    }

    private function getMessage($id, OperationState $operationState)
    {
        $state = $operationState->getState()->label();
        $name = $operationState->getOperationName();
        $processed = $operationState->getItemsProcessed();
        $returned = $operationState->getItemsReturned();
        $asyncWaiting = $operationState->getAsyncWaiting();
        return "$state - $name ($processed IN / $asyncWaiting ASYNC / $returned OUT)";
    }
}
