---
layout: base
title: PHP-ETL - Cook Books
subTitle: Using Sub Chains
width: large
---

### Using subchains

There will be cases where the chain description can become quite repetitive, let's take the following example
from Chapter 1 - [Splittin/Forking](/doc/10-examples/120-split-the-chain.html).

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

The subChain can be used in such cases:


{% capture description %}
We can create such a subchain that will make the necessary transformations.
{% endcapture %}
{% capture code %}
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
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We can use this operation anywhere within our chain
{% endcapture %}
{% capture code %}
```yaml
          transform:
            operation: subchain
            options:
              name: customTransform
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

**The following rules applies for subchains:**
- Sub chains can have multiple operations as with a normal chain.
- Operation for subchains are cloned, so a grouping operation will not share memory. Unless option; shared is true.
- subchains can use subchains, so it's possible to have multiple levels of subchains.

#### Complete Code

```yaml
subChains:
  customTransform:
    chain:
      generic-subchain-transformation:
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
  read-file:
    operation: csv-read
    options: [] # The default delimeter,&
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