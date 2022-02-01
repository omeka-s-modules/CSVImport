<?php
namespace CSVImport\Service\Controller;

use CSVImport\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mediaIngesterManager = $serviceLocator->get('Omeka\Media\Ingester\Manager');
        $config = $serviceLocator->get('CSVImport\Config');
        $userSettings = $serviceLocator->get('Omeka\Settings\User');
        $tempDir = $serviceLocator->get('Config')['temp_dir'];

        $indexController = new IndexController($config, $mediaIngesterManager, $userSettings, $tempDir);
        return $indexController;
    }
}
