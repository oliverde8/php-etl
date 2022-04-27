<?php

require_once __DIR__ . "/vendor/autoload.php";

use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

$simpleEtl = new \Oliverde8\Component\PhpEtl\SimpleEtl();
$simpleEtl
    ->dataOfType('csv')
    ->to("local", ['filepath' => "test.csv"])
    ->treatWith(function (array $item, ExecutionContext $context) {
        return [
            ['col1' => 'data1-1', 'col2' => 'data1-2'],
            ['col1' => 'data2-1', 'col2' => 'data2-2'],
        ];
    })
    ->go();
