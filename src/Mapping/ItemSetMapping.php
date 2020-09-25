<?php
namespace CSVImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

class ItemSetMapping extends AbstractResourceMapping
{
    protected $label = 'Item set data'; // @translate
    protected $resourceType = 'item_sets';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/item-set')
            . parent::getSidebar($view);
    }

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
