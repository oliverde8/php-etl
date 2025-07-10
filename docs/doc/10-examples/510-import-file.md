---
layout: base
title: PHP-ETL - Cook Books
subTitle: With Context - Import External File
width: large
---

### With Context - Import External File

The ETL can be used to fetch files from another filesystem and process them using the ETL. Files will be moved into
`processing` and `processed` directories as the ETL runs.



{% capture description %}
We will use the `ExternalFileFinderOperation` to find the files. And use the `ExternalFileProcessorOperation` to copy
the files to the context of the ETL execution.

The file finder will use a directory that we will add to the context at the beginning of the execution.

We will also need to provide the finder with a regex as chain input so that it can find all the files.
{% endcapture %}
{% capture code %}
```yaml
  find-file1:
    operation: external-file-finder-local
    options:
      directory: "@context['dir']"

  process-new-file1:
    operation: external-file-processor
    options: []

```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture column1 %}
#### 🐘 Standalone
```php
$options = [
    // ...
    'dir' => $dir,
];

$chainProcessor->process(
    new ArrayIterator(['/^file[0-9]\.csv$/']),
    $options
);
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony
```sh
./bin/console etl:execute myetl "['/^file[0-9]\.csv$/']" "{'dir': '/var/import'}"
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

#### Complete Code

```yaml
chain:
  find-file1:
    operation: external-file-finder-local
    options:
      directory: "@context['dir']"

  process-new-file1:
    operation: external-file-processor
    options: []

  read-file:
    operation: csv-read
    options: [] 

  write-new-file:
    operation: csv-write
    options:
      file: "output.csv"

  process-finalized-file1:
    operation: external-file-processor
    options: []
```
