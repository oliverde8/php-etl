---
layout: base
title: PHP-ETL - Cook Books
subTitle: Making your chains configurable
width: large
---

{% capture description %}
You are able to configure through the input the names of the files that are being read.
{% endcapture %}
{% capture code %}
```php
$chainProcessor->process(
    new ArrayIterator([__DIR__ . "/customers.csv"]),
    []
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
But we might need to configure some operations independently from the input. For example the name of the csv output file.
{% endcapture %}
{% capture code %}
```yaml
write-new-file:
  operation: csv-write
  options:
    file: "output.csv"
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}

The name "output.csv" is hardcoded here. But we can make this dynamic with symfony expression language. We will need
to start our line with the `!` character.
{% endcapture %}
{% capture code %}
```yaml
write-new-file:
  operation: csv-write
  options:
    file: "!filewriter['outputfile']['name']"
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

We will also need to give this informaton when the chain is being created:

{% capture column1 %}
#### 🐘 Standalone
```php
$inputOptions = ['filewriter' =>
    ['outputfile' =>
        ['name' => 'configured-output.csv']
    ]
];

$chainProcessor = $builder->buildChainProcessor(
    Yaml::parse(file_get_contents($fileName))['chain'],
    $inputOptions
);
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony
```sh
./bin/console etl:execute myetl "['./customers.csv']" "{'outputfile': {'name': 'configured-output.csv'}}"
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

### Complete Code

```yaml
chain:
  read-file:
    operation: csv-read
    options: [] # The default delimeter,&

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
        SubscriptionStatus:
          rules:
            - get: {field: 'IsSubscribed'}

  write-new-file:
    operation: csv-write
    options:
      file: "!filewriter['outputfile']['name']"

```