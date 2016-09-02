<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\ItemSidebar;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ItemSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $auth = $services->get('Omeka\AuthenticationService');
        $user = $auth->getIdentity();
        return new ItemSidebar($user);
    }
}
