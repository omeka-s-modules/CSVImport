<?php
namespace CSVImport\Mapping;

class ItemMapping extends AbstractResourceMapping
{
    static protected $label = 'Item data'; // @translate
    static protected $resourceType = 'items';

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();
        parent::processGlobalArgsItem();
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);
        parent::processCellItem($index, $values);
    }
}
