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
            //in theory this _should_ be the correct way to make this 1st, 
            //instead of putting it before the parent build
            //but not so much, so the parent comes after
            array('priority' => 100000) 
        ));
        
        parent::buildForm();
        
        $this->add(array(
            'name' => 'multivalue-separator',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Multivalue Separator'),
                'info'  => $translator->translate('The separator to use for columns with multiple values.')
            ),
            'attributes' => array(
                'id' => 'multivalue-separator',
                'value' => ','
            )
        ));
        
        $itemSetSelect = $this->get('o:item_set');
        $itemSetSelect->setAttribute('multiple', true);

    }
}