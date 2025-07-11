---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Split Item(item-split)
---

Allow us to split a single DataItem into multiple data items. For example we receive a list of customers and their
addresses and would like to process all addresses.

## Options

**keys:** The list of keys to read from the inputed Data, a new DataItem is returned for each key.
**singleElement:** if true reads only the first key and returns a DataItem for each item in that array.
**keepKeys:** If true will return a data containing key & value to keep the original keys during split.
**keyName:** If not null will move the data in a sub array before returning it. (Only works when data is of type array)
**duplicateKeys:** can also be name "mergeKeys" will extract keys from the main array and add them to each individual DataItem. (Only works when data is of type array)

## Example Use

🚧 TODO 🚧
