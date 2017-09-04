<?php

namespace CSVImport\Form;

use CSVImport\Job\Import;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Form;
use Zend\Form\Element\Select;

class MappingForm extends Form
{
    protected $serviceLocator;

    public function init()
    {
        $resourceType = $this->getOption('resourceType');
        $serviceLocator = $this->getServiceLocator();
        $currentUser = $serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        $acl = $serviceLocator->get('Omeka\Acl');

        $this->add([
            'name' => 'comment',
            'type' => 'textarea',
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'A note about the purpose or source of this import', // @translate
            ],
            'attributes' => [
                'id' => 'comment',
                'class' => 'input-body',
            ],
        ]);

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
                    'label' => 'Resource template', // @translate
                    'info' => 'A pre-defined template for resource creation', // @translate
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
                                $resourceClass->label(),
                            ];
                        },
                    ],
                ],
            ]);

            if ($resourceType == 'items') {
                $this->add([
                    'name' => 'o:item_set',
                    'type' => ResourceSelect::class,
                    'attributes' => [
                        'id' => 'select-item-set',
                        'required' => false,
                        'multiple' => true,
                        'data-placeholder' => 'Select item sets', // @translate
                    ],
                    'options' => [
                        'label' => 'Item sets', // @translate
                        'info' => 'Select Item sets for this resource', // @translate
                        'resource_value_options' => [
                            'resource' => 'item_sets',
                            'query' => [],
                            'option_text_callback' => function ($itemSet) {
                                return $itemSet->displayTitle();
                            },
                        ],
                    ],
                ]);
            }
            if ($acl->userIsAllowed('Omeka\Entity\Item', 'change-owner')) {
                $this->add([
                    'name' => 'o:owner',
                    'type' => ResourceSelect::class,
                    'attributes' => [
                        'id' => 'select-owner',
                        'value' => $currentUser->getId(),
                        ],
                    'options' => [
                        'label' => 'Owner', // @translate
                        'resource_value_options' => [
                            'resource' => 'users',
                            'query' => [],
                            'option_text_callback' => function ($user) {
                                return $user->name();
                            },
                            ],
                        ],
                ]);
            }

            $this->add([
                'name' => 'multivalue-separator',
                'type' => 'text',
                'options' => [
                    'label' => 'Multivalue separator', // @translate
                    'info' => 'The separator to use for columns with multiple values', // @translate
                ],
                'attributes' => [
                    'id' => 'multivalue-separator',
                    'class' => 'input-body',
                    'value' => ',',
                ],
            ]);

            $this->add([
                'name' => 'global-language',
                'type' => 'text',
                'options' => [
                    'label' => 'Language', // @translate
                    'info' => 'Language setting to apply to all imported literal data. Individual property mappings can override the setting here.', // @translate
                ],
                'attributes' => [
                    'id' => 'global-language',
                    'class' => 'input-body value-language',
                    'value' => '',
                ],
            ]);

            $valueOptions = [
                Import::ACTION_CREATE => 'Create a new resource', // @translate
                Import::ACTION_UPDATE => 'Update the resource if it exists, else skip the row', // @translate
                Import::ACTION_UPDATE_ELSE_CREATE => 'Update the resource if it exists, else create one', // @translate
                Import::ACTION_DELETE => 'Delete the resource', // @translate
                Import::ACTION_SKIP => 'Skip process of the resource', // @translate
            ];
            $this->add([
                'name' => 'action',
                'type' => 'select',
                'options' => [
                    'label' => 'Action', // @translate
                    'info' => 'The default action depends on the identifier option: if set, it’s "Update if it exists, else create", else it’s "Create".', // @translate
                    'empty_option' => 'Select below', // @translate
                    'value_options' => $valueOptions,
                ],
                'attributes' => [
                    'id' => 'action',
                ],
            ]);

            $this->add([
                'name' => 'identifier_property',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Resource identifier property', // @translate
                    'info' => 'Use this property, generally "dcterms:identifier", to identify the existing resources, so it will be possible to update them.' // @translate
                        . ' ' . 'One column of the file must map the selected property.' // @translate
                        . ' ' . 'In all cases, it is strongly recommended to add one ore more unique identifiers to all your resources.', // @translate
                    'empty_option' => 'Select below', // @translate
                    'term_as_value' => false,
                    'prepend_value_options' => [
                        'internal_id' => 'Internal id', // @translate
                    ]
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property', // @translate
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
            $inputFilter->add([
                'name' => 'o:item_set',
                'required' => false,
            ]);
            $inputFilter->add([
                'name' => 'action',
                'required' => false,
            ]);
            $inputFilter->add([
                'name' => 'identifier_property',
                'required' => false,
            ]);
        }
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
