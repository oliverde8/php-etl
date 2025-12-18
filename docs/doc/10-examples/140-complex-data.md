---
layout: base
title: PHP-ETL - Cook Books
subTitle: Complex data to csv / Flatten Data
width: large
---

{% capture description %}
Complex `json` files/api responses can be flattened and have multiple columns using the rule engine.
In our example we have a list of products with their name, their skus etc.
The name of the product is different for each locale.

We could manually create a list of columns for each locale using the rule engine, but this will not be very generic,
and if we have a lot of locales & a lot of translatable fields on our products this will be complicated to maintain.
{% endcapture %}
{% capture code %}

## Example products file

```json
[
  {
    "productId": 1,
    "sku": "sku1",
    "name": {
      "fr_FR": "Mon Produit 1",
      "en_US": "My Product 1"
    }
  },
  {
    "productId": 2,
    "sku": "sku2",
    "name": {
      "fr_FR": "Mon Produit 2",
      "en_US": "My Product 2"
    }
  },
  {
    "productId": 3,
    "sku": "sku3",
    "name": {
      "fr_FR": "Mon Produit 3",
      "en_US": "My Product 3"
    }
  }
]
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We can use dynamic columns for this purpose. To use this we will need to list locales when starting the process:
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => __DIR__ . "/products.json"])]),
    [
        'locales' => ['fr_FR', 'en_US']
    ]
);
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
Then we will use the following rule to read the name for each of the given locales:

As you can see the `{@context/locales}` part of the columns name is dynamic. We can use then the get rule to read the
data from that product. We could also have used symfony expression language but both behaves differently if the
given locale is missing. `get` will simply return an empty column, symfony expression language rule will fail.
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;

$chainConfig->addLink(new RuleTransformConfig(
    columns: [
        'name-{@context/locales}' => [
            'rules' => [
                ['get' => ['field' => ['name', '@context/locales']]]
            ]
        ]
    ]
));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Code

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink(new RuleTransformConfig(
        columns: [
            'productId' => [
                'rules' => [
                    ['get' => ['field' => 'productId']]
                ]
            ],
            'sku' => [
                'rules' => [
                    ['get' => ['field' => 'sku']]
                ]
            ],
            'name-{@context/locales}' => [
                'rules' => [
                    ['get' => ['field' => ['name', '@context/locales']]]
                ]
            ]
        ],
        add: false
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => __DIR__ . '/products.json'])]),
    [
        'locales' => ['fr_FR', 'en_US']
    ]
);
```
