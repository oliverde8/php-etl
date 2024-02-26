# 1.2.0
- :star2: Feature #14 - Added possibility to create subchains.

# 1.1.2
- :wrench: Fix - Usage of the AbstractFactory for operation without overriding configureValidator causing errors.

# 1.1.1
- -:exclamation: **Deprecation - Dropped support for php 7.4 and 8.0.** Both are in end of life.
- :wrench: Fix - Deprecation issues with the Csv extractor.
- :wrench: Fix - Add missing tests to Csv extractor. 
- :wrench: Fix - Unit test not running for ChainSplitOperation.

# 1.1.0
- :star2: Docs & Docs & Docs, lots of new & better documentation
- :star2: Feature #4 - Added Support to the ChainBuilder for the `Split` operation
- :star2: Feature #4 - new `Merge` operation that works like split but merges back the result of each branch for nex operations.
- :star2: Feature #9 - Added ne Filter operation to filter data.
- :star2: Feature #17 - Add new complex item MixItem. This allows operations to return more than one type of item.
- :star2: Feature #18 - Add new Item FileExtractedItem. Operations extracting data from files should send this item once all the data has been extracted.
- :star2: Feature #19 - Add new Item FileLoadedItem. Operations loading data into files should send this item once all the data has been written.
- :star2: Feature #4 - Add new split item operation
- :star2: Feature #11 - Add a Http client operation using symfony http client.
- :star2: Feature #24 - Added support for Asynchronous Items. The Processor will then wait for the item to finish before transferring it to the next operation.

# 1.0.3
- :wrench: Fix - Stop Item is sent multiple times when using a step returns multiple GroupedItem's
- :star2: Added support for psr/log 2 & 3. (no code needed)

# 1.0.2
- :wrench: Fix - Context not being finalised when there is an exception.

# 1.0.1 
- :wrench: Fix - File writers not using the file abstraction.

# 1.0.0
- :confetti_ball: :tada: First stable release :tada: :confetti_ball:
- :star2: Added support for symfony 6
- :star2: Added better handling of 'execution' files to use as input files or as output files. (See Symfony Bundle)
- :star2: Improve method resolution for chain operations to use types instead of arbitrary names. processData can be names anything now as long as it typed properly
- :wrench: Improve the global quality of the code by having typed methods.

-:exclamation: This will break any of your existing chain declarations: 
 - If you are using the symfony bundle only your custom operations will break (not factories). The context is now an object. 
 - If not the definition of chains remains un changed but both the `ChainBuilder` and `ChainProcessor` has changed.

# 0.4.0
- :star2: Added support for symfony 5

# 0.3.0
- :star2: Added support for symfony 4

# 0.2.0 

- :star2: Added a chainBuilder this allow building chanins from descriptions (such as yaml files)
- :star2: Added the [Symfony Expression Langauge](https://symfony.com/doc/3.4/components/expression_language.html) to the RuleEngine
-:exclamation: The condition rule was deprecated in favor of the Symfony Expression Language.

# 0.1.0

- :star2: Added possibility to have nested columns when using the RuleTransformer
- :star2: Added possibility to have dynamic columns when using the RuleTransformer

# 0.0.3

- :wrench: Added unit test on important & complex components.
- :star2: Added a crude error support, allows to have understood in which ETL operation the error happened. (Hopefully)

# v0.0.2

- :star2: Added support for php 5.6 & 7.0

# v0.0.1
- :confetti_ball: :tada: First release :tada: :confetti_ball:
