<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\ResourceSidebar;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ResourceSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $auth = $services->get('Omeka\AuthenticationService');
        $user = $auth->getIdentity();
        return new ResourceSidebar($user);
    }
}
