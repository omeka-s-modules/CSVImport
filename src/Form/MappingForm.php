<?php
namespace CSVImport\Form;

use Omeka\Form\Form;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends Form
{
    public function buildForm()
    {
        $resourceType = $this->getOption('resourceType');
        $currentUser = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        $serviceLocator = $this->getServiceLocator();
        $acl = $serviceLocator->get('Omeka\Acl');
        
        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Comment', // @translate
                'info'  => 'A note about the purpose or source of this import.' // @translate
            ),
            'attributes' => array(
                'id' => 'comment',
                'class' => 'input-body',
            ),
        ));
        
        if ($resourceType == 'items' || $resourceType == 'item_sets') {
            $url = $serviceLocator->get('ViewHelperManager')->get('url');
            $templateSelect = new ResourceSelect($serviceLocator);
            $templateSelect
                ->setName('o:resource_template[o:id]')
                ->setAttribute('id', 'resource-template-select')
                ->setAttribute('data-api-base-url', $url('api/default',
                    ['resource' => 'resource_templates']))
                ->setLabel('Resource Template') // @translate
                ->setEmptyOption('Select Template') // @translate
                ->setOption('info', 'A pre-defined template for resource creation.') // @translate
                ->setResourceValueOptions(
                    'resource_templates',
                    [],
                    function ($template, $serviceLocator) {
                        return $template->label();
                    }
                );
                $this->add($templateSelect);
                $classSelect = new ResourceSelect($serviceLocator);
                $classSelect
                    ->setName('o:resource_class[o:id]')
                    ->setAttribute('id', 'resource-class-select')
                    ->setLabel('Class') // @translate
                    ->setEmptyOption('Select Class') // @translate
                    ->setOption('info', 'A type for the resource. Different types have different default properties attached to them.') // @translate
                    ->setResourceValueOptions(
                        'resource_classes',
                        [],
                        function ($resourceClass, $serviceLocator) {
                            return [
                                $resourceClass->vocabulary()->label(),
                                $resourceClass->label()
                            ];
                        }
                    );
                $this->add($classSelect);
                $inputFilter = $this->getInputFilter();
                $inputFilter->add([
                    'name' => 'o:resource_template[o:id]',
                    'required' => false,
                ]);
                $inputFilter->add([
                    'name' => 'o:resource_class[o:id]',
                    'required' => false,
                ]);
            }

            $itemSetSelect = new ResourceSelect($serviceLocator);
            $itemSetSelect->setName('o:item_set')
                ->setAttribute('required', false)
                ->setAttribute('multiple', true)
                ->setAttribute('id', 'select-item-set')
                ->setAttribute('data-placeholder', 'Select Item Sets') // @translate
                ->setLabel('Item Sets') // @translate
                ->setOption('info', 'Select Items Sets for this resource.') // @translate
                ->setResourceValueOptions(
                    'item_sets',
                    [],
                    function ($itemSet, $serviceLocator) {
                        return $itemSet->displayTitle();
                    }
                );
            if (!$itemSetSelect->getValueOptions()) {
                $itemSetSelect->setAttribute('disabled', true);
                $itemSetSelect->setAttribute('data-placeholder', 'No item sets exist'); // @translate
            }
            $this->add($itemSetSelect);

            if( $acl->userIsAllowed('Omeka\Entity\Item', 'change-owner') ) {
                $ownerSelect = new ResourceSelect($this->getServiceLocator());
                $ownerSelect->setName('o:owner')
                    ->setAttribute('id', 'select-owner')
                    ->setLabel('Owner') // @translate
                    ->setOption('info', 'Assign ownership') // @translate
                    ->setResourceValueOptions(
                        'users',
                        [],
                        function ($user, $serviceLocator) {
                            return $user->name();
                        }
                    );
                $this->add($ownerSelect);
            }

            $this->add(array(
                'name' => 'multivalue-separator',
                'type' => 'text',
                'options' => array(
                    'label' => 'Multivalue Separator', // @translate
                    'info'  => 'The separator to use for columns with multiple values.' // @translate
                ),
                'attributes' => array(
                    'id' => 'multivalue-separator',
                    'class' => 'input-body',
                    'value' => ','
                )
            ));
        }
}
