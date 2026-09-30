<?php
declare(strict_types=1);

namespace Extract\File;

use Oliverde8\Component\PhpEtl\Extract\File\Csv;
use PHPUnit\Framework\TestCase;

class TestCsvExtract extends TestCase
{
    public function testSimpleCsvRead()
    {
        $filePath = __DIR__ . "/test.csv";
        $csvFile = new Csv($filePath, ',');

        $this->assertEquals(
            ["column1","column-2","column 3","column;4"],
            $csvFile->getHeaders(),
            "Expecting headers to be read correctly."
        );

        $increment = 1;
        foreach ($csvFile as $line) {
            $this->assertEquals(
                ["column1","column-2","column 3","column;4"],
                array_keys($line),
                "Expect reader to return data in each line with the correct key based on the header."
            );

            $this->assertEquals(
                ["value1-$increment","value2-$increment","value3-$increment","value4-$increment"],
                array_values($line),
                "Expect reader to return data in each line with the correct key based on the header."
            );
            $increment++;
        }

        $this->assertEquals(3, $increment-1, "Expecting iterator to read all data lines.");
    }

    public function testProjectedCsvRead()
    {
        $csvFile = new Csv(__DIR__ . "/test.csv", ',', columns: ["column 3", "column1"]);

        $lines = iterator_to_array($csvFile, false);

        $this->assertCount(3, $lines);
        $this->assertSame(["column 3" => "value3-1", "column1" => "value1-1"], $lines[0]);
        $this->assertSame(["column 3" => "value3-3", "column1" => "value1-3"], $lines[2]);
        $this->assertEquals(["column1","column-2","column 3","column;4"], $csvFile->getHeaders());
    }

    public function testProjectedCsvReadWithMissingColumn()
    {
        $csvFile = new Csv(__DIR__ . "/test.csv", ',', columns: ["column1", "unknown"]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("unknown");

        $csvFile->getHeaders();
    }
}
