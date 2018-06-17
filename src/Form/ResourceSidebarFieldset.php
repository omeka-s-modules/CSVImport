<?php
namespace CSVImport\Form;

use Omeka\Form\Element\PropertySelect;
use Omeka\Permissions\Acl;
use Omeka\View\Helper\Setting;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\I18n\View\Helper\Translate;
use Zend\View\Helper\Url;

class ResourceSidebarFieldset extends Fieldset
{
    use EventManagerAwareTrait;

    /**
     * @var array
     */
    protected $resourceTypeLabels;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var Setting
     */
    protected $userSettingHelper;

    /**
     * @var Translate
     */
    protected $translateHelper;

    public function init()
    {
        // Initialize the default resource type labels directly.
        $this->resourceTypeLabels = [
            'items' => 'Item', // @translate
            'item_sets' => 'Item set', // @translate
            'media' => 'Media', // @translate
            'resources' => 'Resources', // @translate
        ];

        $resourceType = $this->getOption('resourceType');

        switch ($resourceType) {
              case empty($resourceType):
              case 'resources':
                $this->addResourceElements();
                $this->addItemSetElements();
                $this->addItemElements();
                $this->addMediaElements();
                // No rule for resources, so use item.
                $this->addGenericResourceElements(\Omeka\Entity\Item::class);
                break;
          case 'item_sets':
                $this->addItemSetElements();
                $this->addGenericResourceElements(\Omeka\Entity\ItemSet::class);
                break;
            case 'items':
                $this->addItemElements();
                $this->addGenericResourceElements(\Omeka\Entity\Item::class);
                break;
            case 'media':
                $this->addMediaElements();
                $this->addGenericResourceElements(\Omeka\Entity\Media::class);
                break;
        }

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        // No filter for a fieldset: use the main MappingForm.
    }

    public function addResourceElements()
    {
        $translate = $this->getTranslateHelper();

        $valueOptions = [
            'default' => 'Select below', // @translate
            'items' => 'Item', // @translate
            'item-sets' => 'Item set', // @translate
            'media' => 'Media', // @translate
        ];
        $this->add([
            'name' => 'data_resource_type_select',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Resource type', // @translate
                'info' => $translate('Set the resource type to get specific fields for it.'), // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'data-resource-type-select',
                'class' => 'advanced-settings',
            ],
        ]);
    }

    public function addItemSetElements()
    {
        $translate = $this->getTranslateHelper();

        $this->add([
            'name' => 'additions',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Open to additions', // @translate
            ],
            'attributes' => [
                'id' => 'additions',
                'value' => '0',
                'data-flag-class' => 'resource-data item-sets',
                'data-flag-name' => 'column-is_open',
                'data-flag-label' => $translate('Open to additions'), // @translate
            ]
        ]);
    }

    public function addItemElements()
    {
        $userSetting = $this->getUserSettingHelper();
        $translate = $this->getTranslateHelper();

        $this->add([
            'name' => 'column-item_set_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item set identifier', // @translate
                'empty_option' => 'Select below', // @translate
                'prepend_value_options' => [
                    'internal_id' => 'Internal id', // @translate
                ],
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'column-item_set_property',
                'value' => $userSetting('csvimport_identifier_property', 'internal_id'),
                'class' => 'chosen-select',
                'data-placeholder' => $translate('Select the identifier below'), // @translate
                'data-flag-name' => 'column-item_set',
                'data-flag-class' => 'resource-data items',
                'data-flag-label' => $translate('Item set'), // @translate
            ],
        ]);
    }

    public function addMediaElements()
    {
        $userSetting = $this->getUserSettingHelper();
        $translate = $this->getTranslateHelper();

        $this->add([
            'name' => 'column-item_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item identifier', // @translate
                'info' => $translate('There must be an item identifier to create a media. This is generally "dcterms:identifier", but it may be an internal id.'),
                'empty_option' => 'Select below', // @translate
                'prepend_value_options' => [
                    'internal_id' => 'Internal id', // @translate
                ],
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'column-item_property',
                'value' => $userSetting('csvimport_identifier_property', 'internal_id'),
                'class' => 'chosen-select',
                'data-placeholder' => $translate('Select the identifier below'), // @translate
                'data-flag-name' => 'column-item',
                'data-flag-class' => 'resource-data media',
                'data-flag-label' => $translate('Item'), // @translate
            ],
        ]);
    }

    public function addGenericResourceElements($resourceTypeClass = null)
    {
        $acl = $this->getAcl();
        $resourceType = $this->getOption('resourceType');
        $translate = $this->getTranslateHelper();

        $valueOptions = [];
        if (empty($resourceType) || $resourceType === 'resources') {
            $valueOptions['column-resource_type'] = 'Resource type'; // @translate,
        }
        $valueOptions['column-resource'] = 'Internal id'; // @translate,
        $valueOptions['column-resource_template'] = 'Resource template name'; // @translate,
        $valueOptions['column-resource_class'] = 'Resource class term'; // @translate,
        if ($resourceTypeClass && $acl->userIsAllowed($resourceTypeClass, 'change-owner')) {
            $valueOptions['column-owner_email'] = 'Owner email address'; // @translate,
        }
        $valueOptions['column-is_public'] = 'Visibility public/private'; // @translate,

        $this->add([
            'name' => 'resource_data',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Generic data', // @translate
                'empty_option' => 'Select below', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'resource_data',
                'class' => 'flags',
                'data-flag-class' => 'resource-data',
                'data-flag-label' => $translate('Data'), // @translate
            ],
        ]);
    }

    public function setResourceTypeLabels(array $resourceTypes)
    {
        $this->resourceTypeLabels = $resourceTypes;
    }

    public function getResourceTypeLabels()
    {
        return $this->resourceTypeLabels;
    }

    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    protected function getUrlHelper()
    {
        return $this->urlHelper;
    }

    public function setUserSettingHelper(Setting $userSettingHelper)
    {
        $this->userSettingHelper = $userSettingHelper;
    }

    protected function getUserSettingHelper()
    {
        return $this->userSettingHelper;
    }

    public function setTranslateHelper(Translate $translateHelper)
    {
        $this->translateHelper = $translateHelper;
    }

    protected function getTranslateHelper()
    {
        return $this->translateHelper;
    }
}
