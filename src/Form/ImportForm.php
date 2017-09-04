<?php
namespace CSVImport\Form;

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
        '"' => 'double-quote', // @translate
        "'" => 'quote', // @translate
        '#' => 'hash', // @translate
        // '' => 'empty', // @translate
    ];

    /**
     * @var array
     */
    protected $mappingClasses;

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
        $this->add([
            'name' => 'delimiter',
            'type' => 'select',
            'options' => [
                'label' => 'Column delimiter', // @translate
                'info'=> 'A single character that will be used to separate columns in the csv file.', // @translate
                'value_options' => $valueOptions,
            ],
        ]);

        $valueOptions = $this->getEnclosureList();
        $this->add([
            'name' => 'enclosure',
            'type' => 'select',
            'options' => [
                'label' => 'Column enclosure', // @translate
                'info' => 'A single character that will be used to separate columns in the csv file.' // @translate
                    . ' ' . 'The enclosure can be omitted when the content does not contain the delimiter.', // @translate
                'value_options' => $valueOptions,
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

    public function setMappingClasses(array $mappingClasses)
    {
        $this->mappingClasses = $mappingClasses;
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
