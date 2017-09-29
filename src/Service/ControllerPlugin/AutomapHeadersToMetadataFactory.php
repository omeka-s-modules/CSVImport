<?php
namespace CSVImport\Service\ControllerPlugin;

use CSVImport\Mvc\Controller\Plugin\AutomapHeadersToMetadata;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AutomapHeadersToMetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        $plugin = new AutomapHeadersToMetadata();
        $plugin->setConfigCsvImport($config['csv_import']);
        return $plugin;
    }
}
