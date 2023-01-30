# PHP ETL Chain

[![Build Status](https://travis-ci.org/oliverde8/php-etl.svg?branch=master)](https://travis-ci.org/oliverde8/php-etl)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=oliverde8@gmail.com&lc=US&item_name=php-etl&no_note=0&cn=&curency_code=EUR&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted)
[![Latest Stable Version](https://poser.pugx.org/oliverde8/php-etl/v/stable)](https://packagist.org/packages/oliverde8/php-etl)
[![Latest Unstable Version](https://poser.pugx.org/oliverde8/php-etl/v/unstable)](https://packagist.org/packages/oliverde8/php-etl)
[![License](https://poser.pugx.org/oliverde8/php-etl/license)](https://packagist.org/packages/oliverde8/php-etl)

Php etl is a ETL that will allow transformation of data in a simple and efficient way.

It comes in 2 php components : 

## Rule engine

The rule engine allows to have configuration based transformations to transform a particular data. 
This is integrated inside the ETL with the `RuleTransformOperation`. 

The rule engine can be used in standalone, [see docs](docs/RuleEngine.md)

## The ETL Chain 

An ETL chain is described by **chain operations** and **Items**. The **chain operation** holds the logic, this
means it can:
- Extract data, (possibly duplicate the item)
- Transform data 
- Load the data somewhere. 

Data will propagate throught the ETL operations using Items, there can be different type of items (We will detail this later.)

Chain operations consumes one **item** in order to create a new **item** or an **iterator**. The purpose is to always 
process data individually. For example if we are importing customers we try to never have the data of more than one
customer in memory. 

### Examples of how it works

We will have more detailed real use cases with sample data a bit further in the document.

In the simplest case the chains receive an iterator containing 2 items in input, both items
are processed by each chain operation. This could be for example a list of customer. Each operation
changes the items.

![](docs/flow-1.png)

In the following example the iterator sends a single item. The first operation will then send **GroupedItems** 
containing 2 items. The first item could be a customer, and then we fetch each order of the customer
in the operation1.

![](docs/flow-2.png)

We can also group items, to make aggregations. The chain receives an iterator containg 2 items, 
the first operation processes both items. It breaks the chain for the first item, and returns an aggregation
of item1 & item 2. This can be used to count the number of customers. This kind of grouping can use more memory
and should therefore be used with care.

![](docs/flow-3.png)

Chains can also be split, this would allow 2 different operations to be executed on the same item.

![](docs/flow-4.png)


## Creating a chain. 

There are 2 ways of writing a chain, either you code it; or you describe the chain in a yaml file. 

- Using php code to initiate each operation yourself, this is not recommended! 
- Using yaml files to descrive the chain. 

Please see the [describe chains using yaml configurations](docs/DescribeChain.md)

## Creating you own operations.

Please refer to the [Custom Opertions doc](docs/CustomOperations.md)

# FAQ

Please refer to the [FAQ](docs/faq.md)

