<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\AutomapHeadersToMetadata;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AutomapHeadersToMetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $plugin = new AutomapHeadersToMetadata();
        $plugin->setConfigCsvImport($serviceLocator->get('CSVImport\Config'));
        return $plugin;
    }
}
