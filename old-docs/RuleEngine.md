# Rule Engine - Data Transformation
 
Is a mini transformation engine that transform an associative array into another associative array 
using various sets of rules.

It is meant to be used in the ETL, with the RuleTransformOperation.

## List of available rules

### Expression Langauge (expression_language)

Uses the [Symfony expression langauge](https://symfony.com/doc/3.4/components/expression_language/syntax.html) to 
generate values.

| param name     |     type    | description |
|----------------|-------------|-------------|
|**expression**  | string      | The sf expression. RawData is available in the `rowData` variable |
|**values**      | array       | Can be empty, if not empty adds more variables to be used in the expression |

### Value fetcher (get)

Fetch data from the input.

| param name     |     type    | description |
|----------------|-------------|-------------|
|**field**       | string        | The name of the field to fetch the data from |

### Implode (implode)

As it names indicates it allows to imlode an array into a string

| param name     |     type    | description |
|----------------|-------------|-------------|
|**value**       | rule        | the value that will be imploded, fetched using rule |
|**with**        | string      | Glue to use |

### String To Lower (str_lower)

Allow to lower case a string.

| param name     |     type    | description |
|----------------|-------------|-------------|
|**value**       | rule        | Value to lowercased fetched using rules. |


### String To Upper (str_upper)

Allow to upper case a string.

| param name     |     type    | description |
|----------------|-------------|-------------|
|**value**       | rule        | Value to upercase fetched using rules. |


### Constant (constant)

Have a constant value

| param name     |     type    | description |
|----------------|-------------|-------------|
|**value**       | mixed       | Constant value to be returned |


## Deprecated Rules : 

### Condition (condition)

**Deprecated : expression_language can do equivalent and better**

As it names indicates it allows to add conditioning. 

| param name     |     type    | description |
|----------------|-------------|-------------|
|**if**          | rule        | Value to be compared |
|**value**       | rule        | Value to be compared with |
|**operation**   | rule        | Operation for the comparaison.  Supported operations are (eq, neq, in) |
|**then**        | rule        | Value to send back when the condition is true |
|**else**        | rule        | Value to send back when the condition is false |