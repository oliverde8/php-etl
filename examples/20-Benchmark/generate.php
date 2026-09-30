<?php

$rows = (int) ($argv[1] ?? 1_000_000);
$nbColumns = 30;
$dir = __DIR__ . '/data';
if (!is_dir($dir)) {
    mkdir($dir);
}

$headers = ['id', 'keep'];
for ($i = 1; $i <= $nbColumns - 2; $i++) {
    $headers[] = "column$i";
}

$csv = fopen("$dir/bench.csv", 'w');
$gz = gzopen("$dir/bench.csv.gz", 'w6');
$tmp = fopen('php://memory', 'w+');

$write = function (array $line) use ($csv, $gz, $tmp): void {
    fputcsv($csv, $line, ';', '"', '\\');
    ftruncate($tmp, 0);
    rewind($tmp);
    fputcsv($tmp, $line, ';', '"', '\\');
    rewind($tmp);
    gzwrite($gz, stream_get_contents($tmp));
};

$write($headers);
for ($row = 1; $row <= $rows; $row++) {
    $line = [$row, $row % 20 === 0 ? 1 : 0];
    for ($i = 1; $i <= $nbColumns - 2; $i++) {
        $line[] = "value-$row-$i";
    }
    $write($line);
}

fclose($csv);
gzclose($gz);

echo "Generated $rows rows in $dir\n";
