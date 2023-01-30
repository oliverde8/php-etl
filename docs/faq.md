# FAQ

### Why isn't there a database extract ?

PHP-Etl is a library, not meant to be used standalone, it is meant to be used inside symfony, or magento projects.
Each of these framework/cms's have their own way of handling the database. 

The [symfony bundle](https://github.com/oliverde8/phpEtlBundle) will allow you to use doctrine for example. 

### Is there a validation of the chain configuration ?

Yes there is. you will get an error like this :
```
There was an error building the operation 'simple-grouping' : 
 - "grouping-key" : This field is missing.
 - "file" : This field was not expected.
```

### How is this project maintained

I use this project pretty much daily so any "big" issues should be fixed quite fast. The project is also used by 
projects I am not working on. But I am handling the evolutions and maintenance during my free time so I might have
other priorities that prevent me from updating quickly. 

I try and not to introduce Breaking Changes for those who use the `ChainBuilder`. Quite big changes were made since the 
first stable release without impacting older code. The core of the etl which is very simple is tested as well as the
complex operations to diminish the probability of an unwanted breaking change. 

Version number do follow semantic versioning, and **patch** versions should never introduce a Breaking Change.
**Minor** release can deprecate some features but should not introduce Breaking Changes.
