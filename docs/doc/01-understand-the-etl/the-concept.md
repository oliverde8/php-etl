---
layout: base
title: PHP-ETL - Understand the ETL 
subTitle: The Concept
width: large
---

## Basic Concept of PHP-ETL

PHP-ETL is structured around a series of operations that process items. 
Each item represents either a unit of data that can be passed from one operation to the next in a defined chain or the 
result of an action inside the ETL.

Each operation in the can define specific item types it will handle (each operation can handle any number of
different types of items) and any item that doesn’t match an operation’s defined input simply skips that step.


### Item Types

Different item types serve distinct purposes in the ETL chain. Here are the basic ones you will need to understand
how the etl works.

- **DataItem:** This is the most common item type and is typically used to represent rows of data. 
It can be both input and output in most operations, serving as the primary "payload" of data transformations.

- **ChainBreakItem:** This item type, if returned by an operation, signals that the current chain should stop processing 
the current item and move to the next one. It’s mostly used to filter data inside the chain.

- **StopItem:** This item type is automatically created when all items in the chain are processed. 
It signals the end of processing, allowing operations to finish any remaining tasks, such as persisting data 
or releasing resources. 
StopItems should never be manually created, only utilized when they naturally occur at the end of processing.

- **GroupedItem:** This item encapsulates multiple row of data within an iterator. 
When a GroupedItem is encountered, the items it contains are processed as individual DataItems downstream, 
so a GroupedItem can not be in the input of an operation, they can only be the output.

You can find the list of all native item types [here](doc/01-understand-the-etl/item-types.html).

## Example: Simple CSV Transformation

To demonstrate PHP-ETL’s capabilities, let’s walk through a basic example where we read a CSV file, 
modify each line, and output a new CSV file with selected columns.

### Step 1: Read the Input CSV File

{% capture code %}
```yaml
chain: 
    read-file:
        operation: csv-read
        options: []
```
{% endcapture %}

{% capture description %}
The first step is reading the input CSV. PHP-ETL can handle large volumes of data efficiently by processing each row individually, allowing memory-efficient transformations.

This operation reads the input file line by line, it does this by: 
- Creating an iterator that will iterate on the csv file. 
- Returning a GroupedItem containing this iterator. 

The internals in the ETL will then transform each line returned by the iterator into a DataItem and send it to the
next step. While this step will return a single `GroupedItem` the next step will receive as many `DataItem`'s as theye
are lines in the csv file.
{% endcapture %}

{% include block/etl-step.html code=code description=description %}

### Step 2: Transform the Data

{% capture code %}
```yaml
chain:
    keep-only-name-and-subscription:
      operation: rule-engine-transformer
      options:
        # Replace existing columns with transformed columns.
        add: false  
        columns:
          Name:
            rules:
              - implode: # Concatenate FirstName and LastName
                  values:
                    - [{get : {field: 'FirstName'}}]
                    - [{get : {field: 'LastName'}}]
                  with: " "
          SubscriptionStatus:
            rules:
              - get: {field: 'IsSubscribed'}
```
{% endcapture %}

{% capture description %}
We’ll use a **rule-engine-transformer** operation to transform the data. This operation allows us to define custom
transformations for each column.


In this example:
- **Name** is created by concatenating FirstName and LastName with a space.
- **SubscriptionStatus** copies the IsSubscribed field without any transformation.
{% endcapture %}

{% include block/etl-step.html code=code description=description %}

### Step 3: Write the Output CSV File

{% capture code %}
```yaml
chain:
    write-new-file:
      operation: csv-write
      options:
        file: "output.csv"
```
{% endcapture %}

{% capture description %}
The transformed data is then written to a new CSV file using the csv-write operation. This operation 
will write each line one by one but close the file only when the etl finishes. 

> ##### TIP
> We will dive later into how this operations should be used in a context of exporting data. Indeed when exporting
> data we need to keep a backup of the file and we also need to make sure other processes won't read the file
> before we finished writing in it.
{: .block-tip }
{% endcapture %}

{% include block/etl-step.html code=code description=description %}

### Step 4: Run the Chain Processor

The chain processor is then initialized with the input file(s). 

{% capture column1 %}
#### 🐘 Standalone
For instance, the following code will process two input files and merge their output:

```php
$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv", __DIR__ . "/customers2.csv"]),
    []
);
```
{% endcapture %}

{% capture column2 %}
#### 🎵 Symfony
For instance, the following command will process two input files and merge their output:
```bash
./bin/console etl:execute myetl.yaml "['./customers1.csv', './customers2.csv']"
```
{% endcapture %}

{% include block/2column.html column1=column1 column2=column2 %}

### Output

The output of this ETL will be a output.csv file containg all customer data from files customers1.csv and customers2.csv.

