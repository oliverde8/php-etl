---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Merge(merge)
---

Allows us to execute multiple chain operations as with the ChainSplitOperation but at the end the items returned
from each branch is returned to the next steps.

**⚠** If branches don't filter or transform items then steps after the ChainMerge will receive the same items multiple
times. There is no detection of duplicate data. This means ChainMergeOperation can actually be also used to split data
using more complex rules. If for example a single line of a csv file contains both information on the configurable
product and the single product.

## Options

**branches:** A list of etl chains

## Example Use

🚧 TODO 🚧
