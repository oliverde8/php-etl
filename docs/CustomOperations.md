## Creating you own operations.

To create your own operation you need to extend `Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation`

In your class you will need to create a method taking in parameter an `ItemInterface` and a `ExecutionContext`

**Example**
```php
class MyOperation extends Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation
{
    protected function processItem(\Oliverde8\Component\PhpEtl\Item\ItemInterface $item, \Oliverde8\Component\PhpEtl\Model\ExecutionContext $executionContext): \Oliverde8\Component\PhpEtl\Item\ItemInterface
    {
        // TODO
        return $item;
    }

}
```

If you wish your operation to only process certain item types, for example data items, you can change the signature
of your `processItem` method.

```php
class MyOperation extends Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation
{
    protected function processItem(\Oliverde8\Component\PhpEtl\Item\DataItemInterface $item, \Oliverde8\Component\PhpEtl\Model\ExecutionContext $executionContext): \Oliverde8\Component\PhpEtl\Item\ItemInterface
    {
        // TODO
        return $item;
    }

}
```

The name of the method is not important, only the type of the first argument is important. If you wish to process 
multiple types of items you can create multiple methods. 

```php
class MyOperation extends Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation
{
    protected function processDataItem(\Oliverde8\Component\PhpEtl\Item\DataItemInterface $item, \Oliverde8\Component\PhpEtl\Model\ExecutionContext $executionContext): \Oliverde8\Component\PhpEtl\Item\ItemInterface
    {
        // TODO
        return $item;
    }

    protected function processStopItem(\Oliverde8\Component\PhpEtl\Item\StopItem $item, \Oliverde8\Component\PhpEtl\Model\ExecutionContext $executionContext): \Oliverde8\Component\PhpEtl\Item\ItemInterface
    {
        // TODO
        return $item;
    }
}
```


