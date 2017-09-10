<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

class ItemMapping extends ResourceMapping
{
    public static function getLabel()
    {
        return 'Item data'; // @translate
    }

    public static function getName()
    {
        return 'item-data';
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->itemSidebar();
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
        $this->map['itemSet'] = isset($this->args['column-itemset-id'])
            ? array_keys($this->args['column-itemset-id'])
            : [];
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (in_array($index, $this->map['itemSet'])) {
            foreach ($values as $itemSetId) {
                $itemSet = $this->findItemSet($itemSetId);
                if ($itemSet) {
                    $data['o:item_set'][] = ['o:id' => $itemSetId];
                }
            }
        }
    }

    protected function findItemSet($itemSetId)
    {
        $response = $this->api->search('item_sets', ['id' => $itemSetId]);
        $content = $response->getContent();
        if (empty($content)) {
            $this->logger->err(sprintf('"%s" is not a valid item set.', $itemSetId));
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }
}
