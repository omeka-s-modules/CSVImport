<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ImportForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ImportFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new ImportForm(null, $this->options);
        $config = $elements->getServiceLocator()->get('Config');
        $form->setMappingClasses($config['csv_import_mappings']);
        return $form;
    }
}
