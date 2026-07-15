---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: Flavor
width: large
---

## Flavor

Some operations need more than one instance — e.g. an `ExternalFileFinderConfig` per remote storage. `flavor` is
the string that tells `GenericChainFactory` which registration to use when several share the same config class.

Every operation config has a `flavor`, defaulting to `'default'`. You only need to set it when you register more
than one factory for the same config class.

```php
// Two file finders, one per storage.
$chainBuilder = new ChainBuilderV2($executionContextFactory, [
    new GenericChainFactory(
        ExternalFileFinderOperation::class,
        ExternalFileFinderConfig::class,
        flavor: 'sftp',
        injections: ['fileSystem' => $sftpFileSystem]
    ),
    new GenericChainFactory(
        ExternalFileFinderOperation::class,
        ExternalFileFinderConfig::class,
        flavor: 's3',
        injections: ['fileSystem' => $s3FileSystem]
    ),
]);

// Pick which one to use in the config:
$chainConfig->addLink(new ExternalFileFinderConfig(directory: '/incoming', flavor: 'sftp'));
```

The Symfony bundle's Flysystem integration uses this to auto-register one factory per configured storage, with
flavors named `flysystem.{storage_name}` — see [File Finder](/doc/20-operations/20-extract/010-file-finder.html)
for that example.
