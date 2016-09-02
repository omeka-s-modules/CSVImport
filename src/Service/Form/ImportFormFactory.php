<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ImportForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options);
        $config = $services->get('Config');
        $form->setMappingClasses($config['csv_import_mappings']);
        return $form;
    }
}
