<?php
namespace CSVImport\Form;

use Omeka\Settings\UserSettings;
use Zend\Form\Form;

class ImportForm extends Form
{
    /**
     * @var array
     */
    protected $configCsvImport;

    /**
     * @var UserSettings
     */
    protected $userSettings;

    /**
     * @var array
     */
    protected $mappingClasses;

    public function init()
    {
        $this->setAttribute('action', 'csvimport/map');

        $defaults = $this->configCsvImport['user_settings'];

        $this->add([
                'name' => 'csv',
                'type' => 'file',
                'options' => [
                    'label' => 'CSV file', // @translate
                    'info' => 'The CSV file to upload', //@translate
                ],
                'attributes' => [
                    'id' => 'csv',
                    'required' => 'true',
                ],
        ]);

        $resourceTypes = array_keys($this->mappingClasses);
        $valueOptions = [];
        foreach ($resourceTypes as $resourceType) {
            $valueOptions[$resourceType] = ucfirst($resourceType);
        }
        $this->add([
                'name' => 'resource_type',
                'type' => 'select',
                'options' => [
                    'label' => 'Import type', // @translate
                    'info' => 'The type of data being imported', // @translate
                    'value_options' => $valueOptions,
                ],
        ]);

        $this->add([
            'name' => 'automap_check_names_alone',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Automap with labels alone', // @translate
                'info' => 'Headers are mapped automatically, case sensitively and not, with standard names ("dcterms:title") and labels ("Dublin Core : Title").' // @translate
                    . ' ' . 'If checked, an automatic map will be done with names and labels only ("Title") too, Dublin Core first.', // @translate
            ],
            'attributes' => [
                'id' => 'automap_check_names_alone',
                'value' => (int) (bool) $this->userSettings->get(
                    'csv_import_automap_check_names_alone',
                    $defaults['csv_import_automap_check_names_alone']),
            ],
        ]);

        $checkUserList = (bool) $this->userSettings->get(
            'csv_import_automap_check_user_list',
            $defaults['csv_import_automap_check_user_list']);
        $this->add([
            'name' => 'automap_check_user_list',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Automap with user list', // @translate
                'info' => 'Try to automap first with specific headers, which is useful when a model of spreadsheet file is used.', // @translate
            ],
            'attributes' => [
                'id' => 'automap_check_user_list',
                'value' => (int) $checkUserList,
            ],
        ]);

        $list = $this->userSettings->get(
            'csv_import_automap_user_list',
            $defaults['csv_import_automap_user_list']);
        $value = '';
        foreach ($list as $name => $mapped) {
            $value .= $name . ' = ' . $mapped . PHP_EOL;
        }
        $this->add([
            'name' => 'automap_user_list',
            'type' => 'textarea',
            'options' => [
                'label' => 'Automap user list', // @translate
                'info' => 'List of user headers used to map the file automagically.' // @translate
                    . ' ' . 'Each line should contains a header (with or without case), a "=" and the property term or the mapping type (see readme).' // @translate
            ],
            'attributes' => [
                'id' => 'automap_user_list',
                'rows' => 12,
                'value' => $value,
                'style' => $checkUserList ? '' : 'display:none;',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'csv',
            'required' => true,
        ]);
    }

    public function setConfigCsvImport(array $configCsvImport)
    {
        $this->configCsvImport = $configCsvImport;
    }

    public function setUserSettings(UserSettings $userSettings)
    {
        $this->userSettings = $userSettings;
    }

    public function setMappingClasses(array $mappingClasses)
    {
        $this->mappingClasses = $mappingClasses;
    }
}
