<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

class ItemSetMapping extends ResourceMapping
{
    public static function getLabel()
    {
        return 'Item set data'; // @translate
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->resourceSidebar('item_sets');
    }

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();

        $data = &$this->data;

        if (isset($this->args['o:is_open']) && strlen($this->args['o:is_open'])) {
            $isOpen = $this->args['o:is_open'];
            $data['o:is_open'] = (int) (bool) $isOpen;
        }
        $this->map['isOpen'] = isset($this->args['column-is_open'])
            ? array_keys($this->args['column-is_open'])
            : [];
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (in_array($index, $this->map['isOpen'])) {
            $value = reset($values);
            $data['o:is_open'] = in_array(strtolower($value), ['false', 'off', 'closed'])
                ? 0
                : (int) (bool) $value;
        }
    }
}
