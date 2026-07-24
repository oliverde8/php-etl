<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Output;

use Oliverde8\Component\PhpEtl\ChainOperation\SubChainsAwareOperationInterface;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;

class MermaidStaticOutput
{
    public function generateGrapText(ChainProcessorInterface $chainProcessor)
    {
        $text = "flowchart TD\n";

        $text .= "%% Nodes\n";
        $text .= $this->generateNodes([$chainProcessor]);

        $text .= "%% Links\n";
        $text .= $this->generateLinks([$chainProcessor]);

        return $text;
    }

    public function generateUrl(ChainProcessorInterface $chainProcessor)
    {
        $text = $this->generateGrapText($chainProcessor);
        $json = \json_encode([
            'code'    => (string)$text,
            'mermaid' => [
                'theme' => 'default',
            ],
        ]);

        return 'https://mermaid-js.github.io/mermaid-live-editor/#/edit/' . base64_encode($json);
    }

    /**
     * @param ChainProcessorInterface[] $chainProcessors
     */
    protected function generateNodes(array $chainProcessors, string $prefix = ""): string
    {
        $text = '';
        foreach ($chainProcessors as $processorId => $chainProcessor) {
            $newPrefix = $prefix . $processorId;

            $chainLinkNames = $chainProcessor->getChainLinkNames();
            foreach ($chainProcessor->getChainLinks() as $id => $chainLink) {
                if ($chainLink instanceof SubChainsAwareOperationInterface) {
                    $text .= "\t" . $newPrefix . $id . "B(" . $chainLinkNames[$id] . ")@{ shape: hex}\n";
                    $text .= $this->generateNodes($chainLink->getChainProcessors(), $newPrefix . $id);
                } else {
                    $text .= "\t" . $newPrefix . $id . "B(" . $chainLinkNames[$id] . ")\n";
                }
            }
        }

        return $text;
    }

    /**
     * @param ChainProcessorInterface[] $chainProcessors
     */
    protected function generateLinks(array $chainProcessors, string $prefix = "", ?string $previous = null): string
    {
        $text = '';
        $originalPrevious = $previous;
        foreach ($chainProcessors as $processorId => $chainProcessor) {
            $newPrefix = $prefix . $processorId;
            $previous = $originalPrevious;

            foreach ($chainProcessor->getChainLinks() as $id => $chainLink) {
                if ($previous) {
                    $text .= "\t$previous" . "B-->" . $newPrefix . $id . "B\n";
                }
                $previous = $newPrefix . $id;

                if ($chainLink instanceof SubChainsAwareOperationInterface) {
                    $text .= $this->generateLinks($chainLink->getChainProcessors(), $newPrefix . $id, $previous);
                }
            }
        }

        return $text;
    }
}
