---
layout: base
title: PHP-ETL - Operations
subTitle: Extract - JSON File(json-read)
---

Allow us to read a json file from within the context.

The operation receives a `DataItem` that contains the path to the csv file to read. It will return a list DataItem's.
Should e used after a `external file processor` operation.

#### Options

- **fileKey** If the DataItem received is an array, the key in which the path to the csv file can be found. "/" can be used to read sub arrays. Example `key/subkey1`.

#### Examples

🚧 TODO 🚧