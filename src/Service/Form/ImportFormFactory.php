<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ImportForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options);
        $config = $services->get('Config');
        $form->setConfigCsvImport($config['csv_import']);
        $form->setUserSettings($services->get('Omeka\Settings\User'));
        return $form;
    }
}
