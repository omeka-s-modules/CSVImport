<?php
namespace CSVImport\Form;

use Omeka\Form\AbstractForm;

class ImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        $config = $this->getServiceLocator()->get('Config');
        $mappingClasses = $config['csv_import_mappings'];
        
        $this->setAttribute('action', 'csvimport/map');
        $this->add(array(
                'name' => 'csv',
                'type' => 'file',
                'options' => array(
                    'label' => $translator->translate('Csv File'),
                    'info'  => $translator->translate('The Csv File to upload')
                ),
                'attributes' => array(
                    'id' => 'csv',
                    'required' => 'true'
                )
        ));
        
        $resourceTypes = array_keys($mappingClasses);
        $valueOptions = [];
        foreach($resourceTypes as $resourceType) {
            $valueOptions[$resourceType] = ucfirst($resourceType);
        }
        $this->add(array(
                'name' => 'resource_type',
                'type' => 'select',
                'options' => array(
                    'label' => $translator->translate('Import Type'),
                    'info'  => $translator->translate('The type of data being imported'),
                    'value_options' => $valueOptions,
                ),
        ));
    }
}