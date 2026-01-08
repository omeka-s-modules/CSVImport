<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\MappingModelForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MappingModelFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new MappingModelForm(null, $options ?? []);
        $form->setServiceLocator($services);
        return $form;
    }
}
