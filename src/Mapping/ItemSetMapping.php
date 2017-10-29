<?php
namespace CSVImport\Mapping;

class ItemSetMapping extends AbstractResourceMapping
{
    static protected $label = 'Item set data'; // @translate
    static protected $resourceType = 'item_sets';

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();
        parent::processGlobalArgsItemSet();
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);
        parent::processCellItemSet($index, $values);
    }
}
