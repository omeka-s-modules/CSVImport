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
    protected $configCsvImport;

    /**
     * @var UserSettings
     */
    protected $userSettings;

    /**
     * @var array
     */
    protected $mappingClasses;

    /**
     * @var array
     */
    protected $defaultSettings;

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
            // Currently, there is no resource label. It should be in the Omeka
            // vocabulary, for example "item_sets" => "o:ItemSet" => "Item set".
            // So there is no translation too.
            $valueOptions[$resourceType] = str_replace('_', ' ',ucfirst($resourceType));
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
        $value = $this->convertUserListArrayToText($list);
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
     * Convert a user list text into an array.
     *
     * @param string $text
     * @return array
     */
    public function convertUserListTextToArray($text)
    {
        $result = [];
        $text = str_replace('  ', ' ', $text);
        $list = array_filter(array_map('trim', explode(PHP_EOL, $text)));
        foreach ($list as $line) {
            $map = array_filter(array_map('trim', explode('=', $line)));
            if (count($map) === 2) {
                $result[$map[0]] = $map[1];
            } else {
                $result[$line] = '';
            }
        }
        return $result;
    }

    /**
     * Convert a user list array into a text.
     *
     * @param array $list
     * @return string
     */
    public function convertUserListArrayToText($list)
    {
        $result = '';
        foreach ($list as $name => $mapped) {
            $result .= $name . ' = ' . $mapped . PHP_EOL;
        }
        return $result;
    }

    public function setConfigCsvImport(array $configCsvImport)
    {
        $this->configCsvImport = $configCsvImport;
    }

    public function setUserSettings(UserSettings $userSettings)
    {
        $this->userSettings = $userSettings;
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
