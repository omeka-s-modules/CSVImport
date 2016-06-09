<?php
namespace CSVImport\Form;

use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Form;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends Form
{
    protected $currentUser;
    
    protected $serviceLocator;
    
    public function init()
    {
        $resourceType = $this->getOption('resourceType');
        $serviceLocator = $this->getServiceLocator();
        $currentUser = $serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
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
            $urlHelper = $serviceLocator->get('ViewHelperManager')->get('url');
            $this->add([
                'name' => 'o:resource_template[o:id]',
                'type' => ResourceSelect::class,
                'attributes' => [
                    'id' => 'resource-template-select',
                    'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
                ],
                'options' => [
                    'label' => 'Resource Template', // @translate
                    'info' => 'A pre-defined template for resource creation.', // @translate
                    'empty_option' => 'Select Template', // @translate
                    'resource_value_options' => [
                        'resource' => 'resource_templates',
                        'query' => [],
                        'option_text_callback' => function ($resourceTemplate) {
                            return $resourceTemplate->label();
                        },
                    ],
                ],
            ]);
    
            $this->add([
                'name' => 'o:resource_class[o:id]',
                'type' => ResourceSelect::class,
                'attributes' => [
                    'id' => 'resource-class-select',
                ],
                'options' => [
                    'label' => 'Class', // @translate
                    'info' => 'A type for the resource. Different types have different default properties attached to them.', // @translate
                    'empty_option' => 'Select Class', // @translate
                    'resource_value_options' => [
                        'resource' => 'resource_classes',
                        'query' => [],
                        'option_text_callback' => function ($resourceClass) {
                            return [
                                $resourceClass->vocabulary()->label(),
                                $resourceClass->label()
                            ];
                        },
                    ],
                ],
            ]);
                        
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

            $this->add([
                'name' => 'o:item_set',
                'type' => ResourceSelect::class,
                'attributes' => [
                    'id'       => 'select-item-set',
                    'required' => false,
                    'multiple' => true,
                    'data-placeholder' => 'Select Item Sets', // @translate
                ],
                'options' => [
                    'label' => 'Item Sets', // @translate
                    'info' => 'Select Items Sets for this resource.', // @translate
                    'empty_option' => 'Select Class', // @translate
                    'resource_value_options' => [
                        'resource' => 'item_sets',
                        'query' => [],
                        'option_text_callback' => function ($itemSet) {
                            return $itemSet->displayTitle();
                        },
                    ],
                ],
            ]);
/*
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
*/
            if( $acl->userIsAllowed('Omeka\Entity\Item', 'change-owner') ) {
                            
                $this->add([
                    'name'       => 'o:owner',
                    'type'       => ResourceSelect::class,
                    'attributes' => [
                        'id' => 'select-owner'
                        ],
                    'options'    => [
                        'label' => 'Owner', // @translate
                        'resource_value_options' => [
                            'resource' => 'users',
                            'query'    => [],
                            'option_text_callback' => function ($user) {
                                return $user->name();
                                }
                            ]
                        ],
                        
                ]);
                            
/*
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
                */
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
        
    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    public function setCurrentUser($currentUser)
    {
        $this->currentUser = $currentUser;
    }
    
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

}
