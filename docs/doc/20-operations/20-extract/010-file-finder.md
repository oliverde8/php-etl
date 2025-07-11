---
layout: base
title: PHP-ETL - Operations
subTitle: Extract- File Finder
---

The `ExternalFileFinder` operation is the base operation for importing files from **remote or external file systems**. 
It is responsible for **locating files** based on a given pattern and returning them as `ExternalFileItem`s for further processing.

This operation works with any file system supported by [Flysystem](https://flysystem.thephpleague.com/), including **SFTP, local files, AWS S3**, and more.

---

## How It Works

The `ExternalFileFinder` searches a directory for files matching a provided pattern. For each file found, 
it returns an `ExternalFileItem`. These items are typically passed down the chain to:

- **Copy the file locally** using a dedicated operation.
- **Read/process the file** content using format-specific operations (e.g., `csv-read`, `xml-read`, etc.).

> 📘 Refer to the [Cookbook section](/doc/10-examples/510-import-file.html) for complete examples of end-to-end remote file import flows.

---

### Registering the Operation

Because multiple instances of this operation may be needed (e.g., different connections or directories), the 
`ExternalFileFinder` must be **manually registered** using a factory.

Here is how you would register an instance using a local file system:

{% capture column1 %}
#### 🐘 Standalone

```php
$builder->registerFactory(
    new ExternalFileFinderFactory(
        'external-file-finder-local', 
        ExternalFileFinderOperation::class, 
        new LocalFileSystem("/") // or any Flysystem-compatible adapter
    )
);
```

You can use any Flysystem adapter (e.g., SFTP, AWS S3, Azure Blob Storage, etc.) when creating the factory.
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony

In a Symfony application, you should register the operation via Dependency Injection, defining it as a service and tagging it accordingly.

```yaml
services:
  app.etl.operation.external_file_finder.my-local:
    class: Oliverde8\Component\PhpEtl\Builder\Factories\Extract\ExternalFileFinderLocal
    autowire: true
    arguments:
      $operation: 'my_custom_finder'
      $class: 'Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation'
      $filesystem: '@your.flysystem.adapter.service'
    tags:
      - { name: etl.operation-factory }
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}