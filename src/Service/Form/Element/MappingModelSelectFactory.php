<?php
namespace CSVImport\Service\Form\Element;

use Interop\Container\ContainerInterface;
use CSVImport\Form\Element\MappingModelSelect;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MappingModelSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new MappingModelSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
