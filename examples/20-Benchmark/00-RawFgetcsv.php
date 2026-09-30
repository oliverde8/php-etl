<?php

$startTime = microtime(true);

$in = fopen(__DIR__ . '/data/bench.csv', 'r');
$out = fopen(__DIR__ . '/data/output-raw.csv', 'w');

$headers = fgetcsv($in, 0, ';', '"', '\\');
fputcsv($out, $headers, ';', '"', '\\');

$read = 0;
$written = 0;
while ($line = fgetcsv($in, 0, ';', '"', '\\')) {
    $read++;
    $row = array_combine($headers, $line);
    if ($row['keep'] === '1') {
        fputcsv($out, $row, ';', '"', '\\');
        $written++;
    }
}

fclose($in);
fclose($out);

printf("Read: %d, written: %d\n", $read, $written);
printf("\nTotal: %.2fs, peak memory: %.1f MB\n", microtime(true) - $startTime, memory_get_peak_usage(true) / 1024 / 1024);
