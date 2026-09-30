<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CommandCsvExtractOperation;
use Oliverde8\Component\PhpEtl\Exception\CommandException;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;
use PHPUnit\Framework\TestCase;

class CommandCsvExtractOperationTest extends TestCase
{
    private const string COPY_STDIN = 'stream_copy_to_stream(STDIN, STDOUT);';

    private string $dir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/etl_command_test_' . uniqid();
        mkdir($this->dir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    public function testLocalFileIsPassedAsPath()
    {
        file_put_contents($this->dir . '/input.csv', "name,age\nJohn,30\n");

        $rows = $this->extract(
            new CommandCsvExtractConfig([PHP_BINARY, '-r', 'echo "path\n", $argv[1], "\n";', '{file}']),
            new LocalFileSystem($this->dir),
            'input.csv'
        );

        $this->assertSame([['path' => realpath($this->dir . '/input.csv')]], $rows);
    }

    public function testLocalFileWithoutPlaceholderIsPassedAsStdin()
    {
        file_put_contents($this->dir . '/input.csv', "name,age\nJohn,30\n");

        $rows = $this->extract(new CommandCsvExtractConfig([PHP_BINARY, '-r', self::COPY_STDIN]), new LocalFileSystem($this->dir), 'input.csv');

        $this->assertSame([['name' => 'John', 'age' => '30']], $rows);
    }

    public function testRemoteStreamIsPumpedToStdin()
    {
        $rows = $this->extract(
            new CommandCsvExtractConfig([PHP_BINARY, '-r', 'echo "path,content\n", $argv[1], ",", trim(stream_get_contents(STDIN)), "\n";', '{file}']),
            $this->remoteFileSystem("remote-data"),
            'input.csv'
        );

        $this->assertSame([['path' => '-', 'content' => 'remote-data']], $rows);
    }

    public function testLargeRemoteStreamDoesNotDeadlock()
    {
        $content = "id,value\n";
        for ($i = 0; $i < 100000; $i++) {
            $content .= "$i,value-$i\n";
        }

        $rows = $this->extract(new CommandCsvExtractConfig([PHP_BINARY, '-r', self::COPY_STDIN]), $this->remoteFileSystem($content), 'input.csv');

        $this->assertCount(100000, $rows);
        $this->assertSame(['id' => '99999', 'value' => 'value-99999'], $rows[99999]);
    }

    public function testCommandsArePiped()
    {
        $rows = $this->extract(
            new CommandCsvExtractConfig([
                [PHP_BINARY, '-r', self::COPY_STDIN],
                [PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));'],
            ]),
            $this->remoteFileSystem("name,age\njohn,30\n"),
            'input.csv'
        );

        $this->assertSame([['NAME' => 'JOHN', 'AGE' => '30']], $rows);
    }

    public function testGzipIsDecompressedByCommand()
    {
        file_put_contents($this->dir . '/input.csv.gz', gzencode("name,age\nJohn,30\n"));

        $rows = $this->extract(
            new CommandCsvExtractConfig([PHP_BINARY, '-r', 'readfile("compress.zlib://" . $argv[1]);', '{file}']),
            new LocalFileSystem($this->dir),
            'input.csv.gz'
        );

        $this->assertSame([['name' => 'John', 'age' => '30']], $rows);
    }

    public function testColumnsAreProjected()
    {
        $rows = $this->extract(
            new CommandCsvExtractConfig([PHP_BINARY, '-r', self::COPY_STDIN], columns: ['age']),
            $this->remoteFileSystem("name,age\nJohn,30\n"),
            'input.csv'
        );

        $this->assertSame([['age' => '30']], $rows);
    }

    public function testFailingCommandThrowsWithStderr()
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('boom');

        $this->extract(
            new CommandCsvExtractConfig([PHP_BINARY, '-r', 'echo "name\nJohn\n"; fwrite(STDERR, "boom"); exit(3);']),
            $this->remoteFileSystem("name\n"),
            'input.csv'
        );
    }

    public function testIdleCommandTimesOut()
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('no activity');

        $start = microtime(true);
        try {
            $this->extract(
                new CommandCsvExtractConfig([PHP_BINARY, '-r', 'sleep(10);'], idleTimeout: 0.2),
                $this->remoteFileSystem(''),
                'input.csv'
            );
        } finally {
            $this->assertLessThan(5, microtime(true) - $start);
        }
    }

    public function testInputStreamTimeoutThrows()
    {
        [$input, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        stream_set_timeout($input, 0, 100000);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn($input);
        $fileSystem->method('getRootPath')->willReturn('/');

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Timed out while reading the input stream');

        $this->extract(new CommandCsvExtractConfig([PHP_BINARY, '-r', self::COPY_STDIN]), $fileSystem, 'input.csv');
    }

    public function testInvalidIdleTimeoutIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CommandCsvExtractConfig(['xan'], idleTimeout: 0);
    }

    public function testEarlyStopTerminatesCommand()
    {
        $operation = new CommandCsvExtractOperation(new CommandCsvExtractConfig([PHP_BINARY, '-r', 'echo "id\n"; while (true) { echo "1\n"; }']));
        $result = $operation->process(new DataItem('input.csv'), new ExecutionContext([], $this->remoteFileSystem("id\n")));

        $iterator = $result->getItems()[0]->getIterator();
        $iterator->rewind();
        $this->assertSame(['id' => '1'], $iterator->current());

        unset($iterator, $result);
        $this->addToAssertionCount(1);
    }

    public function testResultItems()
    {
        $operation = new CommandCsvExtractOperation(new CommandCsvExtractConfig([PHP_BINARY, '-r', self::COPY_STDIN]));
        $result = $operation->process(new DataItem(['file' => 'input.csv']), new ExecutionContext([], $this->remoteFileSystem("id\n")));

        $items = $result->getItems();
        $this->assertInstanceOf(GroupedItem::class, $items[0]);
        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);
        $this->assertSame('input.csv', $items[1]->getFilePath());
        iterator_to_array($items[0]->getIterator());
    }

    public function testCommandIsNormalizedToPipeline()
    {
        $this->assertSame([['xan', 'filter']], (new CommandCsvExtractConfig(['xan', 'filter']))->commands);
        $this->assertSame([['a'], ['b']], (new CommandCsvExtractConfig([['a'], ['b']]))->commands);
    }

    public function testEmptyCommandIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CommandCsvExtractConfig([]);
    }

    public function testInvalidCommandIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CommandCsvExtractConfig([['xan'], []]);
    }

    public function testGetConfigurationClass()
    {
        $operation = new CommandCsvExtractOperation(new CommandCsvExtractConfig(['xan']));

        $this->assertSame(CommandCsvExtractConfig::class, $operation->getConfigurationClass());
    }

    private function extract(CommandCsvExtractConfig $config, FileSystemInterface $fileSystem, string $file): array
    {
        $operation = new CommandCsvExtractOperation($config);
        $result = $operation->process(new DataItem($file), new ExecutionContext([], $fileSystem));

        return iterator_to_array($result->getItems()[0]->getIterator(), false);
    }

    private function remoteFileSystem(string $content): FileSystemInterface
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn($stream);
        $fileSystem->method('getRootPath')->willReturn('/');

        return $fileSystem;
    }
}
