<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\ItemSidebar;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ItemSidebarFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $user = $auth->getIdentity();
        return new ItemSidebar($user);
    }
}