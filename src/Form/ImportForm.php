<?php
namespace CSVImport\Form;

use Omeka\Settings\UserSettings;
use Laminas\Form\Form;

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
        "'" => 'single quote', // @translate
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

    public function init()
    {
        $this->setAttribute('action', 'csvimport/map');

        $defaults = $this->configCsvImport['user_settings'];

        $this->add([
                'name' => 'source',
                'type' => 'file',
                'options' => [
                    'label' => 'Spreadsheet (csv, tsv or ods)', // @translate
                    'info' => 'The CSV, TSV or ODS file to upload. LibreOffice is recommended for compliant formats.', //@translate
                ],
                'attributes' => [
                    'id' => 'source',
                    'required' => 'true',
                ],
        ]);

        // TODO Move the specific parameters into the source class.

        // Commenting out code that uses UserSettings in case we want to replace or
        // use them differently later

        $valueParameters = $this->getDelimiterList();
        $value = $this->userSettings->get('csv_import_delimiter', $defaults['csv_import_delimiter']);
        $this->add([
            'name' => 'delimiter',
            'type' => 'select',
            'options' => [
                'label' => 'CSV column delimiter', // @translate
                'info' => 'A single character that will be used to separate columns in the csv file.', // @translate
                'value_options' => $valueParameters,
            ],
            'attributes' => [
                'value' => $this->integrateParameter($value),
            ],
        ]);

        $valueParameters = $this->getEnclosureList();
        $value = $this->userSettings->get('csv_import_enclosure', $defaults['csv_import_enclosure']);
        $this->add([
            'name' => 'enclosure',
            'type' => 'select',
            'options' => [
                'label' => 'CSV column enclosure', // @translate
                'info' => 'A single character that will be used to separate columns in the csv file. The enclosure can be omitted when the content does not contain the delimiter.', // @translate
                'value_options' => $valueParameters,
            ],
            'attributes' => [
                'value' => $this->integrateParameter($value),
            ],
        ]);

        $this->add([
                'name' => 'resource_type',
                'type' => 'select',
                'options' => [
                    'label' => 'Import type', // @translate
                    'info' => 'The type of data being imported', // @translate
                    'value_options' => [
                        'items' => 'Items', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'media' => 'Media', // @translate
                        'resources' => 'Mixed resources', // @translate
                        'users' => 'Users', // @translate
                    ],
                ],
                'attributes' => [
                    'value' => 'items',
                ],
        ]);

        $this->add([
            'name' => 'automap_check_names_alone',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Automap with simple labels', // @translate
                'info' => 'If checked, column headings that match property labels will be mapped automatically (for example, "Title" to dcterms:title).', // @translate
            ],
            'attributes' => [
                'id' => 'automap_check_names_alone',
                'value' => (int) (bool) $this->userSettings->get(
                    'csv_import_automap_check_names_alone',
                    $defaults['csv_import_automap_check_names_alone']),
            ],
        ]);

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

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'source',
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
    public function extractParameter($value)
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
    public function integrateParameter($value)
    {
        $specialValues = ["\r", "\t", ' '];
        return in_array($value, $specialValues, true)
            ? sprintf('__>%s<__', $value)
            : $value;
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
