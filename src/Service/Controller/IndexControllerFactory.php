<?php
namespace CSVImport\Service\Controller;

use CSVImport\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mediaIngesterManager = $serviceLocator->get('Omeka\Media\Ingester\Manager');
        $config = $serviceLocator->get('Config');
        $userSettings = $serviceLocator->get('Omeka\Settings\User');

        $indexController = new IndexController($config, $mediaIngesterManager, $userSettings);
        return $indexController;
    }
}
