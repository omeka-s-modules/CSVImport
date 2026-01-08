<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\SaveMappingModel;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SaveMappingModelFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new SaveMappingModel(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\Logger')
        );
    }
}
