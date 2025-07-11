---
layout: base
title: PHP-ETL - Getting Started
subTitle: 🎵 Symfony Framework
---

## The Symfony Bundle

The Php etl bundle allows the usage of the library in symfony. It adds the required "commands" as well as "services" 
to make the etl easy to use. 

### Install

1. Start by installing the necessary dependencies
```sh
    composer require oliverde8/php-etl-bundle
```

2. in `/config/` create a directory `etl`

3. Enable bundle:
```php
\Oliverde8\PhpEtlBundle\Oliverde8PhpEtlBundle::class => ['all' => true],
```

4. **Optional** You can enable queue's if you have an interface allowing users to execute etl processes (Easy Admin for example). 
```yaml
framework:
  messenger:
    routing:
        "Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage": async
```

5. **Optional:** Enable creation of individual files for each log by editing the monolog.yaml
```yaml
etl:
  type: service
  id: Oliverde8\PhpEtlBundle\Services\ChainExecutionLogger
  level: debug
  channels: ["!event"] 
```

### Usage

#### Creating an ETL chain

Each chain is declared in a single file. The name of the chain is the name of the file created in `/config/etl/`. 

Example:
```yaml
chain:
  "Dummy Step":
    operation: rule-engine-transformer
    options:
      add: true
      columns:
        test:
          rules:
            - get : {field: [0, 'uid']}
```

#### Executing a chain

```sh
./bin/console etl:execute demo '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

The first argument is the input, depending on your chain it can be empty. 
The second are parameters that will be available in the context of each link in the chain.

#### Get a definition

```sh
./bin/console etl:get-definition demo
```

#### Get definition graph

```sh
./bin/console etl:definition:graph demo
```

This will return a mermaid graph. Adding a `-u` will return the url to the mermaid graph image.

## Adding an Easyadmin interface

If you a use easyadmin with your symfony project you can have an admin interface allowing you to monitor & execute 
etl processes (see enable queue's for allowing creation of tasks)

1. Install the necessary dependencies
```sh
    composer require oliverde8/php-etl-easyadmin-bundle
```

2. Enable the bundle
```php
\Oliverde8\PhpEtlBundle\Oliverde8PhpEtlEasyAdminBundle::class => ['all' => true],
```

3. Add to easy admin
```php
yield MenuItem::linktoRoute("Job Dashboard", 'fas fa-chart-bar', "etl_execution_dashboard");
yield MenuItem::linkToCrud('Etl Executions', 'fas fa-list', EtlExecution::class);
```

4. Enable routes
```yaml
etl_bundle:
  resource: '@Oliverde8PhpEtlEasyAdminBundle/Controller'
  type: annotation
  prefix: /admin
```

See the [github repository](https://github.com/oliverde8/phpEtlEasyAdminBundle) for additional information. 