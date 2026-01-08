<?php

namespace CSVImport\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use CSVImport\Controller\Admin\MappingModelController;

class MappingModelControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mappingModelController = new MappingModelController();

        return $mappingModelController;
    }
}
