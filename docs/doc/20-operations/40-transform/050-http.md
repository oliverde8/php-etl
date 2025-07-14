---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Simple HTTP Client(http)
---

The `http` operation makes HTTP requests to external services using the [Symfony HTTP Client](https://symfony.com/doc/current/components/http_client.html). It supports asynchronous requests, allowing your ETL process to continue while waiting for responses.

## Options

- **method:** The HTTP method to use (e.g., `GET`, `POST`, `PUT`).
- **url:** The URL to send the request to. You can use the [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language.html) to dynamically generate the URL. To use the expression language, prefix the URL with `@`.
- **options:** (Optional) An array of options for the HTTP client. See the [Symfony HTTP Client documentation](https://symfony.com/doc/current/components/http_client.html#request-options) for a list of available options.
- **response_is_json:** (Optional) If set to `true`, the response will be automatically decoded as JSON.
- **option_key:** (Optional) The key in the input data that contains the options for the HTTP request.
- **response_key:** (Optional) The key where the response from the HTTP request will be stored in the output data.

## Example

Here's an example of how to use the `http` operation to fetch data from a JSON API and store the response in the `api_response` key:

```yaml
chain:
  - operation: http
    options:
      method: GET
      url: "@'https://api.example.com/users/' ~ data.user_id"
      response_is_json: true
      response_key: api_response

  - operation: rule-transformer
    options:
      # Rules to transform the data, including the api_response.
```