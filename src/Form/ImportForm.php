<?php
namespace CSVImport\Form;

use Omeka\Settings\UserSettings;
use Zend\Form\Form;

class ImportForm extends Form
{
    /**
     * A list of standard delimiters.
     *
     * @var array
     */
    protected $delimiterList = [
        ',' => 'comma', // @translate
        ';' => 'semi-colon', // @translate
        ':' => 'colon', // @translate
        "__>\t<__" => 'tabulation', // @translate
        '__>\r<__' => 'carriage return', // @translate
        '__> <__' => 'space', // @translate
        '|' => 'pipe', // @translate
        // '  ' => 'double space', // @translate
        // '' => 'empty', // @translate
    ];

    /**
     * A list of standard enclosures.
     *
     * @var array
     */
    protected $enclosureList = [
        '"' => 'double quote', // @translate
        "'" => 'quote', // @translate
        '#' => 'hash', // @translate
        // '' => 'empty', // @translate
    ];

    /**
     * @var array
     */
    protected $mappingClasses;

    /**
     * @var array
     */
    protected $defaultSettings;

    /**
     * @var UserSettings
     */
    protected $userSettings;

    public function init()
    {
        $this->setAttribute('action', 'csvimport/map');
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

        $valueOptions = $this->getDelimiterList();
        $value = $this->userSettings->get('csv_import_delimiter', $this->defaultSettings['csv_import_delimiter']);
        $this->add([
            'name' => 'delimiter',
            'type' => 'select',
            'options' => [
                'label' => 'Column delimiter', // @translate
                'info'=> 'A single character that will be used to separate columns in the csv file.', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'value' => $this->integrateCsvOption($value),
            ],
        ]);

        $valueOptions = $this->getEnclosureList();
        $value = $this->userSettings->get('csv_import_enclosure', $this->defaultSettings['csv_import_enclosure']);
        $this->add([
            'name' => 'enclosure',
            'type' => 'select',
            'options' => [
                'label' => 'Column enclosure', // @translate
                'info' => 'A single character that will be used to separate columns in the csv file.' // @translate
                    . ' ' . 'The enclosure can be omitted when the content does not contain the delimiter.', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'value' => $this->integrateCsvOption($value),
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

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'csv',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'delimiter',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'enclosure',
            'required' => false,
        ]);
    }

    /**
     * Extract values that can’t be passed via a select form element in Zend.
     *
     * The values is extracted from a string between "__>" and "<__".
     *
     * @param string $value
     * @return string
     */
    public function extractCsvOption($value)
    {
        if (strpos($value, '__>') === 0
            && ($pos = strpos($value, '<__')) == (strlen($value) - 3)
        ) {
            $result = substr($value, 3, $pos - 3);
            return $result === '\r' ? "\r" : $result;
        }
        return $value;
    }

    /**
     * Integrate values that can’t be passed via a select form element in Zend.
     *
     * The values are integrated with a string between "__>" and "<__".
     *
     * @param string $value
     * @return string
     */
    public function integrateCsvOption($value)
    {
        $specialValues = ["\r", "\t", ' '];
        return in_array($value, $specialValues, true)
            ? sprintf('__>%s<__', $value)
            : $value;
    }

    /**
     * @param array $mappingClasses
     */
    public function setMappingClasses(array $mappingClasses)
    {
        $this->mappingClasses = $mappingClasses;
    }

    /**
     * @param array $defaultSettings
     */
    public function setDefaultSettings(array $defaultSettings)
    {
        $this->defaultSettings= $defaultSettings;
    }

    /**
     * @param UserSettings $userSettings
     */
    public function setUserSettings(UserSettings $userSettings)
    {
        $this->userSettings = $userSettings;
    }

    /**
     * Return a list of standard delimiters.
     *
     * @return array
     */
    public function getDelimiterList()
    {
        return $this->delimiterList;
    }

    /**
     * Return a list of standard enclosures.
     *
     * @return array
     */
    public function getEnclosureList()
    {
        return $this->enclosureList;
    }
}
