<?php
namespace CSVImport\Service\Form\Element;

use Interop\Container\ContainerInterface;
use CSVImport\Form\Element\MappingSelect;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MappingSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new MappingSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
