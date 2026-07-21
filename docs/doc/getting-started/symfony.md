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

## Live execution graph

Once the EasyAdmin interface is wired in, every execution's detail page shows its chain as a live graph:
topology, per-step item counts, and a streaming log tail — updating in real time while the chain runs.

![The live execution graph while a chain is running, mid-way through a split into two branches](/assets/images/live-execution-graph/execution-detail-running.png)

Click any step to inspect its live state: items in/out, time spent, throughput, and that step's own log lines.

![A step selected, showing its per-step stats and logs in the side drawer](/assets/images/live-execution-graph/execution-detail-node-drawer.png)

The graph degrades gracefully depending on what's installed and how the execution runs:

| Setup | Behaviour |
|---|---|
| `symfony/mercure-bundle` installed + hub configured, execution running async | **live push** over Mercure (SSE) |
| execution running async, no Mercure | polls for state + logs every couple of seconds |
| execution finished, or run synchronously (`sync` transport) | fully static graph from the persisted result |

### Install

Nothing beyond the EasyAdmin bundle above is required to see the static/polling graph. To get there and to light up
real-time push:

1. Mount the bundle's observability endpoints (topology, state and log JSON used by the widget) — put them behind
   your admin firewall, the same one that protects the EasyAdmin routes above:

   ```yaml
   # config/routes/oliverde8_etl_observability.yaml
   oliverde8_php_etl_observability:
       resource: '@Oliverde8PhpEtlBundle/Controller/'
       type: attribute
       prefix: /admin
   ```

2. Route executions launched from the UI through the async transport, with a worker consuming it — without this,
   a chain runs synchronously in-request and the graph has nothing to stream (it just renders static once done):

   ```yaml
   framework:
     messenger:
       routing:
           'Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage': async
   ```

   ```sh
   php bin/console messenger:consume async
   ```

3. **Optional, for live push instead of polling:** install `symfony/mercure-bundle` and configure a hub:

   ```sh
   composer require symfony/mercure-bundle
   ```

   ```yaml
   # config/packages/mercure.yaml
   mercure:
       hubs:
           default:
               url: '%env(default::MERCURE_URL)%'
               public_url: '%env(default::MERCURE_PUBLIC_URL)%'
               jwt:
                   secret: '%env(MERCURE_JWT_SECRET)%'
                   publish: '*'
   ```

   Point `MERCURE_URL`/`MERCURE_PUBLIC_URL`/`MERCURE_JWT_SECRET` at any Mercure hub — the standalone
   [Mercure.rocks](https://mercure.rocks/) Docker image, or the Caddy module bundled with
   [FrankenPHP](https://frankenphp.dev/) if that's how you serve the app. The bundle detects
   `symfony/mercure-bundle` automatically and starts pushing; nothing else to configure.

4. Publish the widget's assets:

   ```sh
   php bin/console assets:install public
   ```

### A note on security

Every execution's state and logs are published to Mercure as **private** topics, scoped to that one execution — the
detail page mints a subscriber JWT (as a cookie) for the execution being viewed, so someone can't just guess another
execution's topic and subscribe to its live logs by reaching the hub directly. You don't need to configure any of
this yourself.

What you *do* still need to configure: `EtlExecutionVoter` (the voter the bundle's controllers check) ships as a
permissive stub that grants every attribute (`view`, `queue`, `download`, `dashboard`) to everyone. It's meant to be
decorated with your own authorization logic. Until you do, the only thing standing between the outside world and
these executions — their data, logs, and downloadable output files — is your app's firewall/`access_control` around
wherever you mounted the EasyAdmin and observability routes (`/admin` in the examples above). Make sure that's a real,
authenticated firewall before deploying.

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