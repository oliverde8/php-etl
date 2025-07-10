---
layout: base
title: PHP-ETL - Cook Books
subTitle: With Context - Api to CSV
width: large
---

### With Context - Api to CSV

{% capture description %}
The chain definition is identical to our previous [definition](/doc/10-examples/150-api-csv.html) one without a context. 
It's the end results that changes, as now our file is created within the unique context.
{% endcapture %}
{% capture code %}
```yaml
chain:
  get-from-api:
    operation: http
    options:
      url: https://63b687951907f863aaf90ab1.mockapi.io/test
      method: GET
      response_is_json: true
      option_key: ~
      response_key: ~
      options:
        headers: {'Accept': 'application/json'}

  split-item:
    operation: split-item
    options:
      keys: ['content']
      singleElement: true

  write-new-file:
    operation: csv-write
    options:
      file: "output.csv"

```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

If we wish to "make" available outside the context we will need to use specific operations to achieve that. If we wish
to access a file outside the context we will also need to import the file in the context first. We will see this in
other examples.
