---
layout: base
title: PHP-ETL - Cook Books
subTitle: Grouping / Aggregation
width: large
---

A second example we can work on is to write a json file where customers are grouped based on their subscription state.
We will write this in json as its more suited to understand what we are doing.

{% capture description %}
Let's start by reading our csv file
{% endcapture %}
{% capture code %}
```yaml
  read-file:
    operation: csv-read
    options: [] # The default delimeter
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We will use the `simple-grouping` operation for this. **This operation needs to put all the data in memory
and should therefore be used with caution.**

We have a single **grouping-key**, we can make more complex grouping operations, by grouping by subscription status and
gender for example.

Grouping identifier allows us to remove duplicates, if we had customer emails we could have used
that information for example.
{% endcapture %}
{% capture code %}
```yaml
group-per-subscription:
  operation: simple-grouping
  options:
    grouping-key: ['IsSubscribed']
    group-identifier: []
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We will also use json write operation.

This works like the csv file, but is more suited for complex multi level datas as we have after the grouping.
{% endcapture %}
{% capture code %}
```yaml
write-new-file:
  operation: json-write
  options:
    file: "output.json"
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete yaml

```yaml
chain:
  read-file:
    operation: csv-read
    options: [] # The default delimeter

  group-per-subscription:
    operation: simple-grouping
    options:
      grouping-key: ['IsSubscribed']
      group-identifier: []

  write-new-file:
    operation: json-write
    options:
      file: "output.json"

```