<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\LoadMapping;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LoadMappingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new LoadMapping(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\Logger')
        );
    }
}
