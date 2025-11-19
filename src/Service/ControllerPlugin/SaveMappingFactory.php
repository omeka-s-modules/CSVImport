<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\SaveMapping;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SaveMappingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new SaveMapping(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\Logger')
        );
    }
}
