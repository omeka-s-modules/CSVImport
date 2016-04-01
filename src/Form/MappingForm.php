<?php
namespace CSVImport\Form;

use Omeka\Form\ItemForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends ItemForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
                'info'  => $translator->translate('A note about the purpose or source of this import.')
            ),
            'attributes' => array(
                'id' => 'comment'
            ),
            array('priority' => 100000)
        ));
        
        parent::buildForm();
        
        $itemSetSelect = $this->get('o:item_set');
        $itemSetSelect->setAttribute('multiple', true);

    }
}