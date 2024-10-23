<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Output;

use Oliverde8\Component\PhpEtl\Model\State\OperationState;

class MermaidRunOutput
{
    public function generateGrapText(array|string|null $operationStates): string
    {
        if (is_null($operationStates)) {
            return "";
        }
        if (is_string($operationStates)) {
            $operationStates = json_decode($operationStates, true);
        }

        $text = "flowchart TD\n";

        $text .= "\n\n%% Legend\n";
        $text .= "subgraph Legend\n";
        $text .= "\t LNI(Not Initialized) ~~~ LW\n";
        $text .= "\t LW(Waiting) ~~~ LA\n";
        $text .= "\t LA(Asnyc Executution) ~~~ LR\n";
        $text .= "\t LR(Running) ~~~ LSP\n";
        $text .= "\t LSP(Stopping) ~~~ LS(Stopped)\n";
        $text .= "\t style LNI fill:#FFF\n";
        $text .= "\t style LW fill:#EEE\n";
        $text .= "\t style LA fill:#ffe294\n";
        $text .= "\t style LR fill:#8099ff\n";
        $text .= "\t style LSP fill:#c6ffb5\n";
        $text .= "\t style LS fill:#c6ffb5\n";
        $text .= "end\n";

        $text .= "subgraph Execution\n";
        $text .= "%% Nodes\n";
        $text .= $this->generateNodes($operationStates);

        $text .= "%% Links\n";
        $text .= $this->generateLinks($operationStates);
        $text .= "end\n";


        return $text;
    }

    protected function generateNodes(array $operationStates, string $prefix = ""): string
    {
        $text = '';
        foreach ($operationStates as $id => $state) {
            $newId = $prefix . $id;

            if ($state['subStates']) {
                $text .= "\t" . $newId . "B(" . $this->generateName($state) . ")@{ shape: hex}\n";
                foreach ($state['subStates'] as $branchId => $subState) {
                    $text .= $this->generateNodes($subState, $newId . $branchId);
                }
            } else {
                $text .= "\t" . $newId . "B(" . $this->generateName($state) . ")\n";
            }

            if ($state['state'] == "Not Initialized") {
                $text .= "style {$newId}B fill:#FFF\n";
            } elseif ($state['state'] == "Waiting") {
                $text .= "style {$newId}B fill:#EEE;\n";
            } elseif ($state['state'] == "Async") {
                $text .= "style {$newId}B fill:#ffe294;\n";
            } elseif ($state['state'] == "Running") {
                $text .= "style {$newId}B fill:#8099ff;\n";
            } elseif ($state['state'] == "Stopping") {
                $text .= "style {$newId}B fill:#c6ffb5;\n";
            } elseif ($state['state'] == "Stopped") {
                $text .= "style {$newId}B fill:#c6ffb5;\n";
            }
        }

        return $text;
    }

    protected function generateLinks(array $operationStates, string $prefix = "", $previousId = null): string
    {
        $text = "";
        foreach ($operationStates as $id => $state) {
            /** @var OperationState $state */
            $newId = $prefix . $id;
            if (!is_null($previousId)) {
                $text .= "$previousId --> {$newId}B\n";
            }
            $previousId = $newId . "B";

            if ($state['subStates']) {
                foreach ($state['subStates'] as $branchId => $subState) {
                    $text .= $this->generateLinks($subState, $prefix . $newId . $branchId, $previousId);
                }
            }
        }

        return $text;
    }

    protected function generateName(array $state): string
    {
        $name = $state['operationName'];
        $name .= "<br/><br/>";
        $name .= $state['itemsProcessed'] . '<i class="sign in alternate icon"></i> / ';
        if (isset($state['asynInProgres']) && $state['asynInProgres'] > 0) {
            $name .= $state['asynInProgres']  . '<i class="clock icon"></i> / ';
        }
        $name .= $state['itemsReturned']  . '<i class="sign out alternate icon"></i><br/>';
        $name .= $this->formatTimeSpent($state['timeSpent']) . '<i class="hourglass half icon"></i>';

        return $name;
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
