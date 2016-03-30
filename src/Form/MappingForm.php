<?php
namespace CSVImport\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends AbstractForm
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
            )
        ));
        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $itemSetSelect = new ResourceSelect($serviceLocator);
        $itemSetSelect->setAttribute('multiple', true);
        $itemSetSelect->setAttribute('id', 'item-set-select');
        $itemSetSelect->setName('itemSet')
            ->setLabel('Import into')
            ->setOption('info', $translator->translate('Optional. Import items into this item set.'))
            ->setEmptyOption('Select Item Set(s)...')
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $auth->getIdentity()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]');
                }
            );
        $this->add($itemSetSelect);
        
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
        
        
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'itemSet',
            'required' => false,
        ));
    }
}