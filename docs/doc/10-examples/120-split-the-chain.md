---
layout: base
title: PHP-ETL - Cook Books
subTitle: Split/Fork the chain
width: large
---

In our next example 
- One file containing all the customers
- A second file containing unsubscribed customers
- A third file with subscribed customers.

{% capture description %}
To achieve this we will use the split operation. This operation creates multiple new chains linked to the first chain.
The result of these new chains are not attached to the main chain. So if we do any filtering in one of these
**branches** as they are called, the filtering will not be visible on the main branch.

For our example, the main branch will be used to write all customers, this is very similar to what we did in the
first example. But before writing the files we will add a split operation to create 2 new branches. 1 branch will
filter to get subscribed customers and write them. The second branch will filter to get un subscribed customers and
write them.
{% endcapture %}
{% capture code %}
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
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Code

{% capture column1 %}
```yaml
chain:
  read-file:
    operation: csv-read
    options: [] # The default delimeter,&

  branch-out:
    operation: split
    options:
      branches:
        -
          filter-subscribed:
            operation: filter
            options:
              rule: [{get : {field: 'IsSubscribed'}}]
              negate: false

          write-new-file:
            operation: csv-write
            options:
              file: "subscribed.csv"
        -
          filter-unsubscribed:
            operation: filter
            options:
              rule: [{get : {field: 'IsSubscribed'}}]
              negate: true

          write-new-file:
            operation: csv-write
            options:
              file: "unsubscribed.csv"

  write-new-file:
    operation: csv-write
    options:
      file: "output.csv"

```
{% endcapture %}
{% capture mermaid %}
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
{% endcapture %}
{% capture column2 %}
{% include block/mermaid.html mermaid=mermaid %}
{% endcapture %}

{% include block/2column.html column1=column1 column2=column2 %}