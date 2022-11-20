# Describe a chain using Yaml!

## How to read your Yaml File

The first step would be to initialize the `Builder` object. This will create a ChainProcessor from an array. 

First let's initialize a ChainBuilder with all the operation factories registered.
```php
$builder = new ChainBuilder(new ExecutionContextFactory());
$builder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
// ...
```

Then you can create load the yaml and use it to create the ChainProcessor. 

```php
$chainProcessor $builder->buildChainProcessor(
    Yaml::parse(file_get_contents($fileName))['chain']
);
```

You can find a complete example in the examples [.init.php](examples/.init.php)

We can now execute our chain, with an Iterator as in input: 

```php
$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv"]),
    []
);
```

## Getting Started with a simple example

In order to go further let's use an example;

One of the simplest thing we can do with the etl is to read a csv file, modify each line and output a new csv file. 

Let us consider a csv file with 4 columns:
- ID
- FirstName
- LastName
- IsSubscribed

We would like to output a new CSV file with only 2 columns
- Name, that contains the concatenation of FirstName & LastName
- SubscriptionStatus which for now is the same value as the previous IsSubscribed column remapped. 

So our first operation needs to read a csv file. You can find an example file [here](examples/customers.csv)

```yaml
read-file:
    operation: csv-read
    options: []
```

The PhpEtl works with individual lines, so each individual line will be sent to the next step. This allows the ETL to 
be memory efficient to handle big volumes, but can be a constraint in some cases. 

In our Case let us add a rule operation to transform the files. We will check more about how to use later on for now 
let us look into a few details on how rule engine works: 

```yaml
keep-only-name-and-subscription:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      Name:
        rules:
          - implode: # Concat both firstname & lastname
              values:
                - [{get : {field: 'FirstName'}}]
                - [{get : {field: "LastName"}}]
              with: " "
      SubscriptionStatus: # Fetch Is subscribe information without applying any transformation.
        rules:
          - get: {field: 'IsSubscribed'}
```

First we wish to say that we want to return a new data, and not add/merge data with the existing data. 
We do this by saying `add: false`. Then we will list the key values we wish to create; in our case we want **Name** 
and **SubscriptionStatus**.  

