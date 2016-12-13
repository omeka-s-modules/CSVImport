<?php
namespace CSVImport\Form;

use Zend\Form\Form;

class ImportForm extends Form
{
    protected $mappingClasses;

    public function init()
    {
        $this->setAttribute('action', 'csvimport/map');
        $this->add([
                'name' => 'csv',
                'type' => 'file',
                'options' => [
                    'label' => 'CSV File', // @translate
                    'info'  => 'The CSV File to upload', //@translate
                ],
                'attributes' => [
                    'id' => 'csv',
                    'required' => 'true'
                ]
        ]);

        $resourceTypes = array_keys($this->mappingClasses);
        $valueOptions = [];
        foreach($resourceTypes as $resourceType) {
            $valueOptions[$resourceType] = ucfirst($resourceType);
        }
        $this->add([
                'name' => 'resource_type',
                'type' => 'select',
                'options' => [
                    'label' => 'Import Type', // @translate
                    'info'  => 'The type of data being imported', // @translate
                    'value_options' => $valueOptions,
                ],
        ]);
    }

    public function setMappingClasses(array $mappingClasses)
    {
        $this->mappingClasses = $mappingClasses;
    }
}
