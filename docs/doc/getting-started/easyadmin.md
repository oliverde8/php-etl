---
layout: base
title: PHP-ETL - Getting Started
subTitle: 🛠️ EasyAdmin Interface
---

## The EasyAdmin bundle

If you use EasyAdmin with your Symfony project, this bundle gives you a full admin interface to monitor and
execute ETL processes: a dashboard, an execution list/detail view with logs and downloadable output files, and
(see below) a real-time execution graph.

### Install

**Start by installing the Symfony bundle**

{% include doc/getting-started/install-symfony.md %}

**Now install the EasyAdmin bundle**

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

### Usage

{% include doc/getting-started/usage-symfony.md %}

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
