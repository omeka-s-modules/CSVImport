<?php
namespace CSVImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

class MediaMapping extends AbstractResourceMapping
{
    protected $label = 'Media data'; // @translate
    protected $resourceType = 'media';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/media')
            . parent::getSidebar($view);
    }

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();
        parent::processGlobalArgsMedia();
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);
        parent::processCellMedia($index, $values);
    }
}
