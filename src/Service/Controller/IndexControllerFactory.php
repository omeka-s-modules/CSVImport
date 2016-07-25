<?php
namespace CSVImport\Service\Controller;

use CSVImport\Controller\IndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {   
        $serviceLocator = $serviceLocator->getServiceLocator();
        $mediaIngesterManager = $serviceLocator->get('Omeka\MediaIngesterManager');
        $config = $serviceLocator->get('Config');
        $indexController = new IndexController($config, $mediaIngesterManager);
        return $indexController;
    }
}
