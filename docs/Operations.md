# Operations

## Extraction

Extractors allow us to read data from files, databases or api's.

### JsonExtractOperation

Allow us to read a json file. 

This operation receives a `DataItem` that contains the path to the json file to read. It will return a list of 
DataItems'. 

#### 🔧 Options

- **fileKey** If the DataItem received is an array, the key in which the path to the csv file can be found. "/" can be used to read sub arrays. Example `key/subkey1`.

### CsvExtractOperation

Allow us to read a csv file. 

The operation receives a `DataItem` that contains the path to the csv file to read. It will return a list DataItem's.

#### 🔧 Options

- **delimiter** The csv delimiter character, default value is *';'*
- **enclosure** The csv enclosure character, default value is *'"'*
- **escape** The csv escape character, default value is *'\\'*
- **fileKey** If the DataItem received is an array, the key in which the path to the csv file can be found. "/" can be used to read sub arrays. Example `key/subkey1`.

---

## Load

Load operations allow us to write data into files, database our using api's. 

### CsvFileWriter

This operation allow us to write csv files with a header row. The FileWriter is expecting to receive a DataItem with 
an associative array with a header name value mapping. 

All the DataItems needs to have the same array keys.

#### 🔧 Options 

- **delimiter** The csv delimiter character, default value is *';'*
- **enclosure** The csv enclosure character, default value is *'"'*
- **escape** The csv escape character, default value is *'\\'*
- **file** The name of the file to save. The file is saved using etl's file abstraction layer. 

---

### JsonFileWriter

This operation allow us to read a json file. The FileWriter is expecting to receive a DataItem with
an associative array;

#### 🔧 Options

- **file** The name of the file to save. The file is saved using etl's file abstraction layer.

---

## Transform

The transform operations allow us to modify, group or split data and more.

### FilterDataOperation

Allow to ignore some items and prevent them from being propagated to the rest of the chain using the [rule engine](RuleEngine.md). 

#### 🔧 Options 

- **rule:** The rule needed to get the value that needs to be not null or not false. 
- **negate:** Invereses the result of the rule, true becomes false, and vice versa.

### RuleTransformOperation

Allow us to use the [rule engine](RuleEngine.md) to transform the data!

#### 🔧 Options

- **columns:** List of array keys to create. This list can contain values with "/" symbol to create multi level arrays. 
Then for each column we will have a list of rules. The rule engine will execute each rule until one returns a non null 
or empty value.
- **add:** The operation can either replace the existing item entirely `add: false` or add new data to it `add: true`

### SimpleHttpOperation

Allow's the execution of a http rest call using Symfony HttpClient.

#### 🔧 Options

- **method:** GET, POST, PUT ...
- **options:** Used to create the HttpClient.
- **url:** The url to call. Custom variables can be used with the symfony expression language. Todo so the option needs to start with `@`.
- **response_is_json:** If response is json or not. If true the result will be parsed automatically.
- **option_key:** Key of the data item in input that will be used to create the requests.
- **response_key:** Key of the DataItem where the results should be put into. 

### SplitOperation

Allow us to split a single DataItem into multiple data items. For example we receive a list of customers and their 
addresses and would like to process all addresses. 

#### 🔧 Options

**keys:** The list of keys to read from the inputed Data, a new DataItem is returned for each key. 
**singleElement:** if true reads only the first key and returns a DataItem for each item in that array.

### SimpleGrouping

Allow us to group data, for example to group customers per customer group and by age. 

#### 🔧 Options

**grouping-key:** List of keys to use to make the grouping.
**group-identifier:** Identifier's to use to identify duplicate entries. 

## Base Operations

Base operations are here to change the behaviour of the ETL. 

### ChainSplitOperation

Allow us to execute multiple chains with incoming Item(s), each chain is independant from each other and from the main
chain. 

#### 🔧 Options

**branches:** A list of etl chains (see examples)

### ChainMergeOperation

Allows us to execute multiple chain operations as with the ChainSplitOperation but at the end the items returned
from each branch is returned to the next steps. 

**⚠** If branches don't filter or transform items then steps after the ChainMerge will receive the same items multiple 
times. There is no detection of duplicate data. This means ChainMergeOperation can actually be also used to split data 
using more complex rules. If for example a single line of a csv file contains both information on the configurable
product and the single product. 

#### 🔧 Options

**branches:** A list of etl chains (see examples)
