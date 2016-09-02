<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\MediaSidebar;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MediaSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new MediaSidebar($services->get('Omeka\MediaIngesterManager'));
    }
}
