<?php

require_once __DIR__ . '/vendor/autoload.php';

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Loader\File\Csv;
use Oliverde8\Component\PhpEtl\Model\File\Csv\Writer;

$inputIterator = new Csv(__DIR__  . '/exemples/I-Service.csv');

$localizableAttributes = ['name_src', 'editor_name_src', 'valid_from', 'valid_to'];

$operations = [];

// Cleanup the data to use akeneo attribute codes.
$operations[] = new CallbackTransformerOperation(function (DataItemInterface $item, &$context) {
    $data = $item->getData();
    $newData = [];
    $newData['sku'] = implode(
        '_',
        [
            'SRV',
            AssociativeArray::getFromKey($data, 'APS_ID')
        ]
    );
    $newData['locale'] = implode(
        '_',
        [
            strtolower(AssociativeArray::getFromKey($data, 'LANGUAGE')),
            strtoupper(AssociativeArray::getFromKey($data, 'COUNTRY')),
        ]
    );
    $newData['categories'] = 'main_service,web_service';
    $newData['family'] = 'service';
    $newData['name_src'] = AssociativeArray::getFromKey($data, 'SERVICE_NAME');
    $newData['editor_name_src'] = AssociativeArray::getFromKey($data, 'PROVIDER');
    $newData['valid_from'] = AssociativeArray::getFromKey($data, 'BEGIN_DATE');
    $newData['valid_to'] = AssociativeArray::getFromKey($data, 'END_DATE');

    return new DataItem($newData);
});

// Group products by sku.
$operations[] = new SimpleGroupingOperation(['sku']);

// Finalize transformation by having proper attribute codes taking locales into account.
$operations[] = new CallbackTransformerOperation(
    function (DataItemInterface $item, &$context) use ($localizableAttributes) {

        $data = [];
        foreach ($item->getData() as $productDetails) {
            $locale = $productDetails['locale'];
            unset($productDetails['locale']);

            foreach ($productDetails as $attributeCode => $value) {
                if (in_array($attributeCode, $localizableAttributes)) {
                    $data[$attributeCode . '-' . $locale] = $value;
                } else {
                    $data[$attributeCode] = $value;
                }
            }
        }

        return new DataItem($data);
    }
);

// Write into files.
$operations[] = new FileWriterOperation(new Writer(__DIR__ . '/exemples/output.csv'));

$chainProcessor = new ChainProcessor($operations);
$chainProcessor->process($inputIterator, []);