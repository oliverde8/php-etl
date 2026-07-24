---
layout: base
title: PHP-ETL - Changelog
subTitle: Release history
---

# 2.1.0

- 🌟 - Allow naming chain links when using new chain builder
- 🌟 - `ChainSplitConfig`, `ChainMergeConfig`, `ChainRepeatConfig`, and `FailSafeConfig` now accept an `isolateContext` option to give a sub-chain its own copy of the execution context instead of sharing the parent's
- 🌟 - Added `BatchConfig` operation to collect items into fixed-size chunks, emitted as soon as each chunk is full, without buffering the whole stream in memory
- 🌟 - Added `IfConfig` operation to route an item to exactly one of two sub-chains based on a Rule Engine condition, letting the chosen branch freely modify the item (unlike `Split`, where multiple branches could run in parallel)
- 🔧 - Fixed Mermaid static diagram not rendering a Merge operation's branches (they were silently skipped, unlike Split's)

# 🌟 2.0.0 🌟

- 🌟 - **NEW PARADIGM** - use php for configurations instead of yaml
- 🌟 - Complete rewrite of the ChainProcessor
- 🌟 - Optimized chain processor not to send as many stop items when multiple chains are involved
- 🌟 - Allows ChainProcessor to output through generators the items at the end. This is great to remove all limitations of the current sub chains.
- 🌟 - ChainRepeatOperation now can handle more complex cases & asynchronous tasks. The operation is not considered as experimental anymore.
- 🌟 - Added FailSafe operation allowing to execute sub-chains and catching exception in them.
- 🌟 - `ChainConfig::addLink()` now accepts an optional name for the link, used in place of its numeric position in generated Mermaid diagrams, logs, and exceptions.

- ❗ **Deprecation** The chainProcessorInterface was changed significantly.
  - This means any complex custom operations using the chainProcessor needs to be redone. => This shouldn't affect any load/extract or transform operation.
  - This means any integrations using the ETL also needs to be updated.
  - For context, this change was not done lightly, the ETL was initially developed using php5.5. The php language
    has evolved and some complex cases the ETL didn't handle well could be easily fixed by using the new features of
    the language (mostly using Generators).
- ❗ **Deprecation** Support for symfony 4 and 5 was dropped.
- ❗ **Deprecation** The yaml/array configuration (`ChainBuilder`) is deprecated in favor of the new php configuration (`ChainBuilderV2`), but remains fully supported.

# 1.2.1
- 🔧 Fix - Temporary files not being deleted when using file load operations.

# 1.2.0
- 🌟 Feature #14 - Added possibility to create subchains.
- 🌟 Feature #34 - Allow chain's to be observed to see progress.
- 🌟 Feature #35 - Added a symfony console output to display nice output based on the chain's progress.
- 🌟 Feature #42 - Added output for generating mermaid graph for a etl chain definition & for running chain.
- 🌟 Feature #45 - Added a log operation. 
- 🌟 Feature #45 - Added a repeat operation allowing part of a chain to be repeated (Experimental 🧪)
- 🔧 Fix - Boolean false values being converted to string by the rule engine.
- 🔧 Fix - Http client operation not handling empty response properly when response is json.
- 🔧 Fix - [Core]Issues with asynchronous items & split chains
- 🔧 Fix - Writing empty json files causes error

# 1.1.4
- 🌟 Split item operation can now return more complex data sets using the `keyName` option
- 🌟 Split item operation can now keep/duplicate data before splitting it using the `duplicateKeys` option.
- 🌟 Split item operation can now keep the original keys while splitting it using the `keepKeys` option.

# 1.1.3
- 🔧 Fix - Added support for symfony 7

# 1.1.2
- 🔧 Fix - Usage of the AbstractFactory for operation without overriding configureValidator causing errors.

# 1.1.1
- ❗ **Deprecation - Dropped support for php 7.4 and 8.0.** Both are in end of life.
- 🔧 Fix - Deprecation issues with the Csv extractor.
- 🔧 Fix - Add missing tests to Csv extractor. 
- 🔧 Fix - Unit test not running for ChainSplitOperation.

# 1.1.0
- 🌟 Docs & Docs & Docs, lots of new & better documentation
- 🌟 Feature #4 - Added Support to the ChainBuilder for the `Split` operation
- 🌟 Feature #4 - new `Merge` operation that works like split but merges back the result of each branch for nex operations.
- 🌟 Feature #9 - Added ne Filter operation to filter data.
- 🌟 Feature #17 - Add new complex item MixItem. This allows operations to return more than one type of item.
- 🌟 Feature #18 - Add new Item FileExtractedItem. Operations extracting data from files should send this item once all the data has been extracted.
- 🌟 Feature #19 - Add new Item FileLoadedItem. Operations loading data into files should send this item once all the data has been written.
- 🌟 Feature #4 - Add new split item operation
- 🌟 Feature #11 - Add a Http client operation using symfony http client.
- 🌟 Feature #24 - Added support for Asynchronous Items. The Processor will then wait for the item to finish before transferring it to the next operation.

# 1.0.3
- 🔧 Fix - Stop Item is sent multiple times when using a step returns multiple GroupedItem's
- 🌟 Added support for psr/log 2 & 3. (no code needed)

# 1.0.2
- 🔧 Fix - Context not being finalised when there is an exception.

# 1.0.1 
- 🔧 Fix - File writers not using the file abstraction.

# 1.0.0
- 🎊 🎉 First stable release 🎉 🎊
- 🌟 Added support for symfony 6
- 🌟 Added better handling of 'execution' files to use as input files or as output files. (See Symfony Bundle)
- 🌟 Improve method resolution for chain operations to use types instead of arbitrary names. processData can be names anything now as long as it typed properly
- 🔧 Improve the global quality of the code by having typed methods.

- ❗ This will break any of your existing chain declarations: 
 - If you are using the symfony bundle only your custom operations will break (not factories). The context is now an object. 
 - If not the definition of chains remains un changed but both the `ChainBuilder` and `ChainProcessor` has changed.

# 0.4.0
- 🌟 Added support for symfony 5

# 0.3.0
- 🌟 Added support for symfony 4

# 0.2.0 

- 🌟 Added a chainBuilder this allow building chanins from descriptions (such as yaml files)
- 🌟 Added the [Symfony Expression Langauge](https://symfony.com/doc/3.4/components/expression_language.html) to the RuleEngine
- ❗ The condition rule was deprecated in favor of the Symfony Expression Language.

# 0.1.0

- 🌟 Added possibility to have nested columns when using the RuleTransformer
- 🌟 Added possibility to have dynamic columns when using the RuleTransformer

# 0.0.3

- 🔧 Added unit test on important & complex components.
- 🌟 Added a crude error support, allows to have understood in which ETL operation the error happened. (Hopefully)

# v0.0.2

- 🌟 Added support for php 5.6 & 7.0

# v0.0.1
- 🎊 🎉 First release 🎉 🎊
