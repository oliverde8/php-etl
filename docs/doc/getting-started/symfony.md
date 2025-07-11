---
layout: base
title: PHP-ETL - Getting Started
subTitle: 🎵 Symfony Framework
---

## The Symfony Bundle

The Php etl bundle allows the usage of the library in symfony. It adds the required "commands" as well as "services" 
to make the etl easy to use. 

### Install

{% include doc/getting-started/install-symfony.md %}


### Usage

{% include doc/getting-started/usage-symfony.md %}

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