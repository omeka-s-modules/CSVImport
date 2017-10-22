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

        // Set columns.
        if (isset($this->args['column-is_open'])) {
            $this->map['isOpen'] = $this->args['column-is_open'];
            $data['o:is_open'] = null;
        }

        // Set default values.
        if (isset($this->args['o:is_open']) && strlen($this->args['o:is_open'])) {
            $data['o:is_open'] = (bool) $this->args['o:is_open'];
        }
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (isset($this->map['isOpen'][$index])) {
            $value = reset($values);
            if (strlen($value)) {
                $data['o:is_open'] = in_array(strtolower($value), ['false', 'no', 'off', 'closed'])
                    ? false
                    : (bool) $value;
            }
        }
    }
}
