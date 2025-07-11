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

## Changing the location of the contextual file system dir


Every PHP-ETL execution is tied to a **dedicated directory**, which serves as a central location for:

- Input and output files
- Logs and debug artifacts

This design allows each execution to be **self-contained**, making logs and file traces easy to access and 
audit—without requiring individual operations to manage paths or storage manually.

By default, PHP-ETL stores these execution directories **on the local filesystem** in var/etl of the symfony project. 
But what if:

- You want to store files on **remote storage**, like **Amazon S3** or **Google Cloud Storage**?
- You need to **move or centralize** execution data across environments?

PHP-ETL uses [Flysystem](https://flysystem.thephpleague.com/) as its file abstraction layer—wrapped in its own internal abstraction. 
This allows you to fully control where and how files are stored, using any Flysystem-compatible adapter (S3, SFTP, etc.).

To customize where the execution directory is stored, you can override the default `FileSystemFactory`.

1. **Create a custom implementation** of the [`FileSystemFactoryInterface`](https://github.com/oliverde8/phpEtlBundle/blob/main/Services/FileSystemFactory.php).

2. **Register your service in Symfony**, replacing the default implementation:

```yaml
services:
  Oliverde8\PhpEtlBundle\Services\FileSystemFactoryInterface: '@App\Services\FileSystemFactory'
```