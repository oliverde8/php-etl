---
layout: base
title: PHP-ETL - Cook Books
subTitle: Filtering data
width: large
---

{% capture description %}
We can also filter data preventing some of it from being propagated through all the chain, in our example
it will prevent unsubscribed customers from being written in our final csv file. So we can add this opertion to our 

The rule engine is used for the filtering, If the rule returns false, 0, empty string or null then the item **will not
be propagated**. We can also inverse this rule, but changing `negate: true`, in this case the rule needs to return
false for the item **to be propagated**.

This might seem limiting but the rule engine does support SymfonyExpressions which opens a whole lot of flexibility.
{% endcapture %}
{% capture code %}
```yaml
filter-unsubscribed:
  operation: filter
  options:
    rule: [{get : {field: 'IsSubscribed'}}]
    negate: false
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete yaml

```yaml
chain:
  read-file:
    operation: csv-read
    options: [] # The default delimeter,&

  filter-unsubscribed:
    operation: filter
    options:
      rule: [{get : {field: 'IsSubscribed'}}]
      negate: false

  write-new-file:
    operation: csv-write
    options:
      file: "output.csv"

```