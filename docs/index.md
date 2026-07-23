---
layout: base
title: PHP-ETL - Extract, Transform, Load for PHP
subTitle:
---

<div class="pe-home-hero">
    <h1 class="pe-home-hero__title">Extract, Transform, Load.</h1>
    <p class="pe-home-hero__subtitle">
        PHP-ETL is the go-to library for executing complex data import, export, and transformation tasks
        within PHP applications, with seamless Symfony and Sylius integrations.
    </p>
    <div class="pe-home-hero__install">
        <code>$ composer require oliverde8/php-etl</code>
    </div>
    <div class="pe-home-hero__ctas">
        <a href="/doc/getting-started.html" class="pe-btn pe-btn--primary">Get Started</a>
        <a href="https://github.com/oliverde8/php-etl" class="pe-btn pe-btn--outline">GitHub</a>
    </div>
    <div class="pe-home-hero__badges">
        <a href="https://github.com/oliverde8/php-etl/stargazers" class="pe-home-hero__badge">⭐ Star on GitHub</a>
        <a href="https://github.com/sponsors/oliverde8" class="pe-home-hero__badge">💜 Sponsor</a>
    </div>
</div>

## What is ETL?

<p class="pe-home-section__subtitle">
    ETL stands for Extract, Transform, Load: read data from a source (a CSV, an API, a database),
    reshape it into what you need, then write it to a destination. It's the pattern behind
    imports, exports, migrations, and data syncs — done as small, reusable steps instead of one
    tangled script.
    <br><br>
    <a href="/doc/01-understand-the-etl/what-is-etl.html">Learn more →</a>
</p>

## Why PHP-ETL?

<p class="pe-home-section__subtitle">A standardized approach for handling complex data tasks, without locking you into a rigid framework.</p>

<div class="pe-feature-grid">
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">🔗</div>
        <h3>Composable Chains</h3>
        <p>Split, Merge, Repeat, and FailSafe operations let you build complex pipelines out of small, reusable pieces.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">⚡</div>
        <h3>Asynchronous Operations</h3>
        <p>API calls and other long-running tasks can run in parallel without blocking the rest of the chain.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">📁</div>
        <h3>Multi-Source I/O</h3>
        <p>Read and write CSV and JSON, or reach remote files over SFTP, S3, and more via Flysystem.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">🧩</div>
        <h3>Rule Engine</h3>
        <p>Shape and transform data declaratively with a flexible rule engine, no custom transformer code required.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">🎯</div>
        <h3>Framework-Agnostic</h3>
        <p>Use it standalone, or drop into Symfony and Sylius with bundle integrations.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">📊</div>
        <h3>Auto-Generated Diagrams</h3>
        <p>Visualize any chain as a Mermaid flowchart, generated straight from your configuration.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">🛡️</div>
        <h3>Fail-Safe by Design</h3>
        <p>Wrap operations in retryable, exception-catching sub-chains without extra plumbing.</p>
    </div>
    <div class="pe-feature-card">
        <div class="pe-feature-card__icon">🗂️</div>
        <h3>Traceable Executions</h3>
        <p>Keep a clear history of processed files, so nothing important gets silently lost.</p>
    </div>
</div>

## Works With Your Stack

<div class="pe-integrations-grid">
    <a href="/doc/getting-started/standalone.html" class="pe-integration-card">
        <span class="pe-integration-card__icon">🐘</span> Standalone
    </a>
    <a href="/doc/getting-started/symfony.html" class="pe-integration-card">
        <span class="pe-integration-card__icon">🎵</span> Symfony
    </a>
    <a href="/doc/getting-started/sylius.html" class="pe-integration-card">
        <span class="pe-integration-card__icon">🦢</span> Sylius
    </a>
    <a href="https://flysystem.thephpleague.com/docs/" class="pe-integration-card" target="_blank">
        <span class="pe-integration-card__icon">📁</span> Flysystem
    </a>
</div>

## See It In Action

Define a chain in PHP, get a flowchart for free — generated automatically from your configuration. Here's a chain
that splits subscribed customers into their own file while still writing everyone to the main export:

{% capture code_col %}
```php
$chainConfig = (new ChainConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink((new ChainSplitConfig())
        ->addSplit((new ChainConfig())
            ->addLink(new FilterDataConfig([
                ['get' => ['field' => 'IsSubscribed']]
            ]))
            ->addLink(new CsvFileWriterConfig('subscribed.csv'))
        )
    )
    ->addLink(new CsvFileWriterConfig('all-customers.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process($input, []);
```
{% endcapture %}

{% capture mermaid_source %}
flowchart TD
    A[Extract CSV] --> B{Split}
    B --> C[Filter: Subscribed]
    C --> D[Write subscribed.csv]
    B --> E[Write all-customers.csv]
{% endcapture %}

{% capture diagram_col %}
{% include block/mermaid.html mermaid=mermaid_source %}
{% endcapture %}

{% include block/2column.html column1=code_col column2=diagram_col %}

<p class="pe-home-section__subtitle">Real chains can get a lot more involved — see the <a href="/doc/10-examples/180-api-pagination.html">cookbook</a> for real-world examples like API pagination and nested sub-chains.</p>

<div class="pe-home-cta">
    <h2>Ready to get started?</h2>
    <p>Install PHP-ETL and have your first chain running in a few minutes.</p>
    <div class="pe-home-cta__buttons">
        <a href="/doc/getting-started.html" class="pe-btn pe-btn--primary">Read the Docs</a>
        <a href="https://github.com/oliverde8/php-etl" class="pe-btn pe-btn--outline">Star on GitHub</a>
    </div>
</div>
