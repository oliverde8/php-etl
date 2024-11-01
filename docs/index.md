---
layout: base
title: Welcome to the PHP-ETL Doc page
subTitle: 
---

## What is PHP-ETL

PHP-ETL is the go-to library for executing complex data import, export, and transformation tasks within PHP applications. 
It offers seamless integrations with the [Symfony Framework](https://symfony.com/), [Sylius](https://sylius.com/fr/) , and can easily be extended to 
other CMS and &frameworks, making it ideal for handling intricate data workflows with ease.

## Why PHP-ETL

PHP-ETL was built to address a common challenge in real-world applications: while complex data transformations should 
ideally be handled by enterprise ETL tools or ESBs, the reality is that many CMS platforms require intricate 
transformations within the application itself. This often leads to complex, hard-to-maintain code with limited
flexibility, usually confined to specific execution methods like command-line scripts.

I wanted a more flexible solution—one that allowed easy splitting of code, reusable operations,
consistent logging, and a clear history of processed files. PHP-ETL offers a standardized approach for handling 
complex tasks, such as reading remote files and performing advanced operations like data aggregation,
all while promoting efficient memory usage. 
It provides an abstraction layer for common tasks, simplifying operations across various file systems 
(via [Flysystem](https://flysystem.thephpleague.com/docs/)) and ensuring backup and accessibility of processed files.

Additionally, while PHP isn't naturally suited for asynchronous tasks, 
PHP-ETL handles asynchronous operations—such as API calls—natively, allowing certain tasks to run in parallel, 
like loading data into the database while making API calls. The library also supports visualizing data flows
through auto-generated diagrams, making complex workflows easier to understand and manage.

## A screenshot


