<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\MediaSidebar;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MediaSidebarFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new MediaSidebar($serviceLocator->get('Omeka\MediaIngesterManager'));
    }
}
