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
