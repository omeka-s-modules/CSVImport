<?php
namespace CSVImport\Mapping;

class ItemMapping extends AbstractResourceMapping
{
    protected $label = 'Item data'; // @translate
    protected $resourceType = 'items';

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
