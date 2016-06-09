<?php
namespace CSVImport\Form;

use Zend\Form\Form;

class ImportForm extends Form
{
    protected $mappingClasses;

    public function init()
    {
        $this->setAttribute('action', 'csvimport/map');
        $this->add(array(
                'name' => 'csv',
                'type' => 'file',
                'options' => array(
                    'label' => 'CSV File', // @translate
                    'info'  => 'The CSV File to upload', //@translate
                ),
                'attributes' => array(
                    'id' => 'csv',
                    'required' => 'true'
                )
        ));

        $resourceTypes = array_keys($this->mappingClasses);
        $valueOptions = [];
        foreach($resourceTypes as $resourceType) {
            $valueOptions[$resourceType] = ucfirst($resourceType);
        }
        $this->add(array(
                'name' => 'resource_type',
                'type' => 'select',
                'options' => array(
                    'label' => 'Import Type', // @translate
                    'info'  => 'The type of data being imported', // @translate
                    'value_options' => $valueOptions,
                ),
        ));
    }

    public function setMappingClasses(array $mappingClasses)
    {
        $this->mappingClasses = $mappingClasses;
    }
}
