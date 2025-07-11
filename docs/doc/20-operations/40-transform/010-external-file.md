---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - External File
---

The `external-file-processor` operation is designed to **move and register external files** 
(e.g., from SFTP, local FS, cloud storage) into the ETL execution context. 
This operation works hand-in-hand with the `external-file-finder` and is essential for enabling
further processing of remote files.

## Purpose

When `external-file-finder` locates a remote file, it returns an `ExternalFileItem`. 
However, that file is not yet part of the ETL's working context.

The `external-file-processor` operation:

- **Copies the external file into the ETL context**, making it accessible to extract operations like `csv-read`, `xml-read`, etc.
- **Archives the file** within the ETL execution history, so it can be tracked and audited later.
- **Returns a `DataItem`** containing the path of the new local file, making it usable by downstream operations.

## File Lifecycle & Behavior

The operation follows a structured file management flow across multiple runs:

1. **Initial Processing**:
  - The file is moved from its source directory into a `processing/` subdirectory (within the external filesystem).
  - It is also copied to the local ETL context (temporary working directory).
  - A `DataItem` is emitted with the new local file path.

2. **Post-Processing**:
  - If the operation is used a second time in the same chain (e.g., near the end of the flow), it will:
    - Move the remote file from `processing/` to `processed/`, effectively archiving it.
    - This signals the file has been fully and successfully handled.

> 💡 **Best Practice**:  
> Use `external-file-processor` **twice** in a chain:
> - Once immediately after the `external-file-finder`.
> - Once at the end of the chain, to archive the file remotely after successful processing.


## Filesystem Agnostic

The operation does **not require manual configuration of the filesystem**. 
It uses the File system instance already embedded in the `ExternalFileItem` provided by the `externalFileFinder`.
