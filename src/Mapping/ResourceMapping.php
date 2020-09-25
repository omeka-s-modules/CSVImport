<?php
namespace CSVImport\Mapping;

use Omeka\Stdlib\Message;
use Laminas\View\Renderer\PhpRenderer;

class ResourceMapping extends AbstractResourceMapping
{
    protected $label = 'Resource data'; // @translate
    protected $resourceType = 'resources';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/item')
            . $view->partial('csv-import/mapping-sidebar/item-set')
            . $view->partial('csv-import/mapping-sidebar/media')
            . parent::getSidebar($view);
    }

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();
        parent::processGlobalArgsItemSet();
        parent::processGlobalArgsItem();
        parent::processGlobalArgsMedia();

        $data = &$this->data;

        if (isset($this->args['column-resource_type'])) {
            $this->map['resourceType'] = $this->args['column-resource_type'];
            $data['resource_type'] = null;
        }
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);
        parent::processCellItemSet($index, $values);
        parent::processCellItem($index, $values);
        parent::processCellMedia($index, $values);

        $data = &$this->data;

        if (isset($this->map['resourceType'][$index])) {
            $resourceType = reset($values);
            // Add some heuristic to avoid common issues.
            $resourceType = str_replace([' ', '_'], '', strtolower($resourceType));
            $resourceTypes = [
                'itemsets' => 'item_sets',
                'itemset' => 'item_sets',
                'items' => 'items',
                'item' => 'items',
                'medias' => 'media',
                'media' => 'media',
                // Add some compatibility with old csv files for Omeka Classic.
                'collections' => 'item_sets',
                'collection' => 'item_sets',
                'files' => 'media',
                'file' => 'media',
            ];
            if (isset($resourceTypes[$resourceType])) {
                $data['resource_type'] = $resourceTypes[$resourceType];
            } else {
                $this->logger->err(new Message('"%s" is not a valid resource type.', reset($values))); // @translate
                $this->setHasErr(true);
            }
        }
    }
}
