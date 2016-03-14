<?php
namespace CSVImport\Form;

use Omeka\Form\AbstractForm;

class ImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        
        $this->setAttribute('action', 'csvimport/map');
        $this->add(array(
                'name' => 'csv',
                'type' => 'file',
                'options' => array(
                    'label' => $translator->translate('CSV File'),
                    'info'  => $translator->translate('The CSV File to upload')
                ),
                'attributes' => array(
                    'id' => 'csv',
                    'required' => 'true'
                )
        ));
    }
}