---
layout: base
title: PHP-ETL - Getting Started
subTitle: 🦢 Sylius the e-commerce framework based on symfony
---

## The sylius bundle

The sylius bundle uses the Symfony bundle (maybe pretty obvious). It therefore allows the usage of the library in symfony. 
It adds the required "commands" as well as "services" to make the etl easy to use. 

### Install

**Start by installing the symfony bundle**

{% include doc/getting-started/install-symfony.md %}

**Now let's install the sylius bundle**

1. Install the additional dependency
```sh
composer require oliverde8/php-etl-sylius-admin-bundle
```

2. Create EtlExecution table via migrations

3. Import configs
```yaml
# config/packages/etl.yaml
imports:
  - { resource: "@Oliverde8PhpEtlSyliusAdminBundle/Resources/config/config.yaml" }
```

4. Import routes
```yaml
# config/routes/etl.yaml
oliverde8_etl:
  resource: '@Oliverde8PhpEtlSyliusAdminBundle/Resources/config/routing.yaml'
```

5. **Optional:** Configure EtlExecution Message:
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        # Uncomment this (and the failed transport below) to send failed messages to this transport for later handling.
        failure_transport: failed

        transports:
            failed: 'doctrine://default?queue_name=failed'
            generic_with_retry:
                dsn: 'doctrine://default?queue_name=generic_with_retry'
                retry_strategy:
                    max_retries: 3
                    multiplier: 4
                    delay: 3600000 #1H first retry, 4H second retry, 16H third retry (see multiplier) 
            etl_async:
                dsn: 'doctrine://default?queue_name=etl_async'
                retry_strategy:
                    max_retries: 0

        routing:
            'Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage': etl_async
```

### Usage

{% include doc/getting-started/usage-symfony.md %}