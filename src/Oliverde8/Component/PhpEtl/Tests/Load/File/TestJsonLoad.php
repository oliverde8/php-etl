<?php
declare(strict_types=1);

namespace Load\File;

use Oliverde8\Component\PhpEtl\Load\File\Json;
use PHPUnit\Framework\TestCase;

class TestJsonLoad extends TestCase
{
    public function testSimpleArrayWrite()
    {
        $filePath = $this->getTestFile();

        $jsonFile = new Json($filePath);
        $jsonFile->write(['myKey' => 'MyValue']);
        fclose($jsonFile->getResource());

        $jsonContent = file_get_contents($filePath);
        $this->assertEquals('{"myKey":"MyValue"}', trim($jsonContent));
    }

    public function testEmptyWrite()
    {
        $filePath = $this->getTestFile();

        $jsonFile = new Json($filePath);

        fclose($jsonFile->getResource());
        $jsonContent = file_get_contents($filePath);
        $this->assertEquals('', trim($jsonContent));
    }

    protected function getTestFile(): string
    {
        $filePath = __DIR__ . "/test.csv";
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $filePath;
    }
}