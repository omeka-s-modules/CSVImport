<?php
namespace CSVImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

class ItemMapping extends AbstractResourceMapping
{
    protected $label = 'Item data'; // @translate
    protected $resourceType = 'items';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/item')
            . parent::getSidebar($view);
    }

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
