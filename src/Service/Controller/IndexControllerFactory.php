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
        $logger = $serviceLocator->get('Omeka\Logger');
        $jobDispatcher = $serviceLocator->get('Omeka\JobDispatcher');
        $mediaIngesterManager = $serviceLocator->get('Omeka\MediaIngesterManager');
        $config = $serviceLocator->get('Config');
        $indexController = new IndexController;
        $servicesArray = [
            'logger'                => $logger,
            'jobDispatcher'         => $jobDispatcher,
            'mediaIngesterManager'  => $mediaIngesterManager,
            'config'                => $config,
        ];
        $indexController->setServices($servicesArray);
        return $indexController;
    }
}
