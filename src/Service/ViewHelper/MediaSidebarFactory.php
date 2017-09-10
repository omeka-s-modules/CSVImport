<?php
namespace CSVImport\Service\ViewHelper;

use CSVImport\View\Helper\MediaSidebar;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MediaSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $translator = $services->get('MvcTranslator');
        $ingesterManager = $services->get('Omeka\Media\Ingester\Manager');
        $mediaAdapters = $config['csv_import_media_ingester_adapter'];
        return new MediaSidebar($ingesterManager, $mediaAdapters, $translator);
    }
}
