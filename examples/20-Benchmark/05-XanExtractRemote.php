<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

require_once __DIR__ . '/../../vendor/autoload.php';

class RemoteLikeFileSystem extends LocalFileSystem
{
    #[\Override]
    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'r+');
        stream_copy_to_stream(parent::readStream($path), $stream);
        rewind($stream);

        return $stream;
    }
}

function getEtlExecutionContextFactory() {
    return new class implements ExecutionContextFactoryInterface {
        public function get(array $parameters): ExecutionContext
        {
            return new ExecutionContext($parameters, new RemoteLikeFileSystem());
        }
    };
}

require_once __DIR__ . '/.init.php';
/** @var ChainBuilderV2 $chainBuilder */
/** @var callable $observer */

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CommandCsvExtractConfig([
        ['gzip', '-dc'],
        ['xan', 'filter', '-d', ';', 'keep == 1'],
    ]), 'extract')
    ->addLink(new CsvFileWriterConfig('data/output-xan-remote.csv'), 'write');

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(new DataItem(['file' => 'data/bench.csv.gz']), [], $observer);
