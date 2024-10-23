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

## Other Item types

### DataItem

This is our main object we will be receiving containing an associative array of data. You can instiate new ones as needed.
You receive it in argument, and you can return it in your process function.

### ChainBreakItem

Your operation can return a chainBreakItem if you want to stop the current objects propagation to the next steps
of the chain.

### GroupedItem

Can only be returned by an operation, as grouped items are not propagated as they are. GroupedItems are split into
individual DataItems automatically by the internal workings of the Etl.

A CSV reader will for example return a GroupedItem of a Csv Reader iterator.

### StopItem

Can be received and returned, but you should never entirely replace a StopItem nor should you initiate a
new StopItem yourself.

The StopItem is automatically created by the ETL, the etl will continue to push a StopItem as long as it has not been
propagated through all the operations. So if an operation ignores the StopItem and returns a DataItem, that operation
will continue to receive a StopItem until it returns back a StopItem.

A File writing operation will for example close the file when it receives a StopItem,

An operation that groups data will for example return a DataItem when it reveice a StopItem for the first time. That's
the data in had in memory that it needs to propagate. The second time it receives a StopItem it has nothing more
to propagate, it will therefore return the StopItem and allow the chain to end.

### FileExtractedItem

This item is propagated after all lines/data from a file has been extracted. It's mostly ignored but could be used by
custom steps to archive read files for example or for other purposes.

It's the responsibility of the Extraction Operation to return this item; the ETL does not make it mandatory.

### FileLoadedItem

This item is propagated after we finished writing in a file. This can be used to archive a file, send a file to an sftp
our per email...

It's the responsibility of the Extraction Operation to return this item; the ETL does not make it mandatory.

### MixItem

In some cases you might to return multiple items, for example when extracting data from a CSV file. In that case 
you need to return a `GroupedItem` which will basically read the file line by line, and you need to return 
a `FileExtractedItem` to propagate the information on the file that was read. In this case you can return 
a MixItem containing an array with both the items. 

### AsyncItemInterface

This is the most "complex" item type of the ETL. When a AsyncItemInterface is returned the etl will periodically check 
if the Item's execution has terminated. Why the item is being processed other items will be processed as well. This
can be used to do concurrent calls to rest api's for example. 


