---
layout: base
title: PHP-ETL - Cook Books
subTitle: Api to CSV
width: large
---

### API to CSV with single call per user.

Previously we fetched from a mock api all the users, what if we need to call individual api's for each user id.
In order to achieve this we need the url of our api to be "dynamic" as at each execution we need to use another
user id.

{% capture description %}
We can achieve this by using symfony expressions in the url key. To tell the operation that a symfony expression
is being used just prefix it with a `@`. (`!` is for using values from the input, @ is for using data from the current data.
All fields do not support `@` as it's handled by each operation, but all fields support `!` as it's generated before the etl starts
processing).

We will also change the `optionKey`, if not our data (id = 1), will be sent into the options of the HttpClient, which
will cause an error. Having an invalid key here will allow us not to have any options.

Let us note that this operation runs multiple queries with concurrency. A single Symfony HttpClient is created for this
operation. And using the AsyncItems functionality of the ETL, we can run all the http requests in parallel.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;

$chainConfig->addLink(new SimpleHttpConfig(
    url: '@"https://63b687951907f863aaf90ab1.mockapi.io/test/"~data["id"]',
    method: 'GET',
    responseIsJson: true,
    optionKey: '-placeholder-',
    responseKey: null,
    options: [
        'headers' => ['Accept' => 'application/json']
    ]
));
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
Now we can write the users into the csv file, as we have done so in our previous examples.
{% endcapture %}

#### Complete Code

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new SimpleHttpConfig(
        url: '@"https://63b687951907f863aaf90ab1.mockapi.io/test/"~data["id"]',
        method: 'GET',
        responseIsJson: true,
        optionKey: '-placeholder-',
        responseKey: null,
        options: [
            'headers' => ['Accept' => 'application/json']
        ]
    ))
    ->addLink(new RuleTransformConfig(
        columns: [
            'createdAt' => [
                'rules' => [
                    ['get' => ['field' => ['content', 'createdAt']]]
                ]
            ],
            'name' => [
                'rules' => [
                    ['get' => ['field' => ['content', 'name']]]
                ]
            ],
            'avatar' => [
                'rules' => [
                    ['get' => ['field' => ['content', 'avatar']]]
                ]
            ],
            'id' => [
                'rules' => [
                    ['get' => ['field' => ['content', 'id']]]
                ]
            ]
        ],
        add: false
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['id' => 1])]),
    []
);
```
