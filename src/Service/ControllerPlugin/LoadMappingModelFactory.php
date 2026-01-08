<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\LoadMappingModel;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LoadMappingModelFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new LoadMappingModel(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\Logger')
        );
    }
}
