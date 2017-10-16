<?php
namespace CSVImport\Mapping;

use Omeka\Stdlib\Message;
use Zend\View\Renderer\PhpRenderer;

class ItemMapping extends ResourceMapping
{
    public static function getLabel()
    {
        return 'Item data'; // @translate
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->resourceSidebar('items');
    }

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();

        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-item_set'])) {
            $this->map['itemSet'] = $this->args['column-item_set'];
            $data['o:item_set'] = [];
        }

        // Set default values.
        if (!empty($this->args['o:item_set'])) {
            $data['o:item_set'] = [];
            foreach ($this->args['o:item_set'] as $id) {
                $data['o:item_set'][] = ['o:id' => (int) $id];
            }
        }
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (isset($this->map['itemSet'][$index])) {
            $identifierProperty = $this->map['itemSet'][$index];
            $resourceType = 'item_sets';
            $findResourceFromIdentifier = $this->findResourceFromIdentifier;
            foreach ($values as $identifier) {
                $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
                if ($resourceId) {
                    $data['o:item_set'][] = ['o:id' => $resourceId];
                } else {
                    $this->logger->err(new Message('"%s" (%s) is not a valid item set.', // @translate
                        $identifier, $identifierProperty));
                    $this->setHasErr(true);
                }
            }
        }
    }
}
