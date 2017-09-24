<?php
namespace CSVImport\Mapping;

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

        if (!empty($this->args['o:item_set'])) {
            $itemSets = $this->args['o:item_set'];
            $data['o:item_set'] = [];
            foreach ($itemSets as $itemSetId) {
                $data['o:item_set'][] = ['o:id' => $itemSetId];
            }
        }
        $this->map['itemSet'] = isset($this->args['column-item_set'])
            ? $this->args['column-item_set']
            : [];
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
                    $this->logger->err(sprintf('"%s" (%s) is not a valid item set.', // @translate
                        $identifier, $identifierProperty));
                    $this->setHasErr(true);
                }
            }
        }
    }
}
