# Describe a chain using Yaml! - 02

This is the second chapter on describing chains using yaml. Here we will go abit further and see more complex use cases. 

If you haven't already you should read the [first chapter first](./DescribeChain.md). 

## Sub chains. 

There will be cases where the chain description can become quite repetitive, let's take the following example
from Chapter 1 - [Example 05 - Write 3 customer files](./DescribeChain.md).

In that example we have split our customer.csv files into 2 files, one with the customers subscribed to the newsletter
and one with those not subscribed. We do not do any additional process to change the structure of the data. 

Let's now imagine we would like to extract only the firstName and Lastname from the csv file for the subscribed customers. 
The resulting chain would look like: 

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
          transform:
            operation: rule-engine-transformer
            options:
              add: false # We want to replace all existing columns with our new columns.
              columns:
                FirstName:
                  rules:
                    - get : {field: 'FirstName'}
                LastName:
                  rules:
                    - get : {field: "LastName"}
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

In order to do the same for both subscribed & unsubscribed customer we would need to duplicate the whole `transform` 
operation. That would be quite inefficient. Also this is a very simple case, if we wanted to add grouping and more 
transforms it makes the amount of duplications even more important.  

The subChain can be used in this particular case: 

```yaml
subChains:
  customTransform:
    chain:
      -
        operation: rule-engine-transformer
        options:
          add: false # We want to replace all existing columns with our new columns.
          columns:
            FirstName:
              rules:
                - get : {field: 'FirstName'}
            LastName:
              rules:
                - get : {field: "LastName"}

chain: 
  # .... 
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
          transform:
            operation: subchain
            options:
              name: customTransform
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
          transform:
            operation: subchain
            options:
              name: customTransform
          write-new-file:
            operation: csv-write
            options:
              file: "unsubscribed.csv"
```

**The following rules applies for subchains:**
- Sub chains can have multiple operations as with a normal chain. 
- Operation for subchains are cloned, so a grouping operation will not share memory. Unless option; shared is true.
- subchains can use subchains, so it's possible to have multiple levels of subchains.

You can test this rule yourself, check the [transform yml](examples/01-describe/01-subchain.yml)
and by executing `php docs/examples/01-describe/01-subchain.php`