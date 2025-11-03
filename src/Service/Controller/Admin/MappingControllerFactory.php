<?php

namespace CSVImport\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use CSVImport\Controller\Admin\MappingController;

class MappingControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mappingModelController = new MappingController();

        return $mappingModelController;
    }
}
