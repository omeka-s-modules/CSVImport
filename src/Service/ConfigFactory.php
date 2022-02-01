<?php
namespace CSVImport\Service;

use Laminas\EventManager\Event;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config')['csv_import'];
        $eventManager = $services->get('EventManager');

        $args = $eventManager->prepareArgs(['config' => $config]);
        $eventManager->triggerEvent(new Event('csv_import.config', null, $args));
        return $args['config'];
    }
}
