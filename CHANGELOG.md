# 1.0.3
- :wrench: Fix - Stop Item is sent multiple times when using a step returns multiple GroupedItem's

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
