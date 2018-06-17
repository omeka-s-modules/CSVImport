<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\MappingForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new MappingForm(null, $options);
        $form->setEventManager($services->get('EventManager'));
        $form->setUrlHelper($services->get('ViewHelperManager')->get('url'));
        $form->setAcl($services->get('Omeka\Acl'));
        return $form;
    }
}
