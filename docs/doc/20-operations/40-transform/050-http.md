---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Simple HTTP Client
---

Allow's the execution of a http rest call using Symfony HttpClient.

## Options

- **method:** GET, POST, PUT ...
- **options:** Used to create the HttpClient.
- **url:** The url to call. Custom variables can be used with the symfony expression language. Todo so the option needs to start with `@`.
- **response_is_json:** If response is json or not. If true the result will be parsed automatically.
- **option_key:** Key of the data item in input that will be used to create the requests.
- **response_key:** Key of the DataItem where the results should be put into.

## Example Use

🚧 TODO 🚧