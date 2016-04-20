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
        $currentUser = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        $userRole = $currentUser->getRole();
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
        
        if( ($userRole == 'global_admin') || ($userRole == 'site_admin')) {
            $ownerSelect = new ResourceSelect($this->getServiceLocator());
            $ownerSelect->setName('o:owner')
                ->setAttribute('id', 'select-owner')
                ->setLabel($translator->translate('Owner'))
                ->setOption('info', $translator->translate('Assign ownership'))
                ->setResourceValueOptions(
                    'users',
                    [],
                    function ($user, $serviceLocator) {
                        return $user->name();
                    }
                );
            $ownerSelect->setValue($currentUser->getId());
            
            $this->add($ownerSelect);
        }
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