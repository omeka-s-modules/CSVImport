<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ImportForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options ?? []);
        $form->setConfigCsvImport($services->get('CSVImport\Config'));
        $form->setUserSettings($services->get('Omeka\Settings\User'));
        return $form;
    }
}
