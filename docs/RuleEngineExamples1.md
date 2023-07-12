# Rule Engine - Examples #1

## Reading arrays

We can use the get operation to remap a data array. if the get can't find the data then it will return an empty string.  

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      uid:
        - get: {field: 'UniqueId'}
```

It is also possible to give multiple choices, maybe our data is not well normalized and the `UniqueId` key does not 
exist. It is possible in that case to ask the rule engine to return the value from another key. 

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      uid:
        - get: {field: 'UniqueId'}
        - get: {field: 'sku'}
```

It's also possible to mix operations, so if the get operation can't find a value then another operation can. We can use 
this to have a "default" value.

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      uid:
        - get: {field: 'UniqueId'}
        - get: {field: 'sku'}
        - "DefaultUID"
```

The rule engine can also create nested arrays. This is done using the `/` character.

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      data/uid:
        - get: {field: 'UniqueId'}
```

The get operation can of course read nested arrays. This is done using an array.

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      data/uid:
        - get: {field: ["data", "UniqueId"]}
```


Finally it's possible to have dynamic mappings based on data provided in the context. 

```yaml
custom-rule:
  operation: rule-engine-transformer
  options:
    add: false # We want to replace all existing columns with our new columns.
    columns:
      label-{@context/locales}:
        - [{get : {field: ['@context/locales', 'label']}]
```

In this case a `label-` column for each locales provided in the execution context will be created. 