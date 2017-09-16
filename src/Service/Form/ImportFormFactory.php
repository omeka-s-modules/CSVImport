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
        $userSettings = $services->get('Omeka\Settings\User');
        $form->setConfigCsvImport($config['csv_import']);
        $form->setUserSettings($services->get('Omeka\Settings\User'));
        return $form;
    }
}