Let's start with **SubscriptionStatus** as it's easier as it requires no transformation. We will use a 
[get](RuleEngine.md#value-fetcher-get) rule to read  the existing data. Each rules section can have multiple
**rules**, each rule is executed individually and the first  non-null value is kept. 
If each rules returns null then null is kept. 

In this case we read the IsSubscribed without any fallback values, we could have added a constant as a fallback. 

For our **Name** we wish to concat 2 fields, we will therefore use the [implode](RuleEngine.md#implode-implode) rule. 
We need to define what string to use during the concatenation of our 2 value with `with: " "` then list the rules that
needs to be executed to fetch each value in the values field. This is like with the SubscriptionStatus field. But we 
will define 2 set of rules, one for the FirstName and a second for the LastName. 

Finally, let us write a new csv file. 

```yaml
write-new-file:
  operation: csv-write
  options:
    file: "output.csv"
  ```

This step is quite simple and the only option needed here is the name of the output file. 

The last thing to do is to start our chain process. Most processes will need to start with an input (there can be special cases). 
In our case this is the name of the csv file containing our customers.

```php
$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv"]),
    []
);
```

You can test this rule yourself, check the [transform yml](examples/01-csv-transform.yml), and execute `php docs/examples/01-csv-transform.php`

The way phpEtl works is by sending individual data to each step.
So our file path will be sent to the first step that will not return the file path, but will read the file and split 
that data in as many lines as the csv file contains. So running the Etl with multiple input file will allow us to use
this same chain to merge multiple customers csv files. 

```php
$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv", __DIR__ . "/customers2.csv"]),
    []
);
```

You can test this rule yourself by executing `php docs/examples/02-csv-transform-merge.php`

## Fundamentals - Understanding the propagation

PhpEtl works by propagating Items from one end of the chain to the other. On it's way the Item can be: 
- **Replaced with an iterator:** In this case the individual item is replaced with a multitude of new items. For example when we receive a filename then read each individual line.  
- **Replaced**, when we create a new item based of the data of the pervious item. We used for example the **rule** operation with `add: false`
- **Modified:** when we modify the item. In the previous example if we replaced the **rule** operation with `add: true`
- **Untouched:** The operation might use the item todo a process but then return it back untouched. The write csv operation does this, it reads the item data and writes it in a file, then returns it back. Allowing additional processing to be done after.
- **Dropped:** A step might decide that the item is invalid, in which case we wish to prevent the item to continue to be propageted in the chain. We can also use this to group data.

For this purpose the ETL comes with 4 basic Item types. This can be extended to make more complex behaviours 
if needed when developing custom operations.

### DataItem 

This is our main object we will be receiving containing an associative array of data. You can instiate new ones as needed. 
You receive it in argument, and you can return it in your process function. 

### ChainBreakItem

Your operation can return a chainBreakItem if you want to stop the current objects propagation in the next steps 
of the chain. 

### GroupedItem 

Can only be returned by an operation, as grouped items are not propagated as they are. GroupedItems are split into 
individual DataItems automatically by the intrnal workings of the Etl. 

A CSV reader will for example return a GroupedItem of a Csv Reader iterator. 

### StopItem

Can be received and returned, but you should never entirely replace a StopItem nor should you initiate a 
new StopItem yourself.  

The StopItem is automatically created by the ETL, the etl will continue to push a StopItem as long as it has not been 
propagated through all the operation. So if an operation ignores the StopItem and returns a DataItem, that operation
will continue to receive a StopItem until it returns back a StopItem. 

A File writing operation will for example close the file when it receives a StopItem, 

An operation that groups data will for example return a DataItem when it reveice a StopItem for the first time. That's
the data in had in memory that it needs to propagate. The second time it receives a StopItem it has nothing more
to propagate, it will therefore return the StopItem and allow the chain to end. 

### FileExtractedItem

This item is propagated after all lines/data from a file has been extracted. It's mostly ignored but could be used by 
custom steps to archive read files for example or for other purposes. 

It's the responsibility of the Extraction Operation to return this item; the ETL does not make it mandatory.

### FileLoadedItem

This item is propagated after we finished writing in a file. This can be used to archive a file, send a file to an sftp
our per email...

It's the responsibility of the Extraction Operation to return this item; the ETL does not make it mandatory.

## Fundamentals - Understanding more "complex" chains

Let us continue with a few additional examples to see how powerfull phpEtl can be. We will get into more details on 
all available operations and options later.

### Example - Grouping

A second example we can work on is to write a json file where customers are grouped based on their subscription state.
We will write this in json as its more suited to understand what we are doing. 

We will use the `simple-grouping` operation for this. **This operation needs to put all the data in memory
and should therefore be used with caution.**

```yaml
group-per-subscription:
  operation: simple-grouping
  options:
    grouping-key: ['IsSubscribed']
    group-identifier: []
```

We have a single **grouping-key**, we can make more complex grouping operations, by grouping by subscription status and
gender for example. 

Grouping identifier allows us to remove duplicates, if we had customer emails we could have used 
that information for example.

We will also use json write operation 

```yaml
write-new-file:
  operation: json-write
  options:
    file: "output.json"
```

This works like the csv file, but is more suited for complex multi level datas as we have after the grouping. 

You can test this rule yourself, check the [transform yml](examples/03-json-grouped-merge.yml)
and by executing `php docs/examples/03-json-grouped-merge.php`

### Example - Keep subscribed customers only

We can also filter data preventing some of it from being propagated through all the chain, in our example
it will prevent unsubscribed customers from being written in our final csv file. 

```yaml
filter-unsubscribed:
  operation: filter
  options:
    rule: [{get : {field: 'IsSubscribed'}}]
    negate: false
```

The rule engine is used for the filtering, If the rule returns false, 0, empty string or null then the item **will not 
be propagated**. We can also inverse this rule, but changing `negate: true`, in this case the rule needs to return 
false for the item **to be propagated**.

This might seem limiting but the rule engine does support SymfonyExpressions which opens a whole lot of flexibility. 

You can test this rule yourself, check the [transform yml](examples/04-csv-filter.yml)
and by executing `php docs/examples/04-csv-filter.php`

### Example - Write 3 customer files

In our next example which will be also the last of this section we wish to write 3 files. 
- One file containing all the customers
- A second file containing unsubscribed customers
- A third file with subscribed customers. 

To achieve this we will use the split operation. This operation creates multiple new chains linked to the first chain. 
The result of thise new chains are not attached to the main chain. So if we do any filtering in one of these 
**branches** as they are called, the filtering will not be visible on the main branch. 

For our example, the main branch will be used to write all customers, this is very similar to what we did in the
first example. But before writing the files we will add a split operation to create 2 new branches. 1 branch will 
filter to get subscribed customers and write them. The second branch will filter to get un subscribed customers and 
write them. 

```yaml
  branch-out:
    operation: split
    options:
      branches:
        -
          filter-unsubscribed:
            operation: filter
            options:
              rule: [{get : {field: 'IsSubscribed'}}]
              negate: false

          write-new-file:
            operation: csv-write
            options:
              file: "subscribed.csv"
        -
          filter-subscribed:
            operation: filter
            options:
              rule: [{get : {field: 'IsSubscribed'}}]
              negate: true

          write-new-file:
            operation: csv-write
            options:
              file: "unsubscribed.csv"
```

You can test this rule yourself, check the [transform yml](examples/05-csv-3-files.yml)
and by executing `php docs/examples/05-csv-3-files.php`

```mermaid
flowchart TB
S1[Read CSV] -->|Subscribed| S2{Split Step}
S1 -->|UnSubscribed| S2

S2 -->|Susbscribed| S2A1(Filter Subscribed)
S2 -->|UnSubscribed| S2A1

S2 -->|Susbscribed| S2B1(Filter UnSubscribed)
S2 -->|UnSubscribed| S2B1


subgraph SubFlow
S2A1 -->|Susbscribed| S2A2(Write Subscribed)

S2B1 -->|UnSubscribed| S2B2(Write UnSubscribed)
end

S2 --->|Susbscribed| S3(Write Both)
S2 --->|UnSubscribed| S3
```

## Available Operations

You can now read the [Operations](Operations.md) documentation that list all available operations

You can also read all [available rules](RuleEngine.md) for the rule operation. 

Finally check for additional examples here(TBD). 