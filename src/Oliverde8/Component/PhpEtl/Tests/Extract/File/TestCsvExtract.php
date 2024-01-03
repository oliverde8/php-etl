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
}