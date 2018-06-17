<?php
namespace CSVImport\Form;

use CSVImport\Job\Import;
use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;

class MappingForm extends Form
{
    use EventManagerAwareTrait;

    protected $inputFilter;
    protected $resourceType;
    protected $urlHelper;
    protected $serviceLocator;

    public function init()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $this->urlHelper = $services->get('ViewHelperManager')->get('url');

        $this->inputFilter = $this->getInputFilter();
        $this->resourceType = $this->getOption('resource_type');

        $this->addCommonElements();

        switch ($this->resourceType) {
            case 'item_sets':
                $this->addResourceElements();
                if ($acl->userIsAllowed(\Omeka\Entity\ItemSet::class, 'change-owner')) {
                    $this->addOwnerElement();
                }
                $this->addItemSetElements();
                $this->addProcessElements();
                $this->addAdvancedElements();
                break;
            case 'items':
                $this->addResourceElements();
                if ($acl->userIsAllowed(\Omeka\Entity\Item::class, 'change-owner')) {
                    $this->addOwnerElement();
                }
                $this->addItemElements();
                $this->addProcessElements();
                $this->addAdvancedElements();
                break;
            case 'media':
                $this->addResourceElements();
                if ($acl->userIsAllowed(\Omeka\Entity\Media::class, 'change-owner')) {
                    $this->addOwnerElement();
                }
                $this->addMediaElements();
                $this->addProcessElements();
                $this->addAdvancedElements();
                break;
            case 'resources':
                $this->addResourceElements();
                // No rule for resources, so use item.
                if ($acl->userIsAllowed(\Omeka\Entity\Item::class, 'change-owner')) {
                    $this->addOwnerElement();
                }
                $this->addResourceGenericElements();
                $this->addProcessElements();
                $this->addAdvancedElements();
                break;
            case 'users':
                break;
        }

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $this->inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }

    public function addCommonElements()
    {
        $this->add([
            'name' => 'resource_type',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->resourceType,
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'delimiter',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->getOption('delimiter'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'enclosure',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->getOption('enclosure'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'automap_check_names_alone',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->getOption('automap_check_names_alone'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'automap_check_user_list',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->getOption('automap_check_user_list'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'automap_user_list',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => $this->getOption('automap_user_list'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'comment',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'A note about the purpose or source of this import', // @translate
            ],
            'attributes' => [
                'id' => 'comment',
                'class' => 'input-body',
            ],
        ]);
    }

    public function addResourceElements()
    {
        $urlHelper = $this->urlHelper;

        $this->add([
            'name' => 'o:resource_template[o:id]',
            'type' => ResourceSelect::class,
            'options' => [
                'label' => 'Resource template', // @translate
                'info' => 'A pre-defined template for resource creation', // @translate
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

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Class', // @translate
                'info' => 'A type for the resource. Different types have different default properties attached to them.', // @translate
                'empty_option' => 'Select a class', // @translate
            ],
            'attributes' => [
                'id' => 'resource-class-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:is_public',
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Visibility', // @translate
                'info' => 'The default visibility is private if the cell contains "0", "false", "no", "off" or "private" (case insensitive), else it is public.', // @translate
                'value_options' => [
                    '1' => 'Public', // @translate
                    '0' => 'Private', // @translate
                ],
            ],
        ]);

        $this->inputFilter->add([
            'name' => 'o:resource_template[o:id]',
            'required' => false,
        ]);
        $this->inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'required' => false,
        ]);
        $this->inputFilter->add([
            'name' => 'o:is_public',
            'required' => false,
        ]);
    }

    public function addOwnerElement()
    {
        $urlHelper = $this->urlHelper;

        $this->add([
            'name' => 'o:owner[o:id]',
            'type' => ResourceSelect::class,
            'options' => [
                'label' => 'Owner', // @translate
                'info' => 'If not set, the default owner will be the current user for a creation.', // @translate
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

        $this->inputFilter->add([
            'name' => 'o:owner[o:id]',
            'required' => false,
        ]);
    }

    public function addItemSetElements()
    {
        $this->add([
            'name' => 'o:is_open',
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Open/closed to additions', // @translate
                'info' => 'The default openess is closed if the cell contains "0", "false", "off", or "closed" (case insensitive), else it is open.', // @translate
                'value_options' => [
                    '1' => 'Open', // @translate
                    '0' => 'Closed', // @translate
                ],
            ],
        ]);

        $this->inputFilter->add([
            'name' => 'o:is_open',
            'required' => false,
        ]);
    }

    public function addItemElements()
    {
        $this->add([
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

        $this->inputFilter->add([
            'name' => 'o:item_set',
            'required' => false,
        ]);
    }

    public function addMediaElements()
    {
    }

    public function addResourceGenericElements()
    {
        $this->add([
            'name' => 'o:is_open',
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Item sets open/closed to additions', // @translate
                'info' => 'The default openess is closed if the cell contains "0", "false", "off", or "closed" (case insensitive), else it is open.', // @translate
                'value_options' => [
                    '1' => 'Open', // @translate
                    '0' => 'Closed', // @translate
                ],
            ],
        ]);

        $this->add([
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

        $this->inputFilter->add([
            'name' => 'o:is_open',
            'required' => false,
        ]);
        $this->inputFilter->add([
            'name' => 'o:item_set',
            'required' => false,
        ]);
    }

    public function addProcessElements()
    {
        $services = $this->getServiceLocator();
        $userSettings = $services->get('Omeka\Settings\User');
        $config = $services->get('Config');
        $default = $config['csv_import']['user_settings'];

        $this->add([
            'name' => 'multivalue_separator',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Multivalue separator', // @translate
                'info' => 'The separator to use for columns with multiple values', // @translate
            ],
            'attributes' => [
                'id' => 'multivalue_separator',
                'class' => 'input-body',
                'value' => $userSettings->get(
                    'csvimport_multivalue_separator',
                    $default['csvimport_multivalue_separator']),
            ],
        ]);

        $this->add([
            'name' => 'multivalue_by_default',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Use the multivalue separator for all columns', // @translate
                'info' => 'Allows to set/unset all columns multivalued by default.', // @translate
            ],
            'attributes' => [
                'id' => 'multivalue_by_default',
                'value' => (int) (bool) $userSettings->get(
                    'csvimport_multivalue_by_default',
                    $default['csvimport_multivalue_by_default']),
            ],
        ]);

        $this->add([
            'name' => 'global_language',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Language', // @translate
                'info' => 'Language setting to apply to all imported literal data. Individual property mappings can override the setting here.', // @translate
            ],
            'attributes' => [
                'id' => 'global_language',
                'class' => 'input-body value-language',
                'value' => $userSettings->get(
                    'csvimport_global_language',
                    $default['csvimport_global_language']),
            ],
        ]);
    }

    public function addAdvancedElements()
    {
        $services = $this->getServiceLocator();
        $userSettings = $services->get('Omeka\Settings\User');
        $config = $services->get('Config');
        $default = $config['csv_import']['user_settings'];

        $this->add([
            'type' => Fieldset::class,
            'name' => 'advanced-settings',
            'options' => [
                'label' => 'Advanced Settings', // @translate
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
            Import::ACTION_SKIP => 'Skip row', // @translate
        ];

        $advancedSettingsFieldset->add([
            'name' => 'action',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Action', // @translate
                'info' => 'In addition to the default "Create" and to the common "Delete", to manage most of the common cases, four modes of update are provided:
- append: add new data to complete the resource;
- revise: replace existing data to the resource by the ones set in each cell, except if empty (don’t modify data that are not provided, except for default values);
- update: replace existing data to the resource by the ones set in each cell, even empty (don’t modify data that are not provided, except for default values);
- replace: remove all properties of the resource, and fill new ones from the data.', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'action',
                'class' => 'advanced-settings',
            ],
        ]);

        $advancedSettingsFieldset->add([
            'name' => 'identifier_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Resource identifier property', // @translate
                'info' => 'Use this property, generally "dcterms:identifier", to identify the existing resources, so it will be possible to update them. One column of the file must map the selected property. In all cases, it is strongly recommended to add one ore more unique identifiers to all your resources.', // @translate
                'empty_option' => 'Select below', // @translate
                'term_as_value' => false,
                'prepend_value_options' => [
                    'internal_id' => 'Internal id', // @translate
                ],
                'term_as_value' => true,
            ],
            'attributes' => [
                'value' => $userSettings->get(
                    'csvimport_identifier_property',
                    $default['csvimport_identifier_property']),
                'class' => 'advanced-settings chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $advancedSettingsFieldset->add([
            'name' => 'action_unidentified',
            'type' => Element\Radio::class,
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
                'class' => 'advanced-settings',
                'value' => Import::ACTION_SKIP,
            ],
        ]);

        $advancedSettingsFieldset->add([
            'name' => 'rows_by_batch',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Number of rows to process by batch', // @translate
                'info' => 'By default, rows are processed by 20. In some cases, to set a value of 1 may avoid issues.', // @translate
            ],
            'attributes' => [
                'value' => $userSettings->get(
                    'csvimport_rows_by_batch',
                    $default['csvimport_rows_by_batch']),
                'class' => 'advanced-settings',
                'min' => '1',
                'step' => '1',
            ],
        ]);

        $this->inputFilter->add([
            'name' => 'action',
            'required' => false,
        ]);
        $this->inputFilter->add([
            'name' => 'identifier_property',
            'required' => false,
        ]);
        $this->inputFilter->add([
            'name' => 'action_unidentified',
            'required' => false,
        ]);
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
