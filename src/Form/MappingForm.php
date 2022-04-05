<?php
namespace CSVImport\Form;

use CSVImport\Job\Import;
use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;
use Omeka\Form\Element\SiteSelect;
use Laminas\Form\Form;

class MappingForm extends Form
{
    protected $serviceLocator;

    public function init()
    {
        $resourceType = $this->getOption('resource_type');
        $serviceLocator = $this->getServiceLocator();
        $userSettings = $serviceLocator->get('Omeka\Settings\User');
        $config = $serviceLocator->get('CSVImport\Config');
        $default = $config['user_settings'];
        $acl = $serviceLocator->get('Omeka\Acl');

        $this->add([
            'name' => 'resource_type',
            'type' => 'hidden',
            'attributes' => [
                'value' => $resourceType,
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'delimiter',
            'type' => 'hidden',
            'attributes' => [
                'value' => $this->getOption('delimiter'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'enclosure',
            'type' => 'hidden',
            'attributes' => [
                'value' => $this->getOption('enclosure'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'comment',
            'type' => 'hidden',
            'attributes' => [
                'value' => $this->getOption('comment'),
            ],
        ]);

        $this->add([
            'name' => 'automap_check_names_alone',
            'type' => 'hidden',
            'attributes' => [
                'value' => $this->getOption('automap_check_names_alone'),
                'required' => true,
            ],
        ]);

        $this->add([
            'type' => 'fieldset',
            'name' => 'basic-settings',
            'attributes' => [
                'id' => 'csv-import-basics-fieldset',
                'class' => 'section',
            ],
        ]);

        $basicSettingsFieldset = $this->get('basic-settings');

        if (in_array($resourceType, ['item_sets', 'items', 'media', 'resources'])) {
            $urlHelper = $serviceLocator->get('ViewHelperManager')->get('url');
            $basicSettingsFieldset->add([
                'name' => 'o:resource_template',
                'type' => ResourceSelect::class,
                'options' => [
                    'label' => 'Resource template', // @translate
                    'info' => 'Assign a resource template to all imported resources. Specific mappings can override this setting.', // @translate
                    'empty_option' => 'Select a template', // @translate
                    'resource_value_options' => [
                        'resource' => 'resource_templates',
                        'query' => [],
                        'option_text_callback' => function ($resourceTemplate) {
                            return $resourceTemplate->label();
                        },
                    ],
                ],
                'attributes' => [
                    'id' => 'resource-template-select',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a template', // @translate
                    'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
                ],
            ]);

            $basicSettingsFieldset->add([
                'name' => 'o:resource_class',
                'type' => ResourceClassSelect::class,
                'options' => [
                    'label' => 'Class', // @translate
                    'info' => 'Assign a resource class to all imported resources. Specific mappings can override this setting.', // @translate
                    'empty_option' => 'Select a class', // @translate
                ],
                'attributes' => [
                    'id' => 'resource-class-select',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a class', // @translate
                ],
            ]);

            if (($resourceType === 'item_sets' && $acl->userIsAllowed('Omeka\Entity\ItemSet', 'change-owner'))
                || ($resourceType === 'items' && $acl->userIsAllowed('Omeka\Entity\Item', 'change-owner'))
                || ($resourceType === 'media' && $acl->userIsAllowed('Omeka\Entity\Media', 'change-owner'))
                // No rule for resources, so use item.
                || ($resourceType === 'resources' && $acl->userIsAllowed('Omeka\Entity\Item', 'change-owner'))
            ) {
                $basicSettingsFieldset->add([
                    'name' => 'o:owner',
                    'type' => ResourceSelect::class,
                    'options' => [
                        'label' => 'Owner', // @translate
                        'info' => 'If not set, the default owner for new resources will be the current user.', // @translate
                        'resource_value_options' => [
                            'resource' => 'users',
                            'query' => [],
                            'option_text_callback' => function ($user) {
                                return sprintf('%s (%s)', $user->email(), $user->name());
                            },
                        ],
                        'empty_option' => 'Select a user', // @translate
                    ],
                    'attributes' => [
                        'id' => 'select-owner',
                        'value' => '',
                        'class' => 'chosen-select',
                        'data-placeholder' => 'Select a user', // @translate
                        'data-api-base-url' => $urlHelper('api/default', ['resource' => 'users']),
                    ],
                ]);
            }

            $basicSettingsFieldset->add([
                'name' => 'o:is_public',
                'type' => 'radio',
                'options' => [
                    'label' => 'Visibility', // @translate
                    'info' => 'Set visibility for all imported resources. Specific mappings can override this setting.', // @translate
                    'value_options' => [
                        '1' => 'Public', // @translate
                        '0' => 'Private', // @translate
                    ],
                ],
                'attributes' => [
                    'value' => '1',
                ],
            ]);

            switch ($resourceType) {
                case 'item_sets':
                    $basicSettingsFieldset->add([
                        'name' => 'o:is_open',
                        'type' => 'radio',
                        'options' => [
                            'label' => 'Open/closed to additions', // @translate
                            'info' => 'Set whether imported item sets are open to additions. Specific mappings can override this setting.', // @translate
                            'value_options' => [
                                '1' => 'Open', // @translate
                                '0' => 'Closed', // @translate
                            ],
                        ],
                    ]);
                    break;

                case 'items':
                    $basicSettingsFieldset->add([
                        'name' => 'o:item_set',
                        'type' => ItemSetSelect::class,
                        'attributes' => [
                            'id' => 'select-item-set',
                            'class' => 'chosen-select',
                            'multiple' => true,
                            'data-placeholder' => 'Select item sets', // @translate
                        ],
                        'options' => [
                            'label' => 'Item sets', // @translate
                            'resource_value_options' => [
                                'resource' => 'item_sets',
                                'query' => [],
                            ],
                        ],
                    ]);

                    $basicSettingsFieldset->add([
                        'name' => 'o:site',
                        'type' => SiteSelect::class,
                        'attributes' => [
                            'id' => 'select-sites',
                            'class' => 'chosen-select',
                            'multiple' => true,
                            'data-placeholder' => 'Select sites', // @translate
                            'value' => $this->getDefaultSiteIds(),
                        ],
                        'options' => [
                            'label' => 'Sites', // @translate
                        ],
                    ]);
                    break;

                case 'resources':
                    $basicSettingsFieldset->add([
                        'name' => 'o:is_open',
                        'type' => 'radio',
                        'options' => [
                            'label' => 'Item sets open/closed to additions', // @translate
                            'info' => 'Set whether imported item sets are open to additions. Specific mappings can override this setting.', // @translate
                            'value_options' => [
                                '1' => 'Open', // @translate
                                '0' => 'Closed', // @translate
                            ],
                        ],
                    ]);

                    $basicSettingsFieldset->add([
                        'name' => 'o:item_set',
                        'type' => ItemSetSelect::class,
                        'attributes' => [
                            'id' => 'select-item-set',
                            'class' => 'chosen-select',
                            'multiple' => true,
                            'data-placeholder' => 'Select item sets', // @translate
                        ],
                        'options' => [
                            'label' => 'Item sets for items', // @translate
                            'resource_value_options' => [
                                'resource' => 'item_sets',
                                'query' => [],
                            ],
                        ],
                    ]);

                    $basicSettingsFieldset->add([
                        'name' => 'o:site',
                        'type' => SiteSelect::class,
                        'attributes' => [
                            'id' => 'select-sites',
                            'class' => 'chosen-select',
                            'multiple' => true,
                            'data-placeholder' => 'Select sites', // @translate
                            'value' => $this->getDefaultSiteIds(),
                        ],
                        'options' => [
                            'label' => 'Sites for items', // @translate
                        ],
                    ]);
                    break;
            }

            $basicSettingsFieldset->add([
                'name' => 'multivalue_separator',
                'type' => 'text',
                'options' => [
                    'label' => 'Multivalue separator', // @translate
                    'info' => 'The separator to use for columns with multiple values', // @translate
                ],
                'attributes' => [
                    'id' => 'multivalue_separator',
                    'class' => 'input-body',
                    'value' => $userSettings->get(
                        'csv_import_multivalue_separator',
                        $default['csv_import_multivalue_separator']),
                ],
            ]);

            $basicSettingsFieldset->add([
                'name' => 'global_language',
                'type' => 'text',
                'options' => [
                    'label' => 'Language', // @translate
                    'info' => 'Language setting to apply to all imported literal data. Individual property mappings can override the setting here.', // @translate
                ],
                'attributes' => [
                    'id' => 'global_language',
                    'class' => 'input-body value-language',
                    'value' => $userSettings->get(
                        'csv_import_global_language',
                        $default['csv_import_global_language']),
                ],
            ]);

            $this->add([
                'type' => 'fieldset',
                'name' => 'advanced-settings',
                'attributes' => [
                    'id' => 'csv-import-advanced-fieldset',
                    'class' => 'section',
                ],
            ]);

            $advancedSettingsFieldset = $this->get('advanced-settings');

            $valueOptions = [
                Import::ACTION_CREATE => 'Create a new resource', // @translate
                Import::ACTION_APPEND => 'Append data to the resource', // @translate
                Import::ACTION_REVISE => 'Revise data of the resource', // @translate
                Import::ACTION_UPDATE => 'Update data of the resource', // @translate
                Import::ACTION_REPLACE => 'Replace all data of the resource', // @translate
                Import::ACTION_DELETE => 'Delete the resource', // @translate
            ];

            $advancedSettingsFieldset->add([
                'name' => 'action',
                'type' => 'select',
                'options' => [
                    'label' => 'Action', // @translate
                    'info' => 'By default, an import creates new resources. Select from this dropdown to choose an alternate action for the import. For more information on each action, see the documentation.', // @translate
                    'value_options' => $valueOptions,
                ],
                'attributes' => [
                    'id' => 'action',
                ],
            ]);

            $columns = $this->getOption('columns');
            if ($columns) {
                $advancedSettingsFieldset->add([
                    'name' => 'identifier_column',
                    'type' => 'Select',
                    'options' => [
                        'label' => 'Resource identifier column', // @translate
                        'value_options' => $this->getOption('columns'),
                    ],
                    'attributes' => [
                        'id' => 'identifier_column',
                        'class' => 'action-option',
                    ],
                ]);
            } else {
                $advancedSettingsFieldset->add([
                    'name' => 'identifier_column',
                    'type' => 'Number',
                ]);
            }

            $advancedSettingsFieldset->add([
                'name' => 'identifier_property',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Resource identifier property', // @translate
                    'info' => 'Use this property, generally "dcterms:identifier", to identify the existing resources, so it will be possible to update them. One column of the file must map the selected property. In all cases, it is strongly recommended to add one ore more unique identifiers to all your resources.', // @translate
                    'empty_option' => 'Select below', // @translate
                    'prepend_value_options' => [
                        'internal_id' => 'Internal ID', // @translate
                    ],
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'value' => $userSettings->get(
                        'csv_import_identifier_property',
                        $default['csv_import_identifier_property']),
                    'class' => 'action-option chosen-select',
                    'data-placeholder' => 'Select a property', // @translate
                ],
            ]);

            $advancedSettingsFieldset->add([
                'name' => 'action_unidentified',
                'type' => 'radio',
                'options' => [
                    'label' => 'Action on unidentified resources', // @translate
                    'info' => 'This option determines what to do when a resource does not exist but the action applies to an existing resource ("Append", "Update", or "Replace"). This option is not used when the main action is "Create", "Delete" or "Skip".', // @translate
                    'value_options' => [
                        Import::ACTION_SKIP => 'Skip the row', // @translate
                        Import::ACTION_CREATE => 'Create a new resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'action_unidentified',
                    'class' => 'action-option',
                    'value' => Import::ACTION_SKIP,
                ],
            ]);

            $advancedSettingsFieldset->add([
                'name' => 'rows_by_batch',
                'type' => 'Number',
                'options' => [
                    'label' => 'Number of rows to process by batch', // @translate
                    'info' => 'By default, rows are processed by 20. In some cases, to set a value of 1 may avoid issues.', // @translate
                ],
                'attributes' => [
                    'value' => $userSettings->get(
                        'csv_import_rows_by_batch',
                        $default['csv_import_rows_by_batch']),
                    'min' => '1',
                    'step' => '1',
                ],
            ]);

            $inputFilter = $this->getInputFilter();
            $basicSettingsInputFilter = $inputFilter->get('basic-settings');
            $basicSettingsInputFilter->add([
                'name' => 'o:resource_template',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:resource_class',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:owner',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:is_public',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:is_open',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:item_set',
                'required' => false,
            ]);
            $basicSettingsInputFilter->add([
                'name' => 'o:site',
                'required' => false,
            ]);

            $advancedSettingsInputFilter = $inputFilter->get('advanced-settings');
            $advancedSettingsInputFilter->add([
                'name' => 'action',
                'required' => false,
            ]);
            $advancedSettingsInputFilter->add([
                'name' => 'identifier_column',
                'required' => false,
            ]);
            $advancedSettingsInputFilter->add([
                'name' => 'identifier_property',
                'required' => false,
            ]);
            $advancedSettingsInputFilter->add([
                'name' => 'action_unidentified',
                'required' => false,
            ]);
        }
    }

    private function getDefaultSiteIds()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $userSettings = $serviceLocator->get('Omeka\Settings\User');

        $globalDefaultSites = $api->search('sites', ['assign_new_items' => true])->getContent();
        $userDefaultSiteIds = $userSettings->get('default_item_sites', []);
        $globalDefaultSiteIds = [];
        foreach ($globalDefaultSites as $siteRepresentation) {
            $globalDefaultSiteIds[] = $siteRepresentation->id();
        }
        return array_merge($userDefaultSiteIds, $globalDefaultSiteIds);
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
