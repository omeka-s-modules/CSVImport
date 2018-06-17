<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ResourceSidebarFieldset;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ResourceSidebarFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $viewHelpers = $services->get('ViewHelperManager');
        $form = new ResourceSidebarFieldset(null, $options);
        $form->setEventManager($services->get('EventManager'));
        $form->setAcl($services->get('Omeka\Acl'));
        $form->setUrlHelper($viewHelpers->get('url'));
        $form->setUserSettingHelper($viewHelpers->get('userSetting'));
        $form->setTranslateHelper($viewHelpers->get('translate'));
        return $form;
    }
}
